<?php

namespace Services;

/**
 * Modern Cryptography Service for Zantech Framework.
 * Transitioning from HMAC-SHA256 to secure password_hash (Bcrypt/Argon2id).
 */
class Hash
{
    /**
     * Create a secure hash for a password or sensitive string.
     * Uses PHP's default secure algorithm (currently Bcrypt).
     */
    public static function make(string $data): string
    {
        return password_hash($data, PASSWORD_DEFAULT);
    }

    /**
     * Verify a password against a hash.
     * Supports both modern hashes and legacy HMAC-SHA256 for transition.
     */
    public static function check(string $data, string $hash): bool
    {
        // 1. Try modern password_verify
        if (password_verify($data, $hash)) {
            return true;
        }

        // 2. Fallback to legacy HMAC-SHA256 (for transition period)
        if (defined('PASS_SALT') && defined('HASH_ALGO')) {
            $legacy = self::create(HASH_ALGO, $data, PASS_SALT);
            return hash_equals($legacy, $hash);
        }

        return false;
    }

    /**
     * Determine if a hash needs to be updated to a stronger algorithm.
     */
    public static function needsRehash(string $hash): bool
    {
        // If it doesn't look like a modern password_hash, it definitely needs a rehash
        if (!str_starts_with($hash, '$')) {
            return true;
        }

        return password_needs_rehash($hash, PASSWORD_DEFAULT);
    }

    /**
     * Create a hashed/salted value using HMAC (Legacy Support).
     *
     * @deprecated Use make() for passwords.
     * @param string $algo The algorithm
     * @param string $data The data to encode
     * @param string $salt The salt
     * @return string The hashed/salted data
     */
    public static function create(string $algo, string $data, string $salt): string
    {
        $context = hash_init($algo, HASH_HMAC, $salt);
        hash_update($context, $data);
        return hash_final($context);
    }
}
