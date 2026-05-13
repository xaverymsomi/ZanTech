<?php
//Retrieve User Data For Edit Form
//Author: Fatma Azad
include '../../inc/config.php';
include '../../libs/Log.php';
require '../../libs/Model.php';

$values = json_decode(file_get_contents("php://input"));

if (is_object($values)) {
    $id = $values->{'id'};
    $table = $values->{'table'};
    
    $model = new Model();
    $result = $model->getRecord($id, $table)[0];

    print json_encode($result);
}
