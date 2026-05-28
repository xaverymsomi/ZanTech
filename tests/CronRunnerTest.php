<?php

use Foundation\Console\CronRunner;
use PHPUnit\Framework\TestCase;

final class CronRunnerTest extends TestCase
{
    private string $workDir;

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'oryn-cron-' . bin2hex(random_bytes(6));
        mkdir($this->workDir . DIRECTORY_SEPARATOR . 'storage', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workDir);
    }

    public function testRunCreatesLockAndReturnsSuccess(): void
    {
        ob_start();
        $code = (new CronRunner())->run($this->workDir, tasks: static fn (): int => 3);
        $output = ob_get_clean();

        $this->assertSame(0, $code);
        $this->assertStringContainsString('Cron started', (string)$output);
        $this->assertStringContainsString('jobs=3', (string)$output);
        $this->assertFileExists($this->workDir . DIRECTORY_SEPARATOR . 'storage/cron/oryn-cron.lock');
    }

    public function testRunReturnsFailureWhenTaskThrows(): void
    {
        ob_start();
        $code = (new CronRunner())->run($this->workDir, tasks: static function (): int {
            throw new RuntimeException('broken task');
        });
        $output = ob_get_clean();

        $this->assertSame(1, $code);
        $this->assertStringContainsString('Cron failed', (string)$output);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
