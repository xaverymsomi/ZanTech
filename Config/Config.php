<?php

declare(strict_types=1);

namespace Config;

use Throwable;

final class Config
{
    private static array $items = [];
    private static array $cache = [];

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

        try {
            $db = \Database\DB::connection();
            foreach (self::envKeys($key) as $lookupKey) {
                $result = $db->select(
                    'SELECT txt_value FROM mx_setting WHERE txt_key = :key',
                    [':key' => $lookupKey]
                );

                if (!empty($result)) {
                    return self::$cache[$key] = self::normalizeValue($result[0]['txt_value']);
                }
            }
        } catch (Throwable) {
            // DB-backed config is optional during bootstrap and tests.
        }

        return self::$cache[$key] = $default;
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
