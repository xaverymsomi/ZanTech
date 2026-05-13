<?php

declare(strict_types=1);

namespace Library\Support;

final class Config
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        // 1. Check local cache
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // 2. Check Environment
        $envValue = $_ENV[$key] ?? getenv($key);
        if ($envValue !== false && $envValue !== null) {
            return self::$cache[$key] = self::normalizeValue($envValue);
        }

        // 3. Check Database
        try {
            $db = \Database\DB::connection();
            $result = $db->select("SELECT txt_value FROM mx_setting WHERE txt_key = :key", [':key' => $key]);
            if (!empty($result)) {
                return self::$cache[$key] = self::normalizeValue($result[0]['txt_value']);
            }
        } catch (\Exception $e) {
            // Silently fail if DB not ready
        }

        return $default;
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if (!is_string($value)) return $value;
        $lower = strtolower($value);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if ($lower === 'null') return null;
        if (is_numeric($value)) return strpos($value, '.') !== false ? (float)$value : (int)$value;
        return $value;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$cache[$key] = $value;
        // In a real app, this might persist to DB or session
    }
}
