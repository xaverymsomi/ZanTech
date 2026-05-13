<?php

namespace Library;

use Database\Database;
use Loggers\Log;
use Throwable;

final class DualControl
{
    private Database $db;
    private string $model;
    private string $action;
    public bool $dualRequired = false;
    private ?int $groupId = null;
    private ?string $makerUserId = null;
    private string $tokenGuid;

    public function __construct(string $model, string $action)
    {
        $this->db = \Database\DB::connection();

        $this->model  = $model;
        $this->action = $action;

        $this->makerUserId = (string)(\Authentication\Auth::id() ?? '');

        $this->tokenGuid = $this->newGuid();
    }

    /**
     * Returns:
     *  - true  => dual required AND request created (so caller should NOT execute action)
     *  - false => dual not required (caller may execute action)
     */
    public function getResult(): bool
    {
        $rule = $this->findDualRule();
        if ($rule === null) {
            $this->dualRequired = false;
            return false;
        }

        // dual activity enabled?
        if ((int)($rule['int_require_dual_activity'] ?? 0) !== 1) {
            $this->dualRequired = false;
            return false;
        }

        // resolve group for this rule (if missing -> treat as non-dual)
        $groupId = $this->findRuleGroupId((int)$rule['id']);
        if ($groupId === null) {
            $this->dualRequired = false;
            return false;
        }

        $this->dualRequired = true;
        $this->groupId = $groupId;

        // Build reference value for checker (from txt_table/txt_column)
        $referenceValue = $this->resolveReferenceValue(
            (string)($rule['txt_table'] ?? ''),
            (string)($rule['txt_column'] ?? '')
        );

        // Create pending approval rows for checkers + send email
        $this->createApprovalRequests((int)$rule['id'], $referenceValue);

        // IMPORTANT: block execution (maker must wait for checker)
        return true;
    }

    private function findDualRule(): ?array
    {
        $rows = $this->db->select(
            "SELECT TOP 1 *
               FROM mx_dual_activity
              WHERE txt_model = :model
                AND txt_action = :action",
            [':model' => $this->model, ':action' => $this->action]
        );

        return $rows[0] ?? null;
    }

    private function findRuleGroupId(int $dualActivityId): ?int
    {
        $rows = $this->db->select(
            "SELECT TOP 1 opt_mx_group_id
               FROM mx_dual_activity_group
              WHERE opt_mx_dual_activity_id = :id",
            [':id' => $dualActivityId]
        );

        if (empty($rows)) return null;
        return (int)$rows[0]['opt_mx_group_id'];
    }

    /**
     * Uses txt_table + txt_column to fetch a human readable reference value
     * that the checker can understand (control number, account number, etc.)
     */
    private function resolveReferenceValue(string $table, string $column): ?string
    {
        $table = trim($table);
        $column = trim($column);

        if ($table === '' || $column === '') {
            // no reference configured -> still allow dual request, but with null reference
            return null;
        }

        // Expected payload contains id (txt_row_value or numeric id)
        $payload = $this->readRequestPayload();
        $id = $payload['id'] ?? null;

        if ($id === null || $id === '') {
            Log::sysErr([
                'message' => 'DualControl: missing id in payload for reference lookup',
                'model'   => $this->model,
                'action'  => $this->action,
            ]);
            return null;
        }

        // Quote identifiers safely (requires your Database helpers)
        $tableQ = $this->db->quoteTable($table);
        $colQ   = $this->db->quoteColumn($column);
        $rowQ   = $this->db->quoteColumn('txt_row_value');
        $idQ    = $this->db->quoteColumn('id');

        // Your old code used strlen>20 to decide row_value vs id
        $sql = (strlen((string)$id) > 20)
            ? "SELECT {$colQ} AS ref FROM {$tableQ} WHERE {$rowQ} = :id"
            : "SELECT {$colQ} AS ref FROM {$tableQ} WHERE {$idQ} = :id";

        $rows = $this->db->select($sql, [':id' => $id]);

        return isset($rows[0]['ref']) ? (string)$rows[0]['ref'] : null;
    }

    private function readRequestPayload(): array
    {
        return \Library\Request::all();
    }

    private function createApprovalRequests(int $dualActivityId, ?string $referenceValue): void
    {
        // 1) find checkers (users belonging to group)
        $checkers = $this->db->select(
            "SELECT lcg.opt_mx_login_credential_id AS checker_id
               FROM mx_login_credential_group lcg
              WHERE lcg.opt_mx_group_id = :gid",
            [':gid' => $this->groupId]
        );

        if (empty($checkers)) {
            Log::sysLog("DualControl: no checkers found for group_id={$this->groupId}");
            return;
        }

        $token = base64_encode($this->tokenGuid);

        $duplicates = 0;

        foreach ($checkers as $c) {
            $checkerId = (int)($c['checker_id'] ?? 0);
            if ($checkerId <= 0) continue;

            // prevent duplicates for same activity+checker+reference while pending
            $dup = $this->db->select(
                "SELECT TOP 1 id
                   FROM mx_dual_activity_log
                  WHERE opt_mx_dual_activity_id = :aid
                    AND opt_mx_login_credential_id = :cid
                    AND ISNULL(txt_column_value,'') = :ref
                    AND int_status = 0",
                [
                    ':aid' => $dualActivityId,
                    ':cid' => $checkerId,
                    ':ref' => (string)($referenceValue ?? ''),
                ]
            );

            if (!empty($dup)) {
                $duplicates++;
                continue;
            }

            $sql = "INSERT INTO mx_dual_activity_log
                    (opt_mx_dual_activity_id, opt_mx_login_credential_id, txt_token, txt_column_value,
                     dat_activity_triggered_date, int_activity_triggered_by, int_status, txt_row_value)
                    VALUES
                    (:aid, :cid, :token, :ref, GETDATE(), :by, 0, :row)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':aid'  => $dualActivityId,
                ':cid'  => $checkerId,
                ':token'=> $token,
                ':ref'  => $referenceValue,
                ':by'   => $this->makerUserId,
                ':row'  => $this->newGuid(),
            ]);

            // 2) notify checker (email)
//            $this->sendApprovalEmail($checkerId, $token, $referenceValue);
        }

        Log::sysLog("DualControl: approval queued dual_activity_id={$dualActivityId} group_id={$this->groupId} duplicates={$duplicates}");
    }

    private function sendApprovalEmail(int $checkerLoginCredentialId, string $token, ?string $referenceValue): void
    {
        // get recipient email/username
        $u = $this->db->select(
            "SELECT TOP 1 txt_username
               FROM mx_login_credential
              WHERE id = :id",
            [':id' => $checkerLoginCredentialId]
        );

        $recipient = (string)($u[0]['txt_username'] ?? '');
        if ($recipient === '') return;

        try {
            $mail = new MXMail();

            // Template 13 as you already use it
            $mail->sendEmail(
                13,
                $recipient,
                null,
                ['_url', '_token', '_section', '_action', '_reference'],
                [
                    defined('URL') ? URL : '', 
                    $token, 
                    $this->cleanData($this->model), 
                    $this->cleanData($this->action), 
                    (string)($referenceValue ?? '')
                ]
            );
        } catch (Throwable $e) {
            Log::sysErr(['message' => 'DualControl email failed: ' . $e->getMessage()]);
        }
    }

    private function cleanData(string $data): string
    {
        if (str_starts_with($data, 'post')) {
            return ucwords(str_replace('_', ' ', substr($data, 4)));
        }
        return ucwords(str_replace('_', ' ', $data));
    }

    private function newGuid(): string
    {
        if (function_exists('com_create_guid')) {
            return trim((string)com_create_guid(), '{}');
        }

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(16384, 20479),
            mt_rand(32768, 49151),
            mt_rand(0, 65535),
            mt_rand(0, 65535),
            mt_rand(0, 65535)
        );
    }
}
