<?php

use Logging\Log;
use PHPUnit\Framework\TestCase;

final class LogTest extends TestCase
{
    public function testExceptionLoggingCreatesStructuredLogEntry(): void
    {
        $exception = new RuntimeException('Test exception message');

        Log::exception($exception, 'TEST_EXCEPTION');

        $logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'Oryn-test-logs' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'sys';
        $files = glob($logDir . DIRECTORY_SEPARATOR . '*.log');
        $this->assertNotEmpty($files, 'Expected at least one sys log file to exist.');

        $found = false;
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if (str_contains($contents, 'TEST_EXCEPTION')) {
                $found = true;
                $this->assertStringContainsString('RuntimeException', $contents);
                $this->assertStringContainsString('Test exception message', $contents);
                $this->assertStringContainsString('trace', $contents);
                break;
            }
        }

        $this->assertTrue($found, 'Expected structured exception log entry to be written.');
    }
}
