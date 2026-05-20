<?php

use Database\Database;

$values = json_decode(file_get_contents("php://input"));
$all_rec = [];
$ass_rec = ['mx_User_Group', 'mx_User_Permission'];

if (is_object($values)) {
    $id = $values->{'id'};
    $table = $values->{'table'};
    // Whitelist allowed tables
    $allowed_tables = ['mx_user', 'mx_group', 'mx_institution', 'mx_applicant', 'mx_agency'];
    if (!in_array($table, $allowed_tables)) {
        die(json_encode(['error' => 'Unauthorized table access']));
    }
    $result = getRecord($id, $table);
    $all_rec['User'] = $result;
    foreach ($ass_rec as $table) {
        $class = ucfirst(str_replace("mx_", "", $table));
        $all_rec[$class] = null;
    }
    print json_encode($all_rec);
}

function getRecord($id, $table) {
    $db = new Database();
    $sql = "SELECT * FROM " . $table . "_view WHERE id=:id";
    $result = $db->select($sql, array(':id' => $id));
    unset($db);
    return $result;
}

