<?php

namespace Services;

final class MXPhoneNumber
{
    public static function normalizeTz(mixed $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if (preg_match('/^0(6|7)\d{8}$/', $digits)) {
            return '255' . substr($digits, 1);
        }

        if (preg_match('/^255(6|7)\d{8}$/', $digits)) {
            return $digits;
        }

        if (preg_match('/^(6|7)\d{8}$/', $digits)) {
            return '255' . $digits;
        }

        return null;
    }
}
