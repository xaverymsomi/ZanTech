<?php

namespace Database;

use PDO;
use PDOException;
use Exceptions\DatabaseException;

class Database extends PDO
{
    private string $connectionName = 'default';
    private string $resolvedType = 'sqlsrv';

    public function __construct(string|array $config = 'default')
    {
        try {
            if (is_string($config)) {
                $this->connectionName = $config;
                if ($config === 'default') {
                    $type = defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'sqlsrv');
                    $host = defined('DB_HOST') ? DB_HOST : ($_ENV['DB_HOST'] ?? '');
                    $name = defined('DB_NAME') ? DB_NAME : ($_ENV['DB_NAME'] ?? '');
                    $user = defined('DB_USER') ? DB_USER : ($_ENV['DB_USER'] ?? '');
                    $pass = defined('DB_PASS') ? DB_PASS : ($_ENV['DB_PASS'] ?? '');
                } else {
                    $prefix = strtoupper($config);
                    $type = $_ENV["DB_{$prefix}_TYPE"] ?? getenv("DB_{$prefix}_TYPE") ?: (defined('DB_TYPE') ? DB_TYPE : 'sqlsrv');
                    $host = $_ENV["DB_{$prefix}_HOST"] ?? getenv("DB_{$prefix}_HOST") ?: '';
                    $name = $_ENV["DB_{$prefix}_NAME"] ?? getenv("DB_{$prefix}_NAME") ?: '';
                    $user = $_ENV["DB_{$prefix}_USER"] ?? getenv("DB_{$prefix}_USER") ?: '';
                    $pass = $_ENV["DB_{$prefix}_PASS"] ?? getenv("DB_{$prefix}_PASS") ?: '';
                }
            } else {
                $type = $config['type'] ?? 'sqlsrv';
                $host = $config['host'] ?? '';
                $name = $config['name'] ?? '';
                $user = $config['user'] ?? '';
                $pass = $config['pass'] ?? '';
            }

            $this->resolvedType = strtolower($type);

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

                case 'pgsql':
                    $port = defined('DB_PORT') ? DB_PORT : ($_ENV['DB_PORT'] ?? '5432');
                    parent::__construct(
                        "pgsql:host={$host};port={$port};dbname={$name}",
                        $user,
                        $pass
                    );
                    break;

                case 'sqlite':
                    // For SQLite, host can specify the filepath or ':memory:'
                    parent::__construct(
                        "sqlite:{$host}",
                        null,
                        null
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
            $this->executeAndProfile($stmt, $params, $sql);
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
        
        $fields = [];
        $placeholders = [];
        $bindings = [];
        foreach ($data as $k => $v) {
            $fields[] = $this->quoteField($k);
            $placeholder = ':' . str_replace(['.', ' ', '-'], '_', $k);
            $placeholders[] = $placeholder;
            $bindings[$placeholder] = $v;
        }

        $fieldsStr = implode(', ', $fields);
        $placeholdersStr = implode(', ', $placeholders);

        $sql = "INSERT INTO {$tableQ} ({$fieldsStr}) VALUES ({$placeholdersStr})";

        try {
            $stmt = $this->prepare($sql);
            $this->executeAndProfile($stmt, $bindings, $sql);
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
        $bindings = [];
        foreach ($data as $k => $v) {
            $placeholder = ':' . str_replace(['.', ' ', '-'], '_', $k);
            $fields[] = $this->quoteField($k) . " = {$placeholder}";
            $bindings[$placeholder] = $v;
        }

        $sql = "UPDATE {$tableQ} SET " . implode(', ', $fields) .
            " WHERE " . $this->quoteField($key) . " = :_id";

        $bindings[':_id'] = $id;

        try {
            $stmt = $this->prepare($sql);
            return $this->executeAndProfile($stmt, $bindings, $sql);
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
        $bindings = [];
        foreach ($data as $k => $v) {
            $placeholder = ':' . str_replace(['.', ' ', '-'], '_', $k);
            $fields[] = $this->quoteField($k) . " = {$placeholder}";
            $bindings[$placeholder] = $v;
        }

        $conditions = [];
        foreach ($where as $k => $v) {
            $placeholder = ':w_' . str_replace(['.', ' ', '-'], '_', $k);
            $conditions[] = $this->quoteField($k) . " = {$placeholder}";
            $bindings[$placeholder] = $v;
        }

        $sql = "UPDATE {$tableQ} SET " . implode(', ', $fields) .
            " WHERE " . implode(' AND ', $conditions);

        try {
            $stmt = $this->prepare($sql);
            return $this->executeAndProfile($stmt, $bindings, $sql);
        } catch (PDOException $e) {
            $this->throwDbException($e);
        }
    }

    /* ============================================================
     *  EXECUTION PROFILE & HEAVY QUERY TRACING
     * ============================================================ */

    private function executeAndProfile(\PDOStatement $stmt, array $params = [], string $sql = ''): bool
    {
        $start = microtime(true);
        $result = $stmt->execute($params);
        $elapsedMs = (microtime(true) - $start) * 1000;

        if (class_exists('\\Foundation\\Profiler')) {
            \Foundation\Profiler::recordQuery($sql, $params, $elapsedMs);
        }

        $threshold = (float) \Config\Config::get('ZT_SLOW_QUERY_THRESHOLD', 100); // 100ms slow-query threshold default
        if ($elapsedMs >= $threshold) {
            \Logging\Log::custom_log('db', 'slow', [
                'sql'         => $sql,
                'params'      => $params,
                'duration_ms' => round($elapsedMs, 2),
                'threshold'   => $threshold,
            ]);
        }

        return $result;
    }

    /* ============================================================
     *  DRIVER DETECTION
     * ============================================================ */

    private function dbType(): string
    {
        return $this->resolvedType;
    }

    public function getDriverType(): string
    {
        return $this->resolvedType;
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

            if ($this->dbType() === 'mysql') {
                return "`{$schema}`.`{$tbl}`";
            } elseif ($this->dbType() === 'sqlite' || $this->dbType() === 'pgsql') {
                return "\"{$schema}\".\"{$tbl}\"";
            } else {
                return "[{$schema}].[{$tbl}]";
            }
        }

        if ($this->dbType() === 'mysql') {
            return "`{$table}`";
        } elseif ($this->dbType() === 'sqlite' || $this->dbType() === 'pgsql') {
            return "\"{$table}\"";
        } else {
            return "[{$table}]";
        }
    }

    public function quoteColumn(string $column): string
    {
        $this->assertValidIdentifier($column);

        // allow schema.col (rare for columns, but safe to support)
        if (str_contains($column, '.')) {
            [$left, $right] = explode('.', $column, 2);

            if ($this->dbType() === 'mysql') {
                return "`{$left}`.`{$right}`";
            } elseif ($this->dbType() === 'sqlite' || $this->dbType() === 'pgsql') {
                return "\"{$left}\".\"{$right}\"";
            } else {
                return "[{$left}].[{$right}]";
            }
        }

        if ($this->dbType() === 'mysql') {
            return "`{$column}`";
        } elseif ($this->dbType() === 'sqlite' || $this->dbType() === 'pgsql') {
            return "\"{$column}\"";
        } else {
            return "[{$column}]";
        }
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
