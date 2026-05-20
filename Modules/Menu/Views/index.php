<?php
/**
 * @group menu
 * @filesource /menu/index
 * @author John Misango
 */

use Database\Database;
use Authentication\Perm_Auth;
use Authentication\Session;

Session::init();

$db = new Database();
$perm = new Perm_Auth();
$user_id = $_GET['user_id'];
$main_menus = getMainMenu();
$data = [];
$menus = [];
$sections = $perm->getPermittedSections($user_id);
//print_r($sections);
foreach ($main_menus as $menu) {
    foreach ($sections as $section) {
        if ($menu['txt_name'] == $section['section_name']) {
            $menus[] = $menu;
            break;
        }
    }
}
//print_r($menus);
//iterate through menus array
foreach ($menus as $menu) {
    $submenus = getSubMenu($menu['id']);
    if($menu['txt_name']=='Utility'){
        $submenus=$perm->verifySubMenuPermissions($submenus,$user_id);
//        print_r(json_encode($submenus));
    }

    $subdata = [];
    if (sizeof($submenus)) {
        foreach ($submenus as $sub) {
            $subdata[] = [ 'name' => trans($sub['txt_name']), 'link' => APP_DIR . $sub['txt_link'], 'title' => $sub['txt_title'], 'icon' => $sub['txt_icon']];
        }
    }
    //ChromePhp::log($menu['txt_name']);
    if ($menu['txt_link'] == "#") {
        $data[] = ['id' => $menu['id'], 'name' => trans($menu['txt_name']),
            'link' => $menu['txt_link'], 'title' => $menu['txt_title'],
            'icon' => $menu['txt_icon'], 'submenus' => $subdata];
    } else {
        $data[] = ['id' => $menu['id'], 'name' => trans($menu['txt_name']),
            'link' => APP_DIR . $menu['txt_link'], 'title' => $menu['txt_title'],
            'icon' => $menu['txt_icon'], 'submenus' => $subdata];
    }
}

function getMainMenu() {
    $db = new Database();
    $sql = 'SELECT * FROM mx_menu WHERE int_parent IS NULL  ORDER BY int_position';
    return $db->select($sql);
}

function getSubMenu($id) {
    $db = new Database();
    $sql = "SELECT * FROM mx_menu WHERE int_parent=:id  ORDER BY int_position ASC";
    return $db->select($sql, array(':id' => $id));
}

print json_encode(['data' => $data]);
