<?php

namespace Foundation\Console;

final class Doctor
{
    public function check(string $basePath): int
    {
        $checks = [
            $this->checkPhpVersion(),
            $this->checkExtensions(['pdo', 'json', 'fileinfo', 'curl', 'mbstring', 'openssl']),
            $this->checkFile($basePath . '/vendor/autoload.php', 'Composer autoload'),
            $this->checkFile($basePath . '/.env', 'Environment file', false),
            $this->checkFile($basePath . '/public/index.php', 'Public front controller'),
            $this->checkDirectory($basePath . '/storage', 'Storage directory', true),
            $this->checkDirectory($basePath . '/src/Database/migrations', 'Migrations directory', false),
        ];

        $exitCode = 0;
        echo "Oryn doctor\n";
        echo "--------------\n";

        foreach ($checks as $check) {
            [$ok, $label, $detail, $required] = $check;
            $marker = $ok ? 'OK' : ($required ? 'FAIL' : 'WARN');
            echo "[{$marker}] {$label}";
            if ($detail !== '') {
                echo " - {$detail}";
            }
            echo "\n";

            if (!$ok && $required) {
                $exitCode = 1;
            }
        }

        return $exitCode;
    }

    private function checkPhpVersion(): array
    {
        $ok = version_compare(PHP_VERSION, '8.2.0', '>=');
        return [$ok, 'PHP version', PHP_VERSION . ' (requires >= 8.2)', true];
    }

    private function checkExtensions(array $extensions): array
    {
        $missing = [];
        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        return [
            $missing === [],
            'Required PHP extensions',
            $missing === [] ? implode(', ', $extensions) : 'missing: ' . implode(', ', $missing),
            true,
        ];
    }

    private function checkFile(string $path, string $label, bool $required = true): array
    {
        return [is_file($path), $label, $path, $required];
    }

    private function checkDirectory(string $path, string $label, bool $writable): array
    {
        $ok = is_dir($path) && (!$writable || is_writable($path));
        $detail = $path . ($writable ? ' (writable)' : '');

        return [$ok, $label, $detail, true];
    }
}
