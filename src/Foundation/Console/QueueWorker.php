<?php

namespace Foundation\Console;

use Database\DB;
use Database\Database;
use Logging\Log;
use Exception;

class QueueWorker
{
    public function work(): void
    {
        echo "Starting Oryn Queue Worker...\n";
        echo "Polling mx_job_queue every 3 seconds...\n";

        $db = DB::connection();

        while (true) {
            $driver = $db->getDriverType();
            
            if (in_array($driver, ['sqlsrv', 'odbc', 'dblib'])) {
                $sql = "SELECT TOP 1 id, job_type, payload FROM mx_job_queue WHERE status = 'pending' ORDER BY id ASC";
            } else {
                $sql = "SELECT id, job_type, payload FROM mx_job_queue WHERE status = 'pending' ORDER BY id ASC LIMIT 1";
            }

            $job = $db->select($sql);

            if (!empty($job) && isset($job[0])) {
                $this->processJob($db, $job[0]);
            }

            // Sleep to prevent CPU thrashing
            sleep(3);
        }
    }

    private function processJob(Database $db, array $job): void
    {
        $id = $job['id'];
        echo "[" . date('Y-m-d H:i:s') . "] Processing Job #{$id} ({$job['job_type']})...\n";

        // Lock the job to prevent duplicate processing by other worker instances
        $db->update('mx_job_queue', [
            'status'    => 'processing',
            'locked_at' => date('Y-m-d H:i:s'),
            'attempts'  => 1
        ], $id);

        try {
            $payload = json_decode($job['payload'], true);

            // Dispatch to appropriate handlers
            if ($job['job_type'] === 'sms') {
                if (class_exists('\\Services\\MXSms') && method_exists('\\Services\\MXSms', 'sendTemplateSMS')) {
                    // Example mapping, exact implementation depends on payload structure
                    // \Services\MXSms::sendTemplateSMS($payload['phone'], $payload['token'], $payload['data']);
                }
                // Simulate processing time
                sleep(1);
            } elseif ($job['job_type'] === 'email') {
                // Simulate processing time
                sleep(1);
            }

            // Finalize job
            $db->update('mx_job_queue', [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s')
            ], $id);

            echo "[" . date('Y-m-d H:i:s') . "] Job #{$id} completed successfully.\n";

        } catch (Exception $e) {
            // Register failure state
            $db->update('mx_job_queue', [
                'status'        => 'failed',
                'error_message' => substr($e->getMessage(), 0, 4000)
            ], $id);

            echo "[" . date('Y-m-d H:i:s') . "] Job #{$id} failed: " . $e->getMessage() . "\n";
            Log::sysErr("Job Queue Failed - ID: {$id} - " . $e->getMessage());
        }
    }
}
