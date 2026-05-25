<?php

namespace Foundation\Middleware;

use Authentication\Auth;
use Authentication\Session;
use Foundation\Routing\RouteContext;
use Services\AuditTrail;
use Closure;

/**
 * SessionSecurityMiddleware
 * 
 * Automatically detects and terminates sessions for unusual activity:
 * - Inactivity (timeout)
 * - IP Address change during session
 * - User Agent change during session
 */
final class SessionSecurityMiddleware implements Middleware
{
    private const INACTIVITY_TIMEOUT = 1800; // 30 minutes

    public function handle(RouteContext $route, Closure $next): mixed
    {
        if (Auth::isLogged()) {
            $this->validateSession($route);
        }

        return $next($route);
    }

    /**
     * Validate the session against security fingerprints and inactivity.
     */
    private function validateSession(RouteContext $route): void
    {
        $now = time();
        $lastActivity = Session::get('zt_last_activity');
        $storedIp = Session::get('zt_auth_ip');
        $storedUa = Session::get('zt_auth_ua');

        $currentIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $reason = null;

        // 1. Check Inactivity
        if ($lastActivity && ($now - $lastActivity) > self::INACTIVITY_TIMEOUT) {
            $reason = "INACTIVITY_TIMEOUT";
        } 
        // 2. Check IP Mismatch
        elseif ($storedIp && $storedIp !== $currentIp) {
            $reason = "SESSION_IP_MISMATCH";
        }
        // 3. Check User Agent Mismatch
        elseif ($storedUa && $storedUa !== $currentUa) {
            $reason = "SESSION_UA_MISMATCH";
        }

        if ($reason) {
            $this->terminateSession($reason);
        }

        // Update activity timestamp for valid requests
        Session::set('zt_last_activity', $now);
    }

    /**
     * Kill the session and log the security event.
     */
    private function terminateSession(string $reason): void
    {
        $user = Auth::user();
        $username = $user['txt_username'] ?? 'UNKNOWN';

        AuditTrail::log(
            'SECURITY_FORCED_LOGOUT', 
            "Reason: {$reason} | User: {$username}",
            [
                'reason' => $reason,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]
        );

        Auth::logout();
        
        // For AJAX requests, return a JSON error
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['status' => false, 'message' => 'Session terminated due to security policy.']);
            exit;
        }

        // For web requests, redirect to login with a message
        header('Location: ' . URL . '/login?error=' . urlencode($reason));
        exit;
    }
}
