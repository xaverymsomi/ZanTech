<?php

namespace Foundation\Console;

use Database\DB;

class ModelMigrator
{
    public function migrate(bool $dryRun = false): void
    {
        echo "Starting Automated Schema Generation...\n";

        $basePath = dirname(__DIR__, 3);
        $modulesDir = $basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Modules';
        
        if (!is_dir($modulesDir)) {
            echo "Modules directory not found at {$modulesDir}!\n";
            return;
        }

        $models = $this->findModels($modulesDir, $basePath);
        echo "Found " . count($models) . " models to inspect.\n";

        $db = DB::connection();
        $driver = $db->getDriverType();
        $isSqlServer = in_array($driver, ['sqlsrv', 'odbc', 'dblib']);

        foreach ($models as $modelClass) {
            if (!class_exists($modelClass)) {
                continue;
            }

            try {
                $instance = new $modelClass();
                if (!method_exists($instance, 'getSchema')) continue;

                $table = $instance->getTable();
                $schema = $instance->getSchema();

                if (empty($schema)) {
                    continue; // Skip models without schema defined
                }

                echo "Processing table: {$table}\n";
                $sql = $this->generateCreateTableSql($table, $schema, $isSqlServer);

                if ($dryRun) {
                    echo "--- SQL PENDING ---\n{$sql}\n-------------------\n";
                } else {
                    $stmt = $db->prepare($sql);
                    $stmt->execute();
                    echo "[SUCCESS] Migrated table: {$table}\n";
                }
            } catch (\Throwable $e) {
                echo "[ERROR] Failed processing {$modelClass}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Automated migrations complete.\n";
    }

    private function findModels(string $dir, string $basePath): array
    {
        $models = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '_Model.php')) {
                // Determine class namespace dynamically from path
                $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $relativePath = str_replace('.php', '', $relativePath);
                $class = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);
                $models[] = $class;
            }
        }

        return $models;
    }

    private function generateCreateTableSql(string $table, array $schema, bool $isSqlServer): string
    {
        if ($isSqlServer) {
            $sql = "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='{$table}' and xtype='U')\nBEGIN\n";
            $sql .= "    CREATE TABLE {$table} (\n";
            $sql .= "        id BIGINT IDENTITY(1,1) PRIMARY KEY,\n";
            
            $fields = [];
            foreach ($schema as $column => $type) {
                if (strtolower($column) === 'id') continue;
                $fields[] = "        {$column} {$type}";
            }
            
            $sql .= implode(",\n", $fields) . "\n";
            $sql .= "    );\nEND";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS {$table} (\n";
            $sql .= "    id BIGINT AUTO_INCREMENT PRIMARY KEY,\n";
            
            $fields = [];
            foreach ($schema as $column => $type) {
                if (strtolower($column) === 'id') continue;
                $fields[] = "    {$column} {$type}";
            }
            
            $sql .= implode(",\n", $fields) . "\n";
            $sql .= ");";
        }

        return $sql;
    }
}
