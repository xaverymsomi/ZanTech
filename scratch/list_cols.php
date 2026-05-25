<?php
require 'vendor/autoload.php';
require __DIR__ . '/../bootstrap/config.php';
require __DIR__ . '/../src/Database/Database.php';
require __DIR__ . '/../src/Database/DB.php';

$db = \Database\DB::connection();
try {
    $res = $db->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'mx_menu'");
    foreach($res as $r) {
        echo $r['COLUMN_NAME'] . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
