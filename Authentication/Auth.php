<?php

declare(strict_types=1);

namespace Authentication;

use Logging\Log;
use Exceptions\AuthException;

final class Auth
{
    public const KEY_USER = ZT_SESS_AUTH_USER;
    public const KEY_FLAG = ZT_SESS_AUTH_FLAG;

    public static function login(array $user): void
    {
        if (empty($user) || empty($user['id'])) {
            throw new AuthException('Invalid user payload during login');
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
            'role'            => (int) ($user['opt_mx_group_id'] ?? 0),
        ];

        Session::set(self::KEY_USER, $cleanUser);
        Session::set(self::KEY_FLAG, true);

        Log::sysLog([
            'event' => 'AUTH_LOGIN',
            'user'  => $cleanUser['txt_username'],
            'id'    => $cleanUser['id'],
        ]);
    }

    public static function logout(): void
    {
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

    private static function forceLogout(): void
    {
        try {
            Session::destroy();
        } catch (\Throwable) {}
    }
}
