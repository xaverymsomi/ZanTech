<?php

namespace Services;

final class EmailValidator
{
    public static function isValid(string $email, bool $checkMx = false): bool
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (!$checkMx) {
            return true;
        }

        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain === '') {
            return false;
        }

        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }
}
