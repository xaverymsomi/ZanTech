<?php

namespace Database;

use PDO;
use PDOException;
use Exceptions\DatabaseException;

class Database extends PDO
{
    public function __construct()
    {
        try {
            $type = defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'sqlsrv');
            $host = defined('DB_HOST') ? DB_HOST : ($_ENV['DB_HOST'] ?? '');
            $name = defined('DB_NAME') ? DB_NAME : ($_ENV['DB_NAME'] ?? '');
            $user = defined('DB_USER') ? DB_USER : ($_ENV['DB_USER'] ?? '');
            $pass = defined('DB_PASS') ? DB_PASS : ($_ENV['DB_PASS'] ?? '');

            switch ($type) {
                case 'mysql':
                    parent::__construct(
                        "mysql:host={$host};dbname={$name};charset=utf8mb4",
                        $user,
                        $pass
                    );
                    break;

                case 'sqlsrv':
                    parent::__construct(
                        "sqlsrv:Server={$host};Database={$name}",
                        $user,
                        $pass
                    );
                    break;

                case 'odbc':
                    parent::__construct(
                        "odbc:Driver={ODBC Driver 17 for SQL Server};Server={$host};Database={$name}",
                        $user,
                        $pass
                    );
                    break;
            }

            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

        } catch (PDOException $e) {
            $this->throwDbException($e);
        }
    }

    /* ============================================================
     *  SELECT
     * ============================================================ */

    public function select(string $sql, array $params = [], int $fetchMode = PDO::FETCH_ASSOC)
    {
        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll($fetchMode);
        } catch (PDOException $e) {
            $this->throwDbException($e);
        }
    }

    /* ============================================================
     *  INSERT
     * ============================================================ */

    public function save(string $table, array $data): int|string
    {
        ksort($data);

        $tableQ = $this->quoteTable($table);
        $fields = implode(', ', array_map([$this, 'quoteField'], array_keys($data)));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$tableQ} ({$fields}) VALUES ({$placeholders})";

        try {
            $stmt = $this->prepare($sql);
            $stmt->execute($data);
            return $this->lastInsertId();
        } catch (PDOException $e) {
            $this->throwDbException($e);
        }
    }

    /* ============================================================
     *  UPDATE (by primary key)
     * ============================================================ */

    public function update(string $table, array $data, $id, string $key = 'id'): bool
    {
        ksort($data);

        $tableQ = $this->quoteTable($table);

        $fields = [];
        foreach ($data as $k => $v) {
            $fields[] = $this->quoteField($k) . " = :$k";
        }

        $sql = "UPDATE {$tableQ} SET " . implode(', ', $fields) .
            " WHERE " . $this->quoteField($key) . " = :_id";

        $data['_id'] = $id;

        try {
            $stmt = $this->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            $this->throwDbException($e);
        }
    }

    /* ============================================================
     *  UPDATE (multiple conditions)
     * ============================================================ */

    public function updateFiltered(string $table, array $data, array $where): bool
    {
        ksort($data);

        $tableQ = $this->quoteTable($table);

        $fields = [];
        foreach ($data as $k => $v) {
            $fields[] = $this->quoteField($k) . " = :$k";
        }

        $conditions = [];
        foreach ($where as $k => $v) {
            $conditions[] = $this->quoteField($k) . " = :w_$k";
            $data["w_$k"] = $v;
        }

        $sql = "UPDATE {$tableQ} SET " . implode(', ', $fields) .
            " WHERE " . implode(' AND ', $conditions);

        try {
            $stmt = $this->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            $this->throwDbException($e);
        }
    }

    /* ============================================================
     *  DRIVER DETECTION
     * ============================================================ */

    private function dbType(): string
    {
        return defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'sqlsrv');
    }

    /* ============================================================
     *  IDENTIFIER SAFETY (tables/columns)
     * ============================================================ */

    private function assertValidIdentifier(string $name): void
    {
        // allow schema.name (dbo.Table) and spaces (for aliases like "Column Name")
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_ ]*(\.[A-Za-z_][A-Za-z0-9_ ]*)?$/', $name)) {
            throw new \InvalidArgumentException("Invalid SQL identifier: {$name}");
        }
    }

    public function quoteTable(string $table): string
    {
        $this->assertValidIdentifier($table);

        if (str_contains($table, '.')) {
            [$schema, $tbl] = explode('.', $table, 2);

            return $this->dbType() === 'mysql'
                ? "`{$schema}`.`{$tbl}`"
                : "[{$schema}].[{$tbl}]";
        }

        return $this->dbType() === 'mysql'
            ? "`{$table}`"
            : "[{$table}]";
    }

    public function quoteColumn(string $column): string
    {
        $this->assertValidIdentifier($column);

        // allow schema.col (rare for columns, but safe to support)
        if (str_contains($column, '.')) {
            [$left, $right] = explode('.', $column, 2);

            return $this->dbType() === 'mysql'
                ? "`{$left}`.`{$right}`"
                : "[{$left}].[{$right}]";
        }

        return $this->dbType() === 'mysql'
            ? "`{$column}`"
            : "[{$column}]";
    }


    /* ============================================================
     *  FIELD ESCAPING
     * ============================================================ */

    private function quoteField(string $field): string
    {
        return $this->quoteColumn($field);
    }

    /* ============================================================
     *  EXCEPTION BRIDGE (PDO → Zantech)
     * ============================================================ */

    private function throwDbException(PDOException $e): void
    {
        // 1. Try to get native code from errorInfo (most reliable for sqlsrv)
        $code = (int)($e->errorInfo[1] ?? 0);

        // 2. Fallback to parsing message for SQL Server specific code if errorInfo is empty
        if ($code === 0) {
            preg_match('/\[SQL Server\](.*?Error: )?(\d+)/', $e->getMessage(), $m);
            $code = (int)($m[2] ?? 0);
        }

        // 3. Last fallback: search for ANY number after SQLSTATE or in brackets
        if ($code === 0) {
            preg_match('/SQLSTATE\[\d+\]:.*?(\d+)/', $e->getMessage(), $m);
            $code = (int)($m[1] ?? 0);
        }

        [$public, $status] = DatabaseErrorMap::resolve($code);

        throw new DatabaseException(
            $e->getMessage(),
            $public,
            $status,
            [
                'sql_code' => $code,
                'driver'   => defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'unknown'),
            ],
            $e
        );
    }

}
