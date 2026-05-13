<?php
include '../../inc/config.php';
include '../../libs/Database.php';
if(!session_start()){
    session_start();
}
$data['opt_mx_institution_ids'] = getInstitutions();

function getInstitutions() {
    $array = [];
    $db = new Database();
    $sql = "SELECT * FROM mx_institution WHERE opt_mx_state_id = 1 ORDER BY id ASC";
    $result = $db->select($sql);

    if ($result) {
        foreach ($result as $value) {
            $array[] = ['id' => $value['id'], 'name' => $value['txt_name']];
        }
    }

    unset($db);
    return $array;
}

$data['opt_mx_groups_ids'] = getGroups();

function getGroups() {
    $array = [];
    $db = new Database();
    $sql = "SELECT * FROM mx_group WHERE opt_mx_institution_id = ".$_SESSION['council']." ORDER BY id ASC";
    $result = $db->select($sql);

    if ($result) {
        foreach ($result as $value) {
            $array[] = ['id' => $value['id'], 'name' => $value['txt_name']];
        }
    }

    unset($db);
    return $array;
}

$data['opt_mx_branches_ids'] = getBranches();

function getBranches() {
    $array = [];
    $db = new Database();
    $sql = "SELECT * FROM mx_branch WHERE opt_mx_institution_id = " .$_SESSION['council']. " ORDER BY id ASC";
    $result = $db->select($sql);

    if ($result) {
        foreach ($result as $value) {
            $array[] = ['id' => $value['id'], 'name' => $value['txt_name']];
        }
    }

    unset($db);
    return $array;
}

print json_encode($data);
