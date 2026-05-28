<?php

namespace Foundation\Console;

use Throwable;

final class CronRunner
{
    public function run(string $basePath, int $maxJobs = 25, ?callable $tasks = null): int
    {
        if (PHP_SAPI !== 'cli') {
            echo "Cron may only run from CLI.\n";
            return 1;
        }

        $lock = $this->acquireLock($basePath);
        if ($lock === null) {
            echo "Cron already running; skipped.\n";
            return 0;
        }

        $started = microtime(true);
        $_SERVER['ZT_REQUEST_ID'] ??= 'CRON-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));

        echo "[" . date('Y-m-d H:i:s') . "] Cron started ({$_SERVER['ZT_REQUEST_ID']}).\n";

        try {
            $processed = $tasks !== null
                ? (int)$tasks()
                : (new QueueWorker())->work(maxJobs: $maxJobs, sleepSeconds: 0, stopWhenEmpty: true);

            $duration = round((microtime(true) - $started) * 1000, 2);
            echo "[" . date('Y-m-d H:i:s') . "] Cron finished. jobs={$processed} duration_ms={$duration}\n";
            return 0;
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $started) * 1000, 2);
            echo "[" . date('Y-m-d H:i:s') . "] Cron failed after {$duration}ms: {$e->getMessage()}\n";
            return 1;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function acquireLock(string $basePath)
    {
        $dir = rtrim($basePath, '/\\') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cron';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $lock = fopen($dir . DIRECTORY_SEPARATOR . 'oryn-cron.lock', 'c+');
        if ($lock === false) {
            throw new \RuntimeException('Unable to open cron lock file.');
        }

        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return null;
        }

        ftruncate($lock, 0);
        fwrite($lock, (string)getmypid());

        return $lock;
    }
}
