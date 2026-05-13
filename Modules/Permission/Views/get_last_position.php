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
    $permissions = last_position();
    print json_encode($permissions, JSON_NUMERIC_CHECK);
}

function last_position() {
    $db = new Database();
//    $query = "SELECT * from mx_menu where int_parent IS NULL";
    $query = "SELECT int_position as last_position FROM mx_menu where int_parent is null order by int_position desc limit 1";
    $result = $db->select($query);
    $data;
    if ($result) {
        foreach ($result as $position){
            $data=$position['last_position'];
        }
        return $data;
    }
    
}
