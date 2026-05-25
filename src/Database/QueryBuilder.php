<?php

namespace Database;

class QueryBuilder
{
    protected Database $db;
    protected string $table;
    protected array $wheres = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [];
    protected int $bindCount = 0;

    public function __construct(Database $db, string $table)
    {
        $this->db = $db;
        $this->table = $table;
    }

    private function dbType(): string
    {
        return method_exists($this->db, 'getDriverType') ? $this->db->getDriverType() : (defined('DB_TYPE') ? DB_TYPE : 'sqlsrv');
    }

    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $placeholder = ":w_" . str_replace(['.', ' '], '_', $column) . "_" . (++$this->bindCount);
        $this->wheres[] = [
            'type' => 'AND',
            'column' => $column,
            'operator' => $operator,
            'placeholder' => $placeholder
        ];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function orWhere(string $column, mixed $value, string $operator = '='): self
    {
        $placeholder = ":w_" . str_replace(['.', ' '], '_', $column) . "_" . (++$this->bindCount);
        $this->wheres[] = [
            'type' => 'OR',
            'column' => $column,
            'operator' => $operator,
            'placeholder' => $placeholder
        ];
        $this->bindings[$placeholder] = $value;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = ['column' => $column, 'direction' => strtoupper($direction)];
        return $this;
    }

    public function limit(int $count): self
    {
        $this->limit = $count;
        return $this;
    }

    public function offset(int $count): self
    {
        $this->offset = $count;
        return $this;
    }

    public function get(): array
    {
        $sql = $this->toSql();
        return $this->db->select($sql, $this->bindings);
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    public function update(array $data): bool
    {
        $tableQ = $this->db->quoteTable($this->table);
        $fields = [];
        foreach ($data as $k => $v) {
            $placeholder = ":u_" . str_replace(['.', ' '], '_', $k);
            $fields[] = $this->db->quoteColumn($k) . " = {$placeholder}";
            $this->bindings[$placeholder] = $v;
        }

        $sql = "UPDATE {$tableQ} SET " . implode(', ', $fields);

        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            foreach ($this->wheres as $i => $w) {
                if ($i > 0) $sql .= " " . $w['type'] . " ";
                $sql .= $this->db->quoteColumn($w['column']) . " " . $w['operator'] . " " . $w['placeholder'];
            }
        }

        return $this->db->prepare($sql)->execute($this->bindings);
    }

    public function delete(): bool
    {
        $tableQ = $this->db->quoteTable($this->table);
        $sql = "DELETE FROM {$tableQ}";

        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            foreach ($this->wheres as $i => $w) {
                if ($i > 0) $sql .= " " . $w['type'] . " ";
                $sql .= $this->db->quoteColumn($w['column']) . " " . $w['operator'] . " " . $w['placeholder'];
            }
        }

        return $this->db->prepare($sql)->execute($this->bindings);
    }

    public function toSql(): string
    {
        $sql = "SELECT * FROM " . $this->db->quoteTable($this->table);

        if (!empty($this->wheres)) {
            $sql .= " WHERE ";
            foreach ($this->wheres as $i => $w) {
                if ($i > 0) $sql .= " " . $w['type'] . " ";
                $sql .= $this->db->quoteColumn($w['column']) . " " . $w['operator'] . " " . $w['placeholder'];
            }
        }

        if (!empty($this->orders)) {
            $sql .= " ORDER BY ";
            $orderParts = [];
            foreach ($this->orders as $o) {
                $orderParts[] = $this->db->quoteColumn($o['column']) . " " . $o['direction'];
            }
            $sql .= implode(', ', $orderParts);
        }

        if ($this->limit !== null) {
            // SQL Server uses TOP or OFFSET/FETCH
            if ($this->dbType() === 'sqlsrv' || $this->dbType() === 'odbc') {
                if ($this->offset !== null) {
                    $sql .= " OFFSET {$this->offset} ROWS FETCH NEXT {$this->limit} ROWS ONLY";
                } else {
                    // TOP must be at the start, so we replace SELECT * with SELECT TOP n *
                    $sql = str_replace("SELECT *", "SELECT TOP {$this->limit} *", $sql);
                }
            } else {
                $sql .= " LIMIT {$this->limit}";
                if ($this->offset !== null) {
                    $sql .= " OFFSET {$this->offset}";
                }
            }
        }

        return $sql;
    }
}
