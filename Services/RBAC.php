<?php

namespace Services;

final class RBAC
{
    public static function canAssignGroup(int $currentGroupId, int $targetGroupId): bool
    {
        return self::canManageGroup($currentGroupId, $targetGroupId);
    }

    public static function canEditGroup(int $currentGroupId, ?int $targetGroupId): bool
    {
        return self::canManageGroup($currentGroupId, $targetGroupId);
    }

    public static function canSuspendGroup(int $currentGroupId, ?int $targetGroupId): bool
    {
        return self::canManageGroup($currentGroupId, $targetGroupId);
    }

    public static function canActivateGroup(int $currentGroupId, ?int $targetGroupId): bool
    {
        return self::canManageGroup($currentGroupId, $targetGroupId);
    }

    public static function canResetPassword(int $currentGroupId, ?int $targetGroupId): bool
    {
        return self::canManageGroup($currentGroupId, $targetGroupId);
    }

    private static function canManageGroup(int $currentGroupId, ?int $targetGroupId): bool
    {
        if ($currentGroupId <= 0 || $targetGroupId === null || $targetGroupId <= 0) {
            return false;
        }

        return $currentGroupId <= $targetGroupId;
    }
}
