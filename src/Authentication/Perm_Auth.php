<?php

namespace Authentication;

use Database\Database;
use Logging\Log;
use PDO;

class Perm_Auth {

    protected static $permissions;
    private static $db;

    //Initiate an empty array for the permissions
    private array $permissionList;
    private int $userRole = 0;

    public function __construct() {
        $this->permissionList = array();
        $this->setDatabase();
    }

    //Alternatively use your own way of setting your Database connection.
    private function setDatabase() : void
    {
        self::$db = \Database\DB::connection();
    }

    //Create populate Role Object
    public static function getPermissions($id = null)
    {
        if (empty($id)) {
            if (!Auth::isLogged()) {
                Log::sysLog('No active session found during permission check. Forcing logout.');
                kill();
                exit;
            }
            $user_id = Auth::id();
        } else {
            $user_id = (int)$id;
        }

        $perm = new Perm_Auth(); //Create new role object

        [$groupSql, $groupParams] = self::groupInClause($user_id);
        $params = $groupParams + [':user_id' => $user_id];

        $stm = self::$db->prepare("SELECT DISTINCT mx_permission.* FROM mx_permission
                                    JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id
                                    WHERE mx_group_permission.opt_mx_group_id IN ({$groupSql})
                                UNION
                                    SELECT DISTINCT mx_permission.* FROM mx_permission
                                    JOIN mx_login_credential_permission ON mx_login_credential_permission.opt_mx_permission_id = mx_permission.id
                                    WHERE mx_login_credential_permission.opt_mx_login_credential_id = :user_id");
        $stm->execute($params);

        //Loop through the results
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $perm->permissionList[$row["txt_name"]] = true;
        }

        // Capture user role for Super Admin bypass
        $perm->userRole = (int)(Auth::user()['role'] ?? 0);

        return $perm;
    }

    //Create populate Role Object
    public static function getPermittedSections($user_id) {
        [$groupSql, $groupParams] = self::groupInClause((int)$user_id);
        $params = $groupParams + [':user_id' => (int)$user_id];

        $stm = self::$db->prepare("SELECT DISTINCT mx_section.txt_name AS section_name FROM mx_section
                                        JOIN mx_permission ON mx_permission.opt_mx_section_id = mx_section.id
                                        JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id
                                        WHERE mx_group_permission.opt_mx_group_id IN ({$groupSql})
                                UNION
                                    SELECT DISTINCT mx_section.txt_name AS section_name FROM mx_login_credential_permission
                                        JOIN mx_permission ON mx_permission.id = mx_login_credential_permission.opt_mx_permission_id
                                        JOIN mx_section ON mx_section.id = mx_permission.opt_mx_section_id
                                        WHERE mx_login_credential_permission.opt_mx_login_credential_id = :user_id ");
        $stm->execute($params);

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    //Check if the specific role has a given permission
    public function verifyPermission($permission) : bool
    {
        // Super Admin Bypass (Role 1 = Super Admin)
        if ($this->userRole === 1) {
            return true;
        }

        return isset($this->permissionList[$permission]);
    }

    private static function getUserGroups($user_id) : string
    {
        $groupIds = self::getUserGroupIds((int)$user_id);
        return implode(',', $groupIds);
    }

    private static function getUserGroupIds(int $user_id): array
    {
        $groupIds = [];
        $stm = self::$db->prepare("SELECT * FROM mx_login_credential_group WHERE mx_login_credential_group.opt_mx_login_credential_id = :user_id");
        $stm->execute(array(":user_id" => $user_id));

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $groupId = (int)($row["opt_mx_group_id"] ?? 0);
            if ($groupId > 0) {
                $groupIds[] = $groupId;
            }
        }

        return array_values(array_unique($groupIds));
    }

    private static function groupInClause(int $user_id): array
    {
        $groupIds = self::getUserGroupIds($user_id);
        if ($groupIds === []) {
            return ['0', []];
        }

        $placeholders = [];
        $params = [];
        foreach ($groupIds as $index => $groupId) {
            $placeholder = ':group_id_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $groupId;
        }

        return [implode(',', $placeholders), $params];
    }

    function verifySubMenuPermissions($submenus, $user_id) : array
    {
        if (!is_array($submenus)) {
            return [];
        }

        $submenuvalues = [];

        if ($this->userRole === 1) {
            return is_array($submenus) ? $submenus : [];
        }

        foreach ($submenus as $submenu) {
            $menuName = trim((string)($submenu['txt_name'] ?? ''));
            $normalized = strtolower((string)preg_replace('/[^a-z0-9]+/i', '_', $menuName));
            $normalized = trim((string)preg_replace('/_+/', '_', $normalized), '_');

            $candidates = [
                $menuName,
                $normalized,
                'view_' . $normalized,
            ];

            $firstToken = strtolower(strtok($menuName, ' ') ?: '');
            if ($firstToken !== '') {
                $candidates[] = 'view_' . preg_replace('/[^a-z0-9_]/', '', $firstToken);
            }

            foreach (array_unique($candidates) as $candidate) {
                if ($candidate !== '' && isset($this->permissionList[$candidate])) {
                    $submenuvalues[] = $submenu;
                    break;
                }
            }
        }

        return $submenuvalues;
    }

}
