<?php
// Initialize framework to get DB access
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../bootstrap/config.php';
require_once __DIR__ . '/../bootstrap/sys_pref.php';

use Database\DB;

try {
    $db = DB::connection();
    
    $type = DB_TYPE;
    $schema = [];
    
    if ($type === 'sqlsrv' || strtolower($type) === 'sqlsrv') {
        $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
        foreach ($tables as $t) {
            $tableName = $t['TABLE_NAME'];
            $columns = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?", [$tableName]);
            $schema[$tableName] = $columns;
        }
    } else if (strtolower($type) === 'mysql') {
        $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'");
        foreach ($tables as $t) {
            $tableName = $t['TABLE_NAME'];
            $columns = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?", [$tableName]);
            $schema[$tableName] = $columns;
        }
    } else {
        $schema['error'] = 'Unsupported DB type: ' . $type;
    }
    
    file_put_contents(__DIR__ . '/schema.json', json_encode($schema, JSON_PRETTY_PRINT));
    echo "Success";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
