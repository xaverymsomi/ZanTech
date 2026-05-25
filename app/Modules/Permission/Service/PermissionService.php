<?php

namespace Modules\Permission\Service;
use Database\Database;
use Exception;
use Logging\Log;

class PermissionService
{
    private Database $db;
    private $model;

    public function __construct(Database $db, $model)
    {
        $this->db = $db;
        $this->model = $model;
    }

    public function logError(string $context, Exception $e): void
    {
        Log::sysLog('[PERMISSION][' . $context . '] ' . $e->getMessage());
    }

    public function getUserGroups(string $domain, string $rowValue): array
    {
        PermissionValidator::assertSafeDomain($domain);

        $userId = (int)$this->model->getRecordIdByRowValue($domain, $rowValue);

        $parent = $this->db->select(
            "SELECT id FROM mx_login_credential WHERE txt_domain = :domain AND user_id = :user_id",
            [':domain' => $domain, ':user_id' => $userId]
        );

        $allGroups = $this->getGroups();

        if (!count($parent)) {
            return $this->processGroups([], $allGroups);
        }

        $userGroups = $this->db->select(
            "SELECT g.txt_name AS group_name
             FROM mx_login_credential_group lg
             JOIN mx_group g ON g.id = lg.opt_mx_group_id
             WHERE lg.opt_mx_login_credential_id = :cred_id
             ORDER BY g.id",
            [':cred_id' => (int)$parent[0]['id']]
        );

        return $this->processGroups($userGroups, $allGroups);
    }

    public function saveUserGroups(string $userRowValue, array $newData): array
    {
        $user = $this->model->getRecordByRowValue('mx_user', $userRowValue);
        if (!$user) {
            return ['status' => 404, 'title' => 'User not found'];
        }

        $parent = $this->model->getRecordByFieldName('mx_login_credential', 'user_id', $user['id']);
        if (!$parent) {
            return ['status' => 404, 'title' => 'Failed to get credentials record'];
        }

        $parentId = (int)$parent['id'];

        $this->model->db->beginTransaction();
        try {
            $this->exec(
                "DELETE FROM mx_login_credential_group WHERE opt_mx_login_credential_id = :id",
                [':id' => $parentId]
            );

            $status = $this->saveCheckedData(
                $newData,
                $parentId,
                'mx_login_credential_group',
                'opt_mx_login_credential_id, opt_mx_group_id, txt_row_value'
            );

            $this->model->db->commit();
            return ['status' => $status, 'title' => 'User Groups Saved Successfully'];
        } catch (Exception $e) {
            $this->model->db->rollBack();
            Log::sysLog('[PERMISSION][' . $userRowValue . '] ' . $e->getMessage());
            return ['status' => false, 'title' => 'Error while saving user groups'];
        }
    }

    public function getGroupPermissions(int $groupId): array
    {
        $given = [];

        if (in_array((string)($_SESSION['role'] ?? ''), ['3'], true)) {
            $given = $this->db->select(
                "SELECT p.txt_name AS per_name, s.txt_name AS section_name
                 FROM mx_group_permission gp
                 JOIN mx_permission p ON p.id = gp.opt_mx_permission_id
                 JOIN mx_section s ON s.id = p.opt_mx_section_id
                 WHERE gp.opt_mx_group_id = :gid
                 ORDER BY p.opt_mx_section_id",
                [':gid' => $groupId]
            );
        } else {
            // kept compatible with your original logic, but now parameterized
            $given = $this->db->select(
                "SELECT p.txt_name AS per_name, s.txt_name AS section_name
                 FROM mx_permission p
                 JOIN mx_section s ON s.id = p.opt_mx_section_id
                 WHERE p.id IN (
                    SELECT gp.opt_mx_permission_id
                    FROM mx_group_permission gp
                    WHERE gp.opt_mx_group_id IN (
                        SELECT lg.opt_mx_group_id
                        FROM mx_login_credential_group lg
                        WHERE lg.opt_mx_group_id = :gid
                    )
                 )",
                [':gid' => $groupId]
            );
        }

        $all = $this->getPermissions();
        return $this->processPermissions($given, $all);
    }

    public function saveGroupPermissions(int $groupId, array $newData): array
    {
        $this->model->db->beginTransaction();
        try {
            $this->exec(
                "DELETE FROM mx_group_permission WHERE opt_mx_group_id = :gid",
                [':gid' => $groupId]
            );

            $status = $this->saveCheckedData(
                $newData,
                $groupId,
                'mx_group_permission',
                'opt_mx_group_id, opt_mx_permission_id, txt_row_value'
            );

            $this->model->db->commit();
            return ['status' => $status, 'title' => 'Group Permissions'];
        } catch (Exception $e) {
            $this->model->db->rollBack();
            throw $e;
        }
    }

    public function getUserPermissions(string $domain, string $rowValue): array
    {
        PermissionValidator::assertSafeDomain($domain);

        $userId = (int)$this->model->getRecordIdByRowValue($domain, $rowValue);

        $given = $this->db->select(
            "SELECT p.txt_name AS per_name, s.txt_name AS section_name
             FROM mx_login_credential_permission lp
             JOIN mx_permission p ON p.id = lp.opt_mx_permission_id
             JOIN mx_section s ON s.id = p.opt_mx_section_id
             WHERE lp.opt_mx_login_credential_id IN (
                SELECT id FROM mx_login_credential
                WHERE user_id = :user_id AND txt_domain = :domain
             )
             ORDER BY p.opt_mx_section_id",
            [':user_id' => $userId, ':domain' => $domain]
        );

        $all = $this->getPermissions();
        return $this->processPermissions($given, $all);
    }

    public function saveUserPermissions(string $domain, string $rowValue, array $newData): array
    {
        PermissionValidator::assertSafeDomain($domain);

        $userId = (int)$this->model->getRecordIdByRowValue($domain, $rowValue);

        $parent = $this->db->select(
            "SELECT id FROM mx_login_credential WHERE txt_domain = :domain AND user_id = :user_id",
            [':domain' => $domain, ':user_id' => $userId]
        );

        if (!count($parent)) {
            return ['status' => 404, 'title' => 'Failed to Update User Permissions'];
        }

        $credId = (int)$parent[0]['id'];

        $this->model->db->beginTransaction();
        try {
            $this->exec(
                "DELETE FROM mx_login_credential_permission WHERE opt_mx_login_credential_id = :cid",
                [':cid' => $credId]
            );

            $status = $this->saveCheckedData(
                $newData,
                $credId,
                'mx_login_credential_permission',
                'opt_mx_login_credential_id, opt_mx_permission_id, txt_row_value'
            );

            $this->model->db->commit();
            return ['status' => 200, 'title' => 'User Permissions updated successfully'];
        } catch (Exception $e) {
            $this->model->db->rollBack();
            throw $e;
        }
    }

    public function createGroup(string $name, int $addedBy): array
    {
        $safeName = PermissionValidator::sanitizeName($name);

        $sql = "INSERT INTO mx_group (txt_name, int_added_by, txt_row_value)
                VALUES (:name, :added_by, " . $this->guidExpr() . ")";

        $ok = $this->exec($sql, [':name' => $safeName, ':added_by' => $addedBy]);
        return ['status' => $ok ? 200 : 100, 'title' => 'Group Data'];
    }

    public function createPermission(string $displayName, string $name, int $sectionId): array
    {
        $safeDisplay = PermissionValidator::sanitizeName($displayName, 120);
        $safeName = PermissionValidator::sanitizePermissionKey($name);

        $sql = "INSERT INTO mx_permission (txt_display_name, txt_name, opt_mx_section_id, txt_row_value)
                VALUES (:display, :name, :section_id, " . $this->guidExpr() . ")";

        $ok = $this->exec($sql, [
            ':display' => $safeDisplay,
            ':name' => $safeName,
            ':section_id' => $sectionId,
        ]);

        return ['status' => $ok ? 200 : 100, 'title' => $ok ? 'Permission Saved Successfully' : 'Failed to save Permission'];
    }

    public function createSection(string $name): array
    {
        $this->model->db->beginTransaction();
        try {
            $data = [
                'txt_name' => PermissionValidator::sanitizeName($name, 120),
                'txt_row_value' => $this->model->getGUID('mx_section'),
            ];

            $ok = $this->model->create($data, 'mx_section');
            $this->model->db->commit();

            return ['status' => $ok ? 200 : 100, 'title' => $ok ? 'Section Saved Successfully' : 'Failed to Save Section'];
        } catch (Exception $e) {
            $this->model->db->rollBack();
            throw $e;
        }
    }

    /** ===== helpers (ported from old controller but secured) ===== */

    private function guidExpr(): string
    {
        $dbType = $_ENV['DB_TYPE'] ?? '';
        if ($dbType === 'sqlsrv' || $dbType === 'odbc') return 'NEWID()';
        return 'UUID()';
    }

    private function exec(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    private function saveCheckedData(array $rows, int $pkId, string $table, string $fields): int
    {
        $status = 200;

        foreach ($rows as $row) {
            // row expected [isAllowed, fk_id]
            $isAllowed = (int)($row[0] ?? 0);
            $fkId = (int)($row[1] ?? 0);

            if ($isAllowed !== 1) continue;
            if ($fkId <= 0) continue;

            $sql = "INSERT INTO {$table} ({$fields})
                    VALUES (:pk, :fk, " . $this->guidExpr() . ")";

            $ok = $this->exec($sql, [':pk' => $pkId, ':fk' => $fkId]);
            if (!$ok) $status = 100;
        }

        return $status;
    }

    private function getGroups(): array
    {
        return $this->db->select(
            "SELECT id AS group_id, txt_name AS group_name FROM mx_group ORDER BY id",
            []
        );
    }

    private function getPermissions(): array
    {
        $role = (string)($_SESSION['role'] ?? '');
        if (in_array($role, ['3'], true)) {
            $rows = $this->model->db->select(
                "SELECT s.id AS section_id, s.txt_name AS section_name,
                        p.id AS permission_id, p.txt_name AS permission_name,
                        REPLACE(p.txt_name, '_', ' ') AS permission_display_name
                 FROM mx_section s
                 JOIN mx_permission p ON p.opt_mx_section_id = s.id
                 ORDER BY section_id"
            );
        } else {
            // keep original rule, but parameterize
            $loginCredentialId = (string)($_SESSION['id'] ?? '');
            $rows = $this->model->db->select(
                "SELECT s.id AS section_id, s.txt_name AS section_name,
                        p.id AS permission_id, p.txt_name AS permission_name,
                        REPLACE(p.txt_name, '_', ' ') AS permission_display_name
                 FROM mx_permission p
                 JOIN mx_section s ON p.opt_mx_section_id = s.id
                 WHERE p.id IN (
                    SELECT gp.opt_mx_permission_id
                    FROM mx_group_permission gp
                    WHERE gp.opt_mx_group_id IN (
                        SELECT lg.opt_mx_group_id
                        FROM mx_login_credential_group lg
                        WHERE lg.opt_mx_login_credential_id = :lc
                    )
                 )
                 ORDER BY section_id",
                [':lc' => $loginCredentialId]
            );
        }

        $data = [];
        foreach ($rows ?: [] as $row) {
            $row['permission_display_name'] = ucwords((string)$row['permission_display_name']);
            $data[] = $row;
        }
        return $data;
    }

    private function processPermissions(array $given, array $all): array
    {
        $givenNames = [];
        foreach ($given as $g) {
            $givenNames[(string)$g['per_name']] = true;
        }

        $out = [];
        foreach ($all as $p) {
            $p['check'] = isset($givenNames[(string)$p['permission_name']]);
            $out[] = $p;
        }
        return $out;
    }

    private function processGroups(array $userGroups, array $allGroups): array
    {
        $given = [];
        foreach ($userGroups as $g) {
            $given[(string)$g['group_name']] = true;
        }

        $out = [];
        foreach ($allGroups as $g) {
            $g['check'] = isset($given[(string)$g['group_name']]);
            $out[] = $g;
        }
        return $out;
    }
}
