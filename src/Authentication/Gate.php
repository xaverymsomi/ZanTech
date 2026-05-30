<?php

namespace Authentication;

use Database\DB;
use Logging\Log;
use PDO;

/**
 * Gate — Oryn RBAC Permission Engine
 *
 * Single source of truth for all permission checks.
 * Loads permissions once per request and caches them in the session.
 *
 * Usage:
 *   Gate::allows('view_menu')
 *   Gate::allowsAny(['edit_menu', 'delete_menu'])
 *   Gate::allowsAll(['view_reports', 'export_reports'])
 *   Gate::isSuperAdmin()
 *   Gate::flush()   // call after changing a user's groups/permissions
 */
final class Gate
{
    /** Session key prefix for the permission cache */
    private const CACHE_PREFIX = 'zt_perm_';

    /** Resolved singleton instance for the current request */
    private static ?self $instance = null;

    /** Flat map of slug => true for the loaded user */
    private array $permissions = [];

    /** Whether the loaded user is a super admin */
    private bool $superAdmin = false;

    /** The user ID this instance was built for */
    private int $userId;

    // -------------------------------------------------------------------------
    // Construction (private — use Gate::for() or Gate::instance())
    // -------------------------------------------------------------------------

    private function __construct(int $userId, bool $superAdmin, array $permissions)
    {
        $this->userId      = $userId;
        $this->superAdmin  = $superAdmin;
        $this->permissions = $permissions;
    }

    // -------------------------------------------------------------------------
    // Public API — static facade
    // -------------------------------------------------------------------------

    /**
     * Check a single permission for the currently authenticated user.
     */
    public static function allows(string $permission): bool
    {
        return self::instance()->can($permission);
    }

    /**
     * Returns true if the user has at least ONE of the given permissions.
     */
    public static function allowsAny(array $permissions): bool
    {
        $gate = self::instance();
        foreach ($permissions as $p) {
            if ($gate->can((string)$p)) return true;
        }
        return false;
    }

    /**
     * Returns true only if the user has ALL of the given permissions.
     */
    public static function allowsAll(array $permissions): bool
    {
        $gate = self::instance();
        foreach ($permissions as $p) {
            if (!$gate->can((string)$p)) return false;
        }
        return true;
    }

    /**
     * Returns true if the current user is a super admin.
     */
    public static function isSuperAdmin(): bool
    {
        return self::instance()->superAdmin;
    }

    /**
     * Build a Gate instance for a specific user ID (not the current session user).
     * Useful for admin tools checking another user's access.
     */
    public static function forUser(int $userId): self
    {
        return self::build($userId);
    }

    /**
     * Invalidate the session permission cache for the current user.
     * Call this after any group or permission assignment change.
     */
    public static function flush(?int $userId = null): void
    {
        Session::init();

        if ($userId !== null) {
            Session::set(self::CACHE_PREFIX . $userId, null);
            return;
        }

        // Flush current user's cache
        $user = Auth::user();
        if ($user && isset($user['credential_id'])) {
            Session::set(self::CACHE_PREFIX . $user['credential_id'], null);
        }

        // Also reset the singleton so it reloads on next check
        self::$instance = null;
    }

    /**
     * Get the full flat permission list for the current user (for debugging).
     */
    public static function list(): array
    {
        return array_keys(self::instance()->permissions);
    }

    // -------------------------------------------------------------------------
    // Internal — instance resolution
    // -------------------------------------------------------------------------

    /**
     * Resolve or create the singleton Gate instance for the current session user.
     */
    private static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        if (!Auth::isLogged()) {
            Log::sysLog('GATE: Permission check attempted with no active session.');
            return new self(0, false, []);
        }

        $user = Auth::user();
        $credentialId = (int)($user['credential_id'] ?? 0);

        self::$instance = self::build($credentialId);
        return self::$instance;
    }

    /**
     * Build a Gate instance for the given credential ID.
     * Uses session cache to avoid repeated DB hits within the same request.
     */
    private static function build(int $credentialId): self
    {
        if ($credentialId <= 0) {
            return new self(0, false, []);
        }

        Session::init();
        $cacheKey = self::CACHE_PREFIX . $credentialId;
        $cached   = Session::get($cacheKey);

        if (is_array($cached) && isset($cached['__built'])) {
            return new self(
                $credentialId,
                (bool)($cached['__superadmin'] ?? false),
                $cached['__perms'] ?? []
            );
        }

        // Load fresh from DB
        [$superAdmin, $permissions] = self::loadFromDb($credentialId);

        // Write to session cache
        Session::set($cacheKey, [
            '__built'      => true,
            '__superadmin' => $superAdmin,
            '__perms'      => $permissions,
        ]);

        Log::sysLog([
            'event'         => 'GATE_BUILT',
            'credential_id' => $credentialId,
            'super_admin'   => $superAdmin,
            'perm_count'    => count($permissions),
        ]);

        return new self($credentialId, $superAdmin, $permissions);
    }

    /**
     * Query the database for super admin flag and the full permission set.
     *
     * @return array{bool, array<string, true>}
     */
    private static function loadFromDb(int $credentialId): array
    {
        try {
            $db = DB::connection();

            $superAdmin = false;
            try {
                // 1. Check super admin flag
                $stmt = $db->prepare(
                    "SELECT bit_is_superadmin FROM mx_login_credential WHERE id = :id"
                );
                $stmt->execute([':id' => $credentialId]);
                $row        = $stmt->fetch(PDO::FETCH_ASSOC);
                $superAdmin = (bool)(int)($row['bit_is_superadmin'] ?? 0);
            } catch (\Throwable $e) {
                // Graceful fallback if migration hasn't been run yet
                Log::sysLog('GATE_SUPERADMIN_FALLBACK: run database/migrations/rbac_v2.sql');
            }

            // Super admins skip permission loading entirely
            if ($superAdmin) {
                return [true, []];
            }

            // 2. Load permissions — via groups UNION direct user assignments
            $stmt = $db->prepare(
                "SELECT DISTINCT p.txt_name
                   FROM mx_permission p
                   JOIN mx_group_permission gp ON gp.opt_mx_permission_id = p.id
                   JOIN mx_login_credential_group lcg ON lcg.opt_mx_group_id = gp.opt_mx_group_id
                  WHERE lcg.opt_mx_login_credential_id = :cid
                 UNION
                 SELECT DISTINCT p.txt_name
                   FROM mx_permission p
                   JOIN mx_login_credential_permission lcp ON lcp.opt_mx_permission_id = p.id
                  WHERE lcp.opt_mx_login_credential_id = :cid2"
            );
            $stmt->execute([':cid' => $credentialId, ':cid2' => $credentialId]);

            $permissions = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $slug = trim((string)($r['txt_name'] ?? ''));
                if ($slug !== '') {
                    $permissions[$slug] = true;
                }
            }

            return [false, $permissions];

        } catch (\Throwable $e) {
            Log::exception($e, 'GATE_LOAD_ERROR', ['credential_id' => $credentialId]);
            return [false, []];
        }
    }

    // -------------------------------------------------------------------------
    // Internal — instance check
    // -------------------------------------------------------------------------

    /**
     * Check a permission on this instance.
     * Super admins always return true.
     */
    private function can(string $permission): bool
    {
        if ($this->superAdmin) {
            return true;
        }

        return isset($this->permissions[$permission]);
    }
}
