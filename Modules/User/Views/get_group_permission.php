<?php

//Author: Fatma Azad
include '../../inc/config.php';
include '../../libs/Database.php';

$posted_data = json_decode(file_get_contents("php://input"), true);
$group_id = $posted_data['group_id'];

$array = [];
$db = new Database();
$query = "SELECT mx_permission.* FROM mx_group_permission JOIN mx_permission ON mx_permission.id = mx_group_permission.permission_id WHERE mx_group_permission.group_id = $group_id";
$result = $db->select($query);


print json_encode(['group_permission' => $result]);
