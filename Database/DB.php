<?php

namespace Database;

final class DB
{
    /** @var array<string, Database> Connection pool */
    private static array $connections = [];

    /**
     * Resolve a named database connection from the pool.
     */
    public static function connection(string $name = 'default'): Database
    {
        if (!isset(self::$connections[$name])) {
            self::$connections[$name] = new Database($name);
        }
        return self::$connections[$name];
    }

    /**
     * Get a QueryBuilder instance for a specific table and connection.
     */
    public static function table(string $table, string $connection = 'default'): QueryBuilder
    {
        return new QueryBuilder(self::connection($connection), $table);
    }

    /**
     * Execute a SQL query on a specific connection.
     */
    public static function select(string $sql, array $params = [], string $connection = 'default'): array
    {
        return self::connection($connection)->select($sql, $params);
    }

    /**
     * Execute a raw SQL statement on a specific connection.
     */
    public static function statement(string $sql, array $params = [], string $connection = 'default'): bool
    {
        $stmt = self::connection($connection)->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Execute a closure callback within a transaction on a specific connection.
     */
    public static function transaction(callable $callback, string $connection = 'default'): mixed
    {
        $db = self::connection($connection);
        try {
            $db->beginTransaction();
            $result = $callback($db);
            $db->commit();
            return $result;
        } catch (\Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
