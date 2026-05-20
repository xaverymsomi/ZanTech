<?php

use Database\Database;

$values = json_decode(file_get_contents("php://input"));

if (is_object($values)) {
    $all_rec = [];

    $id = $values->{'id'};
    $table_input = strtolower($values->{'table'});
    // Whitelist allowed tables
    $allowed_tables = ['user', 'group', 'institution', 'applicant', 'agency', 'user_group', 'user_permission'];
    if (!in_array($table_input, $allowed_tables)) {
        die(json_encode(['error' => 'Unauthorized table access']));
    }
    $table = "mx_" . $table_input;
    if ($table == "mx_user") {
        $data = getRecord($id, $table);
    } else {
        $data = getAssRecord($id, $table);
    }

    $class = ucfirst(str_replace("mx_", "", $table));

    if ($data != null) {
        $all_rec[$class] = $data[0];
    } else {
        $all_rec[$class] = $data;
    }

    print json_encode($all_rec);
}

function getAssRecord($id, $table) {
    try{
        $db = new Database();
        $sql = "SELECT * FROM " . $table . "_view WHERE user_id=:id";
        $result = $db->select($sql, array(':id' => $id));
        return [$result]; // Added array to allow all records to be returned for further processing
    }catch(Exception $ex)
    {
        return [];
    }
    
}

function getRecord($id, $table) {
    try{
        $db = new Database();
        $sql = "SELECT * FROM " . $table . "_view WHERE id=:id";
        $result = $db->select($sql, array(':id' => $id));
        unset($db);
        return [$result];
    }catch(Exception $ex)
    {
        return [];
    }
    
}
