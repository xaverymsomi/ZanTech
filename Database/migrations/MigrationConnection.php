<?php

namespace Database\Migrations;

interface MigrationConnection
{
    public function exec(string $statement): mixed;

    public function query(string $query, ?int $fetchMode = null): mixed;

    public function prepare(string $query, array $options = []): mixed;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollBack(): bool;

    public function inTransaction(): bool;

    public function quoteTable(string $table): string;

    public function quoteColumn(string $column): string;
}
