<?php

namespace Config;

use Throwable;

final class Config
{
    private static array $items = [];
    private static array $cache = [];
    private static bool $dbSettingsLoaded = false;

    public static function load(array $items): void
    {
        self::$items = array_replace_recursive(self::$items, $items);
        self::$cache = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        if (self::hasNested($key)) {
            return self::$cache[$key] = self::nested($key);
        }

        foreach (self::envKeys($key) as $envKey) {
            $envValue = $_ENV[$envKey] ?? getenv($envKey);
            if ($envValue !== false && $envValue !== null) {
                return self::$cache[$key] = self::normalizeValue($envValue);
            }
        }

        // Lazy-load database configurations in a single batch query if not already done
        if (!self::$dbSettingsLoaded) {
            self::loadDbSettings();
        }

        // Check if loading the DB settings populated the cache
        if (array_key_exists($key, self::$cache)) {
            return self::$cache[$key];
        }

        // Also check with uppercase underscore key variation in cache
        foreach (self::envKeys($key) as $lookupKey) {
            if (array_key_exists($lookupKey, self::$cache)) {
                return self::$cache[$key] = self::$cache[$lookupKey];
            }
        }

        return self::$cache[$key] = $default;
    }

    /**
     * Load all database settings in a single efficient query.
     */
    private static function loadDbSettings(): void
    {
        self::$dbSettingsLoaded = true;
        try {
            $db = \Database\DB::connection();
            $results = $db->select('SELECT txt_key, txt_value FROM mx_setting');
            if (is_array($results)) {
                foreach ($results as $row) {
                    if (isset($row['txt_key'])) {
                        $k = $row['txt_key'];
                        $v = $row['txt_value'] ?? null;
                        self::$cache[$k] = self::normalizeValue($v);
                        
                        // Register alternative env-style uppercase keys
                        $upperKey = strtoupper(str_replace('.', '_', $k));
                        self::$cache[$upperKey] = self::normalizeValue($v);
                    }
                }
            }
        } catch (Throwable) {
            // DB configurations are optional during early bootstrapping or testing.
        }
    }

    public static function set(string $key, mixed $value): void
    {
        self::setNested($key, $value);
        self::$cache[$key] = $value;
    }

    public static function clear(): void
    {
        self::$items = [];
        self::$cache = [];
        self::$dbSettingsLoaded = false;
    }

    private static function hasNested(string $key): bool
    {
        $cursor = self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($cursor) || !array_key_exists($segment, $cursor)) {
                return false;
            }

            $cursor = $cursor[$segment];
        }

        return true;
    }

    private static function nested(string $key): mixed
    {
        $cursor = self::$items;
        foreach (explode('.', $key) as $segment) {
            $cursor = $cursor[$segment];
        }

        return $cursor;
    }

    private static function setNested(string $key, mixed $value): void
    {
        $cursor = &self::$items;
        foreach (explode('.', $key) as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }

        $cursor = $value;
    }

    private static function envKeys(string $key): array
    {
        $envKey = strtoupper(str_replace('.', '_', $key));
        return array_values(array_unique([$key, $envKey]));
    }

    private static function normalizeValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $lower = strtolower($value);
        if (in_array($lower, ['true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($lower, ['false', 'no', 'off'], true)) {
            return false;
        }
        if ($lower === 'null') {
            return null;
        }
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }

        return $value;
    }
}
