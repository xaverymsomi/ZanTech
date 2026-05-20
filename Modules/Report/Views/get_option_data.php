<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To changethis template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Database\Database;

$posted_data = json_decode(file_get_contents("php://input"), true);

$table = $posted_data['table'];
// Whitelist of allowed tables for this endpoint
$allowed_tables = ['mx_group', 'mx_institution', 'mx_branch', 'mx_applicant_type', 'mx_application_status', 'mx_payment_status', 'mx_permit_type'];
if (!in_array($table, $allowed_tables)) {
    die(json_encode(['error' => 'Invalid table requested']));
}
$result = loadData($table);


print json_encode($result);


function loadData($table) {
    $database = new Database();
    $sql = "SELECT * FROM " . $table . " ORDER BY id ASC";
    $result = $database->select($sql);

    if ($table == "mx_group") {
        foreach ($result as $value) {
            $array[] = ['id' => $value['id'], 'name' =>$value['name']];
        }
    } else {
        foreach ($result as $value) {
             $array[] = ['id' => $value['id'], 'name' =>$value['txt_name']];
        }
    }
    unset($database);
    return $array;
}
