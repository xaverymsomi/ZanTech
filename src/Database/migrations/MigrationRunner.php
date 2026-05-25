<?php

namespace Database\Migrations;

use Database\Database;
use PDO;

final class MigrationRunner
{
    private const TABLE = 'zt_migrations';

    public function __construct(private readonly Database|MigrationConnection $db) {}

    /**
     * @return array<int, array{name:string,status:string,message:string}>
     */
    public function run(string $directory): array
    {
        $this->ensureTable();
        $applied = $this->appliedMigrations();
        $results = [];

        foreach ($this->migrationFiles($directory) as $file) {
            $name = basename($file);
            if (isset($applied[$name])) {
                $results[] = ['name' => $name, 'status' => 'skipped', 'message' => 'Already applied'];
                continue;
            }

            $sql = trim((string)file_get_contents($file));
            if ($sql === '') {
                $results[] = ['name' => $name, 'status' => 'skipped', 'message' => 'Empty migration'];
                continue;
            }

            try {
                $this->db->beginTransaction();
                $this->executeSql($sql);
                $this->recordMigration($name);
                $this->db->commit();

                $results[] = ['name' => $name, 'status' => 'success', 'message' => 'Applied'];
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }

                $results[] = ['name' => $name, 'status' => 'error', 'message' => $e->getMessage()];
                break;
            }
        }

        return $results;
    }

    public function status(string $directory): array
    {
        $this->ensureTable();
        $applied = $this->appliedMigrations();
        $rows = [];

        foreach ($this->migrationFiles($directory) as $file) {
            $name = basename($file);
            $rows[] = [
                'name' => $name,
                'status' => isset($applied[$name]) ? 'applied' : 'pending',
                'applied_at' => $applied[$name] ?? null,
            ];
        }

        return $rows;
    }

    private function ensureTable(): void
    {
        if ($this->isMysql()) {
            $this->db->exec(
                'CREATE TABLE IF NOT EXISTS zt_migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL UNIQUE,
                    applied_at DATETIME NOT NULL
                )'
            );
            return;
        }

        $this->db->exec(
            "IF NOT EXISTS (SELECT * FROM sysobjects WHERE name='zt_migrations' AND xtype='U')
            CREATE TABLE zt_migrations (
                id INT IDENTITY(1,1) PRIMARY KEY,
                migration NVARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            )"
        );
    }

    private function appliedMigrations(): array
    {
        $rows = $this->db->query('SELECT migration, applied_at FROM ' . $this->db->quoteTable(self::TABLE))
            ->fetchAll(PDO::FETCH_ASSOC);

        $applied = [];
        foreach ($rows as $row) {
            $applied[(string)$row['migration']] = (string)$row['applied_at'];
        }

        return $applied;
    }

    /**
     * @return string[]
     */
    private function migrationFiles(string $directory): array
    {
        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.sql') ?: [];
        sort($files, SORT_STRING);
        return $files;
    }

    private function executeSql(string $sql): void
    {
        $batches = preg_split('/^\s*GO\s*$/mi', $sql) ?: [$sql];
        foreach ($batches as $batch) {
            $batch = trim($batch);
            if ($batch !== '') {
                $this->db->exec($batch);
            }
        }
    }

    private function recordMigration(string $name): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO ' . $this->db->quoteTable(self::TABLE) .
            ' (' . $this->db->quoteColumn('migration') . ', ' . $this->db->quoteColumn('applied_at') . ') VALUES (:migration, :applied_at)'
        );

        $stmt->execute([
            ':migration' => $name,
            ':applied_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function isMysql(): bool
    {
        return strtolower((string)(defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'sqlsrv'))) === 'mysql';
    }
}
