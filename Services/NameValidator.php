<?php

namespace Services;

final class NameValidator
{
    public static function isValid(string $name): bool
    {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 120) {
            return false;
        }

        return (bool) preg_match("/^[\\p{L}\\p{M}' .-]+$/u", $name);
    }
}
