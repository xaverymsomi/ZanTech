<?php

namespace Database\Model\Concerns;

trait HasRelationships
{
    /**
     * Gets records from an associated view/table.
     */
    public function getAssociatedRecords($id, string $table, string $parent, array $hidden = []): array
    {
        $view_suffix = str_contains($table, '_view') ? '' : '_view';
        $viewName = $table . $view_suffix;

        $cols = $this->getTableColumns($viewName, $hidden);
        if (empty($cols)) return [];

        $tableQ = $this->db->quoteTable($viewName);
        $parentQ = $this->db->quoteColumn($parent);

        $selectCols = [];
        foreach ($cols as $c) {
            $selectCols[] = $this->db->quoteColumn($c);
        }

        $sql = "SELECT " . implode(',', $selectCols) .
            " FROM {$tableQ} WHERE {$parentQ} = :id ORDER BY " . $this->db->quoteColumn('id') . " DESC";

        return $this->db->select($sql, [':id' => $id]) ?: [];
    }

    public function getTableColumns(string $table, array $hidden = []): array
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            return [];
        }

        $driver = defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'mysql');

        $sql = match ($driver) {
            'mysql' => "SELECT COLUMN_NAME AS col
                          FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_NAME = :t
                           AND TABLE_SCHEMA = :s",
            'sqlsrv', 'odbc' => "SELECT COLUMN_NAME AS col
                                  FROM INFORMATION_SCHEMA.COLUMNS
                                 WHERE TABLE_NAME = :t",
            default => ""
        };

        if ($sql === '') return [];

        $params = [':t' => $table];
        if ($driver === 'mysql') {
            $params[':s'] = defined('DB_NAME') ? DB_NAME : (string)($_ENV['DB_NAME'] ?? '');
        }

        $result = $this->db->select($sql, $params);
        $out = [];

        foreach ($result as $r) {
            $c = (string)($r['col'] ?? '');
            if ($c === '') continue;
            if (in_array($c, $hidden, true)) continue;
            $out[] = $c;
        }

        return $out;
    }
}
