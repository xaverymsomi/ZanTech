<?php

namespace Services;

class Hash
{
    /**
     * Create a hashed/salted value using HMAC
     *
     * @param string $algo The algorithm (md5, sha1, whirlpool, etc)
     * @param string $data The data to encode
     * @param string $salt The salt (system-wide or per-user)
     * @param string $appKey An additional secret key for app-level security
     * @return string The hashed/salted data
     */
    public static function create(string $algo, string $data, string $salt): string
    {
        $key = $salt;
        $context = hash_init($algo, HASH_HMAC, $key);
        hash_update($context, $data);
        return hash_final($context);
    }
}
