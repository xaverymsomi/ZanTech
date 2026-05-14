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

        $sections = []; //Create new role object
        $perm = new Perm_Auth(); //Create new role object

        //Prepare statement and execute it
        $group_string = self::getUserGroups($user_id);

        $stm = self::$db->prepare("SELECT DISTINCT mx_permission.* FROM mx_permission 
                                    JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id 
                                    WHERE mx_group_permission.opt_mx_group_id IN (" . ($group_string ?: '0') . ")
                                UNION 
                                    SELECT DISTINCT mx_permission.* FROM mx_permission 
                                    JOIN mx_login_credential_permission ON mx_login_credential_permission.opt_mx_permission_id = mx_permission.id 
                                    WHERE mx_login_credential_permission.opt_mx_login_credential_id = :user_id");
        $stm->execute(array(":user_id" => $user_id));

        //Loop through the results
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $perm->permissionList[$row["txt_name"]] = true;
        }
        return $perm;
    }

    //Create populate Role Object
    public static function getPermittedSections($user_id) {
        $sections = [];
        $group_string = self::getUserGroups($user_id);
        $stm = self::$db->prepare("SELECT DISTINCT mx_section.txt_name AS 'section_name' FROM mx_section 
                                        JOIN mx_permission ON mx_permission.opt_mx_section_id = mx_section.id 
                                        JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id 
                                        WHERE mx_group_permission.opt_mx_group_id IN (" . ($group_string ?: '0') . ") 
                                UNION 
                                    SELECT DISTINCT mx_section.txt_name AS 'section_name' FROM mx_login_credential_permission 
                                        JOIN mx_permission ON mx_permission.id = mx_login_credential_permission.opt_mx_permission_id 
                                        JOIN mx_section ON mx_section.id = mx_permission.opt_mx_section_id 
                                        WHERE mx_login_credential_permission.opt_mx_login_credential_id = :user_id ");
        $stm->execute(array(":user_id" => $user_id));

        return $stm->fetchAll(PDO::FETCH_ASSOC);
    }

    //Check if the specific role has a given permission
    public function verifyPermission($permission) : bool
    {
        return isset($this->permissionList[$permission]);
    }

    private static function getUserGroups($user_id) : string
    {
        $group_string = '';
        $stm = self::$db->prepare("SELECT * FROM mx_login_credential_group WHERE mx_login_credential_group.opt_mx_login_credential_id = :user_id");
        $stm->execute(array(":user_id" => $user_id));

        //Loop through the results
        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $group_string .= $row["opt_mx_group_id"] . ',';
        }

        return rtrim($group_string, ',');
    }

    function verifySubMenuPermissions($submenus, $user_id) : array
    {
        $group_id = $this->getUserGroups($user_id);
        $sql = "SELECT DISTINCT mx_permission.* FROM mx_permission 
                    JOIN mx_group_permission ON mx_group_permission.opt_mx_permission_id = mx_permission.id 
                    WHERE mx_group_permission.opt_mx_group_id IN (" . $group_id . ")
                UNION 
                    SELECT DISTINCT mx_permission.* FROM mx_permission 
                    JOIN mx_login_credential_permission ON mx_login_credential_permission.opt_mx_permission_id = mx_permission.id 
                    WHERE mx_login_credential_permission.opt_mx_login_credential_id = '" . $user_id . "'";
        $permissions = self::$db->query($sql)->fetchAll();

        $permission_values = [];
        $submenuvalues = [];
        foreach ($permissions as $permission) {
            $permission_value = explode('_', trim($permission['txt_name']));
            $perm = 'view_' . $permission_value[1];
            $permission_values[] = $perm;
        }
        foreach ($submenus as $submenu) {
            $menu = explode(' ', trim($submenu['txt_name']));
            $sub = 'view_' . strtolower($menu[0]);
            if (in_array($sub, $permission_values)) {
                $submenuvalues[] = $submenu;
            }
        }

        return $submenuvalues;
    }

}
