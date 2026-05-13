<?php

declare(strict_types=1);

namespace Database;

final class DB
{
    private static ?Database $db = null;

    private static function instance(): Database
    {
        if (self::$db === null) {
            self::$db = new Database();
        }
        return self::$db;
    }

    public static function connection(): Database
    {
        return self::instance();
    }

    public static function table(string $table): QueryBuilder
    {
        return new QueryBuilder(self::instance(), $table);
    }

    public static function select(string $sql, array $params = []): array
    {
        return self::instance()->select($sql, $params);
    }

    public static function statement(string $sql, array $params = []): bool
    {
        $stmt = self::instance()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function transaction(callable $callback): mixed
    {
        $db = self::instance();
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
