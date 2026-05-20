<?php

namespace Services;

final class LogSanitizer
{
    public static function maskEmail(string $email): string
    {
        $email = trim($email);
        if (!str_contains($email, '@')) {
            return self::sanitizeString($email);
        }

        [$local, $domain] = explode('@', $email, 2);
        $prefix = substr($local, 0, 2);

        return $prefix . str_repeat('*', max(1, strlen($local) - 2)) . '@' . $domain;
    }

    public static function sanitizeString(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';

        return trim($value);
    }
}
