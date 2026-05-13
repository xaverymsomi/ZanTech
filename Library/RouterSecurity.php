<?php

declare(strict_types=1);

namespace Library;

use Exceptions\RouterException;
use ReflectionMethod;

class RouterSecurity
{
    // Allow: a-z, 0-9, underscore, dash. (dash supported for nicer URLs)
    private const SLUG_PATTERN = '/^[a-z0-9][a-z0-9_-]*$/';

    // Allow method: letters/numbers/underscore, but forbid magic methods
    private const METHOD_PATTERN = '/^[A-Za-z][A-Za-z0-9_]*$/';

    private function __construct() {}

    /**
     * Normalize raw URL into safe segments.
     * - Extract path only
     * - Decode once
     * - Normalize slashes
     * - Remove dot segments
     * - Enforce limits
     *
     * @return string[]
     */
    public static function parseSegmentsFromRequest(): array
    {
        $raw = (string)($_GET['url'] ?? ($_SERVER['PATH_INFO'] ?? '') ?? ($_SERVER['REQUEST_URI'] ?? '/'));
        
        // Use unified foundation normalization
        $path = zt_normalize_path($raw);

        $stack = array_filter(explode('/', trim($path, '/')));
        
        if (count($stack) > ZT_MAX_SEGMENTS) {
            throw new RouterException('Too many path segments', 'Resource Not Found', 404);
        }

        foreach ($stack as $seg) {
            if (strlen($seg) > ZT_MAX_SEGMENT_LENGTH) {
                throw new RouterException('Path segment too long', 'Resource Not Found', 404);
            }
        }

        return array_values($stack);
    }

    public static function isStaticRequest(string $rawUri): bool
    {
        $uri = strtolower($rawUri);
        $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

        return in_array($ext, [
            'png','jpg','jpeg','gif','svg','css','js','map','ico','webp',
            'mp3','wav','ogg','woff','woff2','ttf','eot'
        ], true);
    }

    public static function validateControllerSlug(string $slug): void
    {
        if ($slug === '' || !preg_match(self::SLUG_PATTERN, $slug)) {
            throw new RouterException("Invalid controller slug: {$slug}", 'Resource Not Found', 404);
        }
    }

    public static function validateMethodName(string $method): void
    {
        if (!preg_match(self::METHOD_PATTERN, $method)) {
            throw new RouterException("Invalid method name: {$method}", 'Resource Not Found', 404);
        }

        // Block magic methods + internal-looking calls
        if (str_starts_with($method, '__')) {
            throw new RouterException('Forbidden method', 'Resource Not Found', 404);
        }
    }

    /**
     * Convert "my-module_name" => "MyModuleName"
     */
    public static function slugToStudly(string $slug): string
    {
        $slug = strtolower($slug);
        $slug = str_replace(['-', '_'], ' ', $slug);
        $slug = ucwords($slug);
        return str_replace(' ', '', $slug);
    }

    /**
     * Convert "my-method_name" => "myMethodName"
     */
    public static function slugToCamel(string $slug): string
    {
        $studly = self::slugToStudly($slug);
        return lcfirst($studly);
    }

    /**
     * Validate params are strings and not massive.
     *
     * @param array $params
     * @return array
     */
    public static function sanitizeParams(array $params): array
    {
        $clean = [];
        foreach ($params as $p) {
            $s = (string)$p;
            if (strlen($s) > ZT_MAX_PARAM_LENGTH) {
                throw new RouterException('Parameter too long', 'Resource Not Found', 404);
            }
            $clean[] = $s;
        }
        return $clean;
    }

    /**
     * Ensure a method is public and callable, and optionally deny sensitive base methods.
     */
    public static function assertPublicCallable(object $controller, string $method): void
    {
        if (!method_exists($controller, $method)) {
            throw new RouterException("Method not found: {$method}", 'Resource Not Found', 404);
        }

        $rm = new ReflectionMethod($controller, $method);
        if (!$rm->isPublic()) {
            throw new RouterException("Method not accessible: {$method}", 'Resource Not Found', 404);
        }

        // Optional denylist (protect base/controller utilities if they are public)
        $deny = [
            'init','boot','shutdown',
            'render','view','template','layout',
            'db','database','query','select','insert','update','delete',
            'config','env','debug'
        ];

        $m = strtolower($method);
        if (in_array($m, $deny, true)) {
            throw new RouterException("Forbidden method: {$method}", 'Resource Not Found', 404);
        }

    }

    /**
     * Recursively redact sensitive keys and cap large values.
     */
    public static function redactSensitive(array $data): array
    {

        $walk = function ($value, $key = null) use (&$walk) {
            $maxString = 500;
            $containsBlocked = ['token', 'secret', 'auth', 'bearer', 'key'];
            $exactBlocked = ['password', 'pass', 'pwd', 'csrf', '_token', 'authorization', 'api_key', 'app_key'];
            if (is_array($value)) {
                $out = [];
                foreach ($value as $k => $v) {
                    $out[$k] = $walk($v, (string)$k);
                }
                return $out;
            }

            $k = strtolower((string)$key);
            if ($k !== '') {
                if (in_array($k, $exactBlocked, true)) return '***';

                foreach ($containsBlocked as $needle) {
                    if (str_contains($k, $needle)) return '***';
                }
            }

            if (is_string($value)) {
                if (strlen($value) > $maxString) return substr($value, 0, $maxString) . '...<truncated>';
                return $value;
            }

            if (is_object($value)) return '<object>';
            if (is_resource($value)) return '<resource>';

            return $value;
        };

        return $walk($data);
    }
}
