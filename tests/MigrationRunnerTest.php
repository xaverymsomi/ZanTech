<?php

declare(strict_types=1);

use Database\Migrations\MigrationConnection;
use Database\Migrations\MigrationRunner;
use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zantech-migrations-test';
        $this->removeDir($this->dir);
        mkdir($this->dir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->dir);
        unset($_ENV['DB_TYPE']);
        putenv('DB_TYPE');
    }

    public function testRunAppliesPendingSqlFilesAndRecordsThem(): void
    {
        $_ENV['DB_TYPE'] = 'sqlsrv';
        file_put_contents($this->dir . '/001_create.sql', "CREATE TABLE one (id INT)\nGO\nCREATE TABLE two (id INT)");
        file_put_contents($this->dir . '/002_empty.sql', " \n");

        $db = new FakeMigrationConnection();
        $results = (new MigrationRunner($db))->run($this->dir);

        $this->assertSame('success', $results[0]['status']);
        $this->assertSame('001_create.sql', $results[0]['name']);
        $this->assertSame('skipped', $results[1]['status']);
        $this->assertSame(['001_create.sql'], array_keys($db->applied));
        $this->assertContains('CREATE TABLE one (id INT)', $db->executedSql);
        $this->assertContains('CREATE TABLE two (id INT)', $db->executedSql);
    }

    public function testRunSkipsAlreadyAppliedMigrations(): void
    {
        file_put_contents($this->dir . '/001_create.sql', 'SELECT 1');

        $db = new FakeMigrationConnection(['001_create.sql' => '2026-01-01 00:00:00']);
        $results = (new MigrationRunner($db))->run($this->dir);

        $this->assertSame([[
            'name' => '001_create.sql',
            'status' => 'skipped',
            'message' => 'Already applied',
        ]], $results);
    }

    public function testRunRollsBackAndStopsOnFailure(): void
    {
        file_put_contents($this->dir . '/001_fail.sql', 'FAIL HERE');
        file_put_contents($this->dir . '/002_later.sql', 'SELECT 1');

        $db = new FakeMigrationConnection();
        $results = (new MigrationRunner($db))->run($this->dir);

        $this->assertSame('error', $results[0]['status']);
        $this->assertSame(1, $db->rollbacks);
        $this->assertSame([], $db->applied);
    }

    public function testStatusReportsAppliedAndPending(): void
    {
        file_put_contents($this->dir . '/001_done.sql', 'SELECT 1');
        file_put_contents($this->dir . '/002_pending.sql', 'SELECT 2');

        $rows = (new MigrationRunner(new FakeMigrationConnection([
            '001_done.sql' => '2026-01-01 00:00:00',
        ])))->status($this->dir);

        $this->assertSame('applied', $rows[0]['status']);
        $this->assertSame('pending', $rows[1]['status']);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (array_diff(scandir($dir) ?: [], ['.', '..']) as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }

        rmdir($dir);
    }
}

final class FakeMigrationConnection implements MigrationConnection
{
    public array $executedSql = [];
    public int $rollbacks = 0;
    public bool $transaction = false;

    public function __construct(public array $applied = []) {}

    public function exec(string $statement): mixed
    {
        if (str_contains($statement, 'FAIL')) {
            throw new RuntimeException('Migration failed');
        }

        if (!str_contains($statement, 'zt_migrations')) {
            $this->executedSql[] = trim($statement);
        }

        return 1;
    }

    public function query(string $query, ?int $fetchMode = null): mixed
    {
        $rows = [];
        foreach ($this->applied as $migration => $appliedAt) {
            $rows[] = ['migration' => $migration, 'applied_at' => $appliedAt];
        }

        return new FakeMigrationStatement($rows);
    }

    public function prepare(string $query, array $options = []): mixed
    {
        return new FakeMigrationPreparedStatement($this);
    }

    public function beginTransaction(): bool
    {
        $this->transaction = true;
        return true;
    }

    public function commit(): bool
    {
        $this->transaction = false;
        return true;
    }

    public function rollBack(): bool
    {
        $this->transaction = false;
        $this->rollbacks++;
        return true;
    }

    public function inTransaction(): bool
    {
        return $this->transaction;
    }

    public function quoteTable(string $table): string
    {
        return '[' . $table . ']';
    }

    public function quoteColumn(string $column): string
    {
        return '[' . $column . ']';
    }
}

final class FakeMigrationStatement
{
    public function __construct(private readonly array $rows) {}

    public function fetchAll(int $mode): array
    {
        return $this->rows;
    }
}

final class FakeMigrationPreparedStatement
{
    public function __construct(private readonly FakeMigrationConnection $db) {}

    public function execute(array $params): bool
    {
        $this->db->applied[(string)$params[':migration']] = (string)$params[':applied_at'];
        return true;
    }
}
