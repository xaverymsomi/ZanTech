<?php

declare(strict_types=1);

/**
 * Pure functions extracted from AppLoader.php so PHPUnit can load them
 * without booting the application.
 */

if (!function_exists('zt_normalize_path')) {
    function zt_normalize_path(string $rawUri): string
    {
        $rawUri = trim($rawUri);
        if ($rawUri === '') return '/';

        // Basic DoS guard (URLs should never be huge)
        if (strlen($rawUri) > 4096) {
            return '/';
        }

        // Strip query string + fragment (even if rawUri is already a path)
        $qPos = strpos($rawUri, '?');
        if ($qPos !== false) {
            $rawUri = substr($rawUri, 0, $qPos);
        }
        $hashPos = strpos($rawUri, '#');
        if ($hashPos !== false) {
            $rawUri = substr($rawUri, 0, $hashPos);
        }

        // If it looks like a raw path, don't let parse_url treat "////" as scheme-relative URL
        if ($rawUri !== '' && ($rawUri[0] === '/' || $rawUri[0] === '\\')) {
            $path = $rawUri;
        } else {
            $path = (string)(parse_url($rawUri, PHP_URL_PATH) ?? '/');
            if ($path === '') $path = '/';
        }

        // Strip null bytes + control chars
        $path = str_replace("\0", '', $path);
        $path = preg_replace('/[\x00-\x1F\x7F]/', '', $path) ?? $path;

        // Normalize Windows backslashes to URL slashes
        $path = str_replace('\\', '/', $path);

        // Decode ONCE
        $path = rawurldecode($path);

        // Collapse slashes and ensure leading slash
        $path = '/' . ltrim((preg_replace('#/+#', '/', $path) ?? '/'), '/');

        // Remove dot segments
        $segments = explode('/', trim($path, '/'));
        $stack = [];
        foreach ($segments as $seg) {
            if ($seg === '' || $seg === '.') continue;
            if ($seg === '..') { array_pop($stack); continue; }
            $stack[] = $seg;
        }

        return '/' . implode('/', $stack);
    }
}

if (!function_exists('zt_detect_namespace')) {
    function zt_detect_namespace(string $normalizedPath): string
    {
        $trimmed = trim($normalizedPath, '/');
        if ($trimmed === '') return 'web';

        $first = strtolower(explode('/', $trimmed, 2)[0]);

        return match ($first) {
            'api'     => 'api',
            'cronjob' => 'cronjob',
            default   => 'web',
        };
    }
}

if (!function_exists('zt_is_forbidden_boot_probe')) {
    function zt_is_forbidden_boot_probe(string $normalizedPath): bool
    {
        $leaf = ltrim($normalizedPath, '/');
        return in_array($leaf, ['web.php', 'api.php', 'cronjob.php'], true);
    }
}

if (!function_exists('zt_resolve_entry_file')) {
    function zt_resolve_entry_file(string $foundationDir, string $namespace): string
    {
        if (!in_array($namespace, ['web', 'api', 'cronjob'], true)) {
            throw new RuntimeException('Invalid namespace.');
        }

        $entryFile = rtrim($foundationDir, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . $namespace
            . '.php';

        $realEntry = realpath($entryFile);
        if ($realEntry === false) {
            throw new RuntimeException('Entry file not found.');
        }

        $realFoundation = realpath($foundationDir);
        if ($realFoundation === false) {
            throw new RuntimeException('Foundation directory invalid.');
        }

        $prefix = rtrim($realFoundation, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncmp($realEntry, $prefix, strlen($prefix)) !== 0) {
            throw new RuntimeException('Resolved entry is outside foundation directory.');
        }

        if (!is_readable($realEntry)) {
            throw new RuntimeException('Entry file not readable.');
        }

        return $realEntry;
    }
}
