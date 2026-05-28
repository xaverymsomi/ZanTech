<?php

namespace Foundation\Console;

use Database\DB;
use Database\Database;
use Logging\Log;
use Throwable;

class QueueWorker
{
    private const DEFAULT_SLEEP_SECONDS = 3;

    public function work(int $maxJobs = 0, int $sleepSeconds = self::DEFAULT_SLEEP_SECONDS, bool $stopWhenEmpty = false): int
    {
        echo "Starting Oryn Queue Worker...\n";
        echo $maxJobs > 0
            ? "Processing up to {$maxJobs} job(s).\n"
            : "Polling mx_job_queue every {$sleepSeconds} seconds.\n";

        $db = DB::connection();
        $processed = 0;

        while (true) {
            $didWork = $this->runOnce($db);
            if ($didWork) {
                $processed++;
            }

            if ($maxJobs > 0 && $processed >= $maxJobs) {
                break;
            }

            if (!$didWork && $stopWhenEmpty) {
                break;
            }

            if (!$didWork && $sleepSeconds > 0) {
                sleep($sleepSeconds);
            }
        }

        return $processed;
    }

    public function runOnce(?Database $db = null): bool
    {
        $db ??= DB::connection();
        $job = $this->nextPendingJob($db);
        if ($job === null) {
            return false;
        }

        if (!$this->claimJob($db, (int)$job['id'])) {
            return false;
        }

        $this->processJob($db, $job);
        return true;
    }

    private function nextPendingJob(Database $db): ?array
    {
        $driver = $db->getDriverType();

        if (in_array($driver, ['sqlsrv', 'odbc', 'dblib'], true)) {
            $sql = "SELECT TOP 1 id, job_type, payload, attempts
                    FROM mx_job_queue
                    WHERE status = 'pending'
                    ORDER BY id ASC";
        } else {
            $sql = "SELECT id, job_type, payload, attempts
                    FROM mx_job_queue
                    WHERE status = 'pending'
                    ORDER BY id ASC
                    LIMIT 1";
        }

        $rows = $db->select($sql);
        return $rows[0] ?? null;
    }

    private function claimJob(Database $db, int $id): bool
    {
        $stmt = $db->prepare("
            UPDATE mx_job_queue
            SET status = :status,
                locked_at = :locked_at,
                attempts = attempts + 1
            WHERE id = :id
              AND status = 'pending'
        ");

        $stmt->execute([
            ':status' => 'processing',
            ':locked_at' => date('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        return $stmt->rowCount() === 1;
    }

    private function processJob(Database $db, array $job): void
    {
        $id = (int)$job['id'];
        echo "[" . date('Y-m-d H:i:s') . "] Processing Job #{$id} ({$job['job_type']}).\n";

        try {
            $payload = json_decode((string)$job['payload'], true);
            if (!is_array($payload)) {
                throw new \RuntimeException('Invalid job payload JSON.');
            }

            $this->dispatch($job['job_type'], $payload);

            $db->update('mx_job_queue', [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'error_message' => null,
            ], $id);

            echo "[" . date('Y-m-d H:i:s') . "] Job #{$id} completed.\n";
        } catch (Throwable $e) {
            $db->update('mx_job_queue', [
                'status'        => 'failed',
                'error_message' => substr($e->getMessage(), 0, 4000),
            ], $id);

            echo "[" . date('Y-m-d H:i:s') . "] Job #{$id} failed: {$e->getMessage()}\n";
            Log::sysErr("Job Queue Failed - ID: {$id} - " . $e->getMessage());
        }
    }

    private function dispatch(string $type, array $payload): void
    {
        if ($type === 'sms') {
            return;
        }

        if ($type === 'email') {
            return;
        }

        throw new \RuntimeException("Unknown job type: {$type}");
    }
}
