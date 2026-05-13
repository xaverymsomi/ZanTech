<?php
/**
 * Pure helpers for web bootstrap. Safe to include in PHPUnit without booting the app.
 */

if (!function_exists('zt_env_bool')) {
    function zt_env_bool(string $key, bool $default = false): bool
    {
        $val = $_ENV[$key] ?? getenv($key);
        if ($val === false || $val === null) return $default;
        if (is_bool($val)) return $val;

        $s = strtolower(trim((string)$val));
        return in_array($s, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('zt_is_https')) {
    function zt_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }

        // Trust forwarded proto only from trusted proxies
        $remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $trustedProxies = array_filter(array_map('trim', explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? ''))));

        $ipInCidr = static function (string $ip, string $cidr): bool {
            if ($cidr === '') return false;

            if (strpos($cidr, '/') === false) {
                return hash_equals($ip, $cidr);
            }

            [$subnet, $maskBits] = explode('/', $cidr, 2);
            $maskBits = (int)$maskBits;

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);

            if ($ipLong === false || $subnetLong === false) return false;
            if ($maskBits < 0 || $maskBits > 32) return false;

            $mask = $maskBits === 0 ? 0 : (-1 << (32 - $maskBits));
            return (($ipLong & $mask) === ($subnetLong & $mask));
        };

        $isFromTrustedProxy = false;
        foreach ($trustedProxies as $proxy) {
            if ($ipInCidr($remoteAddr, $proxy)) {
                $isFromTrustedProxy = true;
                break;
            }
        }

        if ($isFromTrustedProxy) {
            $xfp = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
            return $xfp === 'https';
        }

        return false;
    }
}
