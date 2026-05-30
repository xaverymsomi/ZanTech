<?php

namespace Authentication;

use Logging\Log;
use Exceptions\AuthException;

final class Auth
{
    public const KEY_USER = ZT_SESS_AUTH_USER;
    public const KEY_FLAG = ZT_SESS_AUTH_FLAG;

    public static function login(array $user): void
    {
        if (empty($user) || empty($user['id']) || empty($user['txt_username']) || trim($user['txt_username']) === '') {
            throw new AuthException('Invalid user payload or empty username during login');
        }

        Session::init();
        Session::regenerate(); // fixation protection

        $cleanUser = [
            'id'              => (int) $user['id'],
            'credential_id'   => (int) ($user['credential_id'] ?? 0),
            'txt_username'    => (string) ($user['txt_username'] ?? ''),
            'txt_name'        => (string) ($user['txt_name'] ?? ''),
            'txt_domain'      => (string) ($user['txt_domain'] ?? ''),
            'opt_mx_group_id' => (int) ($user['opt_mx_group_id'] ?? 0),
            'bit_is_superadmin' => (bool) ($user['bit_is_superadmin'] ?? false),
        ];

        Session::set(self::KEY_USER, $cleanUser);
        Session::set(self::KEY_FLAG, true);
        
        // Security Fingerprinting
        Session::set('zt_auth_ip', $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
        Session::set('zt_auth_ua', $_SERVER['HTTP_USER_AGENT'] ?? 'unknown');
        Session::set('zt_last_activity', time());

        \Services\AuditTrail::log('LOGIN_SUCCESS', "User: {$cleanUser['txt_username']}");

        Log::sysLog([
            'event' => 'AUTH_LOGIN',
            'user'  => $cleanUser['txt_username'],
            'id'    => $cleanUser['id'],
        ]);
    }

    public static function logout(): void
    {
        $user = self::user();
        $username = $user['txt_username'] ?? 'UNKNOWN';

        \Services\AuditTrail::log('LOGOUT', "User: {$username}");

        Session::init();
        Session::set(self::KEY_USER, null);
        Session::set(self::KEY_FLAG, false);
        Session::destroy();
        Log::sysLog('AUTH_LOGOUT');
    }

    public static function isLogged(): bool
    {
        Session::init();
        $flag = Session::get(self::KEY_FLAG);
        if ($flag !== true && $flag !== 1 && $flag !== '1') {
            return false;
        }
        $user = Session::get(self::KEY_USER);
        if (!is_array($user) || empty($user['id'])) {
            Log::sysLog('AUTH_SESSION_CORRUPTED');
            self::forceLogout();
            return false;
        }
        return true;
    }


    public static function user(): ?array
    {
        return self::isLogged() ? Session::get(self::KEY_USER) : null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function groupId(): ?int
    {
        return self::user()['opt_mx_group_id'] ?? null;
    }

    public static function domain(): ?string
    {
        return self::user()['txt_domain'] ?? null;
    }

    /**
     * Returns true if the current session user is a super admin.
     * This reads directly from the session payload (set at login from bit_is_superadmin column).
     */
    public static function isSuperAdmin(): bool
    {
        return (bool)(self::user()['bit_is_superadmin'] ?? false);
    }

    private static function forceLogout(): void
    {
        try {
            Session::destroy();
        } catch (\Throwable) {}
    }
}
