<?php

namespace Foundation\Middleware;

use Closure;
use Database\Database;
use Foundation\Routing\RouteContext;
use Exceptions\ZantechException;
use Logging\Log;

/**
 * Provides database-backed authentication throttling to prevent brute-force attacks.
 */
class AuthThrottlingMiddleware implements Middleware
{
    private const MAX_ATTEMPTS = 5;
    private const BLOCK_MINUTES = 15;

    public function handle(RouteContext $route, Closure $next): mixed
    {
        $controller = strtolower($route->controller());
        $method = strtolower($route->method());

        // Only throttle sensitive authentication routes
        if ($controller !== 'login' || !in_array($method, ['login', 'recover', 'reset'])) {
            return $next($route);
        }

        $ip = $route->request->ip();
        $username = $_POST['txt_username'] ?? $_POST['email'] ?? null;

        $this->checkThrottle($ip, $username);

        return $next($route);
    }

    /**
     * Check if the current IP or Username is blocked.
     */
    private function checkThrottle(string $ip, ?string $username): void
    {
        $db = new Database();
        $now = date('Y-m-d H:i:s');

        $sql = "SELECT MAX(dat_blocked_until) as blocked_until 
                FROM mx_auth_throttle 
                WHERE (txt_ip_address = :ip OR (txt_username = :user AND txt_username IS NOT NULL))
                AND dat_blocked_until > :now";

        $result = $db->select($sql, [
            ':ip'   => $ip,
            ':user' => $username,
            ':now'  => $now
        ]);

        if (!empty($result[0]['blocked_until'])) {
            $blockedUntil = $result[0]['blocked_until'];
            Log::sysLog("AUTH-THROTTLE: Blocked request from IP: {$ip}, User: {$username} until {$blockedUntil}");
            
            throw new ZantechException(
                "Too many failed attempts. Access is restricted until {$blockedUntil}.",
                "Security Block Active",
                429
            );
        }
    }

    /**
     * Record a failed attempt in the database.
     */
    public static function recordFailure(string $ip, ?string $username): void
    {
        $db = new Database();
        $now = date('Y-m-d H:i:s');
        
        // 1. Check if record exists
        $sql = "SELECT id, int_attempts FROM mx_auth_throttle WHERE txt_ip_address = :ip AND (txt_username = :user OR txt_username IS NULL)";
        $existing = $db->select($sql, [':ip' => $ip, ':user' => $username]);

        if ($existing) {
            $id = $existing[0]['id'];
            $attempts = (int)$existing[0]['int_attempts'] + 1;
            $blockedUntil = null;

            if ($attempts >= self::MAX_ATTEMPTS) {
                $blockedUntil = date('Y-m-d H:i:s', strtotime("+ " . self::BLOCK_MINUTES . " minutes"));
            }

            $db->update('mx_auth_throttle', [
                'int_attempts' => $attempts,
                'dat_last_attempt' => $now,
                'dat_blocked_until' => $blockedUntil
            ], $id, 'id');
        } else {
            $db->create([
                'txt_ip_address' => $ip,
                'txt_username' => $username,
                'int_attempts' => 1,
                'dat_last_attempt' => $now
            ], 'mx_auth_throttle');
        }
    }

    /**
     * Clear all throttle records for a successful login.
     */
    public static function clearThrottle(string $ip, ?string $username): void
    {
        $db = new Database();
        $sql = "DELETE FROM mx_auth_throttle WHERE txt_ip_address = :ip OR (txt_username = :user AND txt_username IS NOT NULL)";
        $db->prepare($sql)->execute([':ip' => $ip, ':user' => $username]);
    }
}
