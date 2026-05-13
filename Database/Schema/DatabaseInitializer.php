<?php

declare(strict_types=1);

namespace Database\Schema;

use Database\Database;
use Exception;
use PDOException;

class DatabaseInitializer
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function initialize(string $sqlFilePath): array
    {
        if (!file_exists($sqlFilePath)) {
            return [
                'status' => 'error',
                'message' => "SQL file not found at: {$sqlFilePath}"
            ];
        }

        $sql = file_get_contents($sqlFilePath);
        if (empty($sql)) {
            return [
                'status' => 'error',
                'message' => "SQL file is empty."
            ];
        }

        try {
            // Split SQL by semicolons, but be careful with multi-line statements (like triggers/procedures)
            // For a simple init script, we can execute the whole thing if the driver supports it,
            // or split by simple semicolons.
            
            // SQL Server (sqlsrv) often prefers executing batches or handling GO commands.
            // For simplicity in this framework, we'll try to execute the full block.
            
            $this->db->exec($sql);

            return [
                'status' => 'success',
                'message' => "Database initialized successfully from " . basename($sqlFilePath)
            ];

        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => "Database Error: " . $e->getMessage()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => "General Error: " . $e->getMessage()
            ];
        }
    }
}
