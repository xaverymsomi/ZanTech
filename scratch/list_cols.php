<?php
require 'vendor/autoload.php';
require 'configuration/config.php';
require 'Database/Database.php';
require 'Database/DB.php';

$db = \Database\DB::connection();
try {
    $res = $db->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'mx_menu'");
    foreach($res as $r) {
        echo $r['COLUMN_NAME'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
