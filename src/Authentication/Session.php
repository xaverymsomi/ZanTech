<?php

namespace Authentication;

final class Session
{
    protected static bool $initialized = false;

    public static function init(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_DISABLED) {
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            self::$initialized = true;
            self::autoRegenerate();
            return;
        }

        if (headers_sent()) {
            self::$initialized = false;
            return;
        }

        @ini_set('session.use_strict_mode', '1');
        @ini_set('session.use_only_cookies', '1');
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.sid_length', '64');
        @ini_set('session.sid_bits_per_character', '6');
        @ini_set('session.use_trans_sid', '0');

        $isHttps = self::isHttpsProxySafe();

        $cookiePath   = (string)($_ENV['SESSION_COOKIE_PATH'] ?? '/');
        $cookieDomain = (string)($_ENV['SESSION_COOKIE_DOMAIN'] ?? '');
        $sameSite     = self::normalizeSameSite((string)($_ENV['SESSION_SAMESITE'] ?? 'Lax'));

        // SameSite=None requires Secure=true in modern browsers
        if ($sameSite === 'None' && !$isHttps) {
            // Force safe default to avoid broken cookies on HTTP
            $sameSite = 'Lax';
        }

        $secure = ($sameSite === 'None') ? true : $isHttps;

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $cookiePath !== '' ? $cookiePath : '/',
            'domain'   => $cookieDomain,
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ]);

        session_start();

        if (!isset($_SESSION[ZT_SESS_INIT_KEY])) {
            session_regenerate_id(true);
            $_SESSION[ZT_SESS_INIT_KEY] = time();
        }

        self::$initialized = true;
        self::autoRegenerate();
    }

    public static function set(string $key, mixed $value): void
    {
        self::init();
        if (session_status() !== PHP_SESSION_ACTIVE) return;
        $_SESSION[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        self::init();
        if (session_status() !== PHP_SESSION_ACTIVE) return null;
        return $_SESSION[$key] ?? null;
    }

    public static function regenerate(): void
    {
        self::init();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
            $_SESSION[ZT_SESS_INIT_KEY] = time();
        }
    }

    public static function destroy(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_DISABLED) {
            return;
        }

        // If session isn't active, only start it when a session cookie exists.
        // This avoids creating new sessions during logout.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (!headers_sent()) {
                $sessionCookieName = session_name();
                $hasSessionCookie = ($sessionCookieName !== '') && isset($_COOKIE[$sessionCookieName]);

                if ($hasSessionCookie) {
                    @session_start();
                }
            }
        }

        // Clear data
        if (isset($_SESSION) && is_array($_SESSION)) {
            $_SESSION = [];
        }

        // Expire session cookie
        if (!headers_sent() && ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            $isHttps = self::isHttpsProxySafe();
            $sameSite = self::normalizeSameSite((string)($_ENV['SESSION_SAMESITE'] ?? ($params['samesite'] ?? 'Lax')));

            if ($sameSite === 'None' && !$isHttps) {
                $sameSite = 'Lax';
            }

            $secure = ($sameSite === 'None') ? true : $isHttps;

            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 3600,
                    'path'     => (string)($params['path'] ?? '/'),
                    'domain'   => (string)($params['domain'] ?? ''),
                    'secure'   => $secure,
                    'httponly' => (bool)($params['httponly'] ?? true),
                    'samesite' => $sameSite,
                ]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_destroy();
            @session_write_close();
        }

        self::$initialized = false;
    }

    public static function csrfToken(): string
    {
        self::init();
        if (session_status() !== PHP_SESSION_ACTIVE) return '';

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function refreshCsrfToken(): string
    {
        self::init();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return self::csrfToken();
    }

    private static function autoRegenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) return;

        if (!isset($_SESSION[ZT_SESS_INIT_KEY])) {
            $_SESSION[ZT_SESS_INIT_KEY] = time();
            return;
        }

        $created = (int)$_SESSION[ZT_SESS_INIT_KEY];

        if ((time() - $created) > ZT_SESS_REGEN_TIME) {
            session_regenerate_id(true);
            $_SESSION[ZT_SESS_INIT_KEY] = time();
        }
    }


    private static function normalizeSameSite(string $value): string
    {
        $v = strtolower(trim($value));
        return match ($v) {
            'strict' => 'Strict',
            'none'   => 'None',
            default  => 'Lax',
        };
    }

    /**
     * Proxy-safe HTTPS detection: only trust X-Forwarded-Proto from trusted proxies.
     */
    private static function isHttpsProxySafe(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) return true;

        $remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $trusted = array_filter(array_map('trim', explode(',', (string)($_ENV['TRUSTED_PROXIES'] ?? ''))));
        if ($remoteAddr === '' || empty($trusted)) return false;

        $ipInCidr = static function (string $ip, string $cidr): bool {
            if ($cidr === '') return false;
            if (!str_contains($cidr, '/')) return hash_equals($ip, $cidr);

            [$subnet, $maskBits] = explode('/', $cidr, 2);
            $maskBits = (int)$maskBits;

            $ipLong = ip2long($ip);
            $subnetLong = ip2long($subnet);
            if ($ipLong === false || $subnetLong === false) return false;
            if ($maskBits < 0 || $maskBits > 32) return false;

            $mask = $maskBits === 0 ? 0 : (-1 << (32 - $maskBits));
            return (($ipLong & $mask) === ($subnetLong & $mask));
        };

        $isTrustedProxy = false;
        foreach ($trusted as $cidr) {
            if ($ipInCidr($remoteAddr, $cidr)) {
                $isTrustedProxy = true;
                break;
            }
        }

        if (!$isTrustedProxy) return false;

        return strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}
