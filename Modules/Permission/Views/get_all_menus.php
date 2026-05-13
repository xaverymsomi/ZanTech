<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Library\\Database;

include '../../inc/config.php';
require '../../libs/Database.php';
$values = json_decode(file_get_contents("php://input"));
if (is_object($values)) {
    $permission = $values->{'permission'};
    $permissions = getMenus();
    print json_encode($permissions);
}

function getMenus() {
    $db = new Database();
//    $query = "SELECT * from mx_menu where int_parent IS NULL";
    $query = "SELECT * from mx_menu WHERE int_parent IS NULL ORDER BY int_parent";
    $result = $db->select($query);
    $data = [];
    if ($result) {
        foreach($result as $row) {
            $row['txt_name'] = ucwords($row['txt_name']);
            $sql = "SELECT * from mx_menu WHERE int_parent = " . $row['id'] . " ORDER BY int_position";
            
            $row['children'] = $db->select($sql);
            $data[] = $row;
            
        }
    }
    return $data;
}
