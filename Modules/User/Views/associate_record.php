<?php

include '../../inc/config.php';
require '../../libs/Database.php';
$values = json_decode(file_get_contents("php://input"));

if (is_object($values)) {
    $all_rec = [];

    $id = $values->{'id'};
    $table = "mx_" . strtolower($values->{'table'});
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
