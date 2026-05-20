<?php

namespace Database\Model\Concerns;

use PDO;

trait HasDataTable
{
    /**
     * SAFE DATATABLE: getAllRecords()
     */
    public function getAllRecords(string $table, $model_class = null): array|string
    {
        try {
            $sort_column = 'id';
            $sort_order  = 'DESC';

            $requestData = $this->getArr($sort_column, $sort_order, $table);

            // ✅ validate + quote table
            $tableQ = $this->db->quoteTable($table);

            // ✅ real columns from schema (safe allowlist for ORDER/FILTER)
            $columns = $this->getTableColumnNames($table);

            if (empty($columns)) {
                return [[], [
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'recordsReturned' => 0,
                    'currentPage' => 1,
                    'totalPages' => 1,
                    'pageSize' => $requestData['length'],
                    'columns' => '[]',
                    'column_label' => 'ID',
                ], $requestData];
            }

            // ✅ validate ORDER BY column + direction
            [$orderCol, $orderDir] = $this->resolveSafeOrder(
                $requestData['order_column'] ?? $sort_column,
                $requestData['order_dir'] ?? $sort_order,
                $columns,
                $sort_column,
                $sort_order
            );

            // ✅ base WHERE + bindings
            $whereSql  = " WHERE 1 = 1 ";
            $bindings  = [];

            // ✅ Search (parameterized)
            $search = trim((string)($requestData['search'] ?? ''));
            if ($search !== '') {
                $schemaColumns = $this->getTableColumnNames($table);
                
                $searchableColumns = array_filter($columns, function($col) use ($schemaColumns) {
                    return in_array($col, $schemaColumns, true);
                });
                $searchableColumns = array_values($searchableColumns); 

                if (!empty($searchableColumns)) {
                    $firstColIndex = $this->findFirstSearchableColumnIndex($searchableColumns);

                    $like = '%' . $search . '%';
                    $whereSql .= " AND (";

                    $col0 = $searchableColumns[$firstColIndex];
                    $whereSql .= $this->db->quoteColumn($col0) . " LIKE :s0 ";
                    $bindings[':s0'] = $like;

                    $i = 1;
                    foreach ($searchableColumns as $idx => $col) {
                        if ($idx === $firstColIndex) continue;
                        if ($this->isDateOrTimeColumn($col)) continue;

                        $ph = ':s' . $i;
                        $whereSql .= " OR " . $this->db->quoteColumn($col) . " LIKE {$ph} ";
                        $bindings[$ph] = $like;
                        $i++;
                    }

                    $whereSql .= ")";
                }
            }

            // ✅ filterable=column, filter=value (validate column exists)
            if (!empty($requestData['filterable']) && ($requestData['filter'] ?? '') !== '') {
                $filterable = (string)$requestData['filterable'];
                $filterVal  = (string)$requestData['filter'];

                if (in_array($filterable, $columns, true)) {
                    $whereSql .= " AND " . $this->db->quoteColumn($filterable) . " = :flt ";
                    $bindings[':flt'] = $filterVal;
                }
            }

            // ✅ total records (no filters)
            $totalSql = "SELECT COUNT(*) AS c FROM {$tableQ}";
            $totalRes = $this->db->select($totalSql);
            $total_records = (int)($totalRes[0]['c'] ?? 0);

            // ✅ filtered count
            $countSql = "SELECT COUNT(*) AS c FROM {$tableQ} " . $whereSql;
            $countRes = $this->db->select($countSql, $bindings);
            $filtered_count = (int)($countRes[0]['c'] ?? 0);

            // ✅ data query + pagination
            $selectSql = "SELECT * FROM {$tableQ} " . $whereSql;
            $selectSql .= $this->buildOrderSql($table, $orderCol, $orderDir);
            $selectSql .= $this->buildPaginationSql((int)$requestData['start'], (int)$requestData['length']);

            $rows = $this->db->select($selectSql, $bindings);

            $returned_count = is_array($rows) ? count($rows) : 0;
            $total_pages = ($filtered_count === 0) ? 1 : (int)ceil($filtered_count / max(1, (int)$requestData['length']));

            // ✅ build columns metadata
            $object_name = get_called_class();
            $object = new $object_name();
            $hidden_columns = method_exists($object, 'getHiddenFields') ? $object->getHiddenFields() : [];

            $sort_column_label = 'ID';
            $colums_string = "[";
            foreach ($columns as $col) {
                if (in_array($col, $hidden_columns, true)) continue;
                if ($col === 'tim_transaction_time') continue;

                $label = $this->cleanTableColumnName($col);
                $colums_string .= "{'column':'{$col}','label':'{$label}'},";
                if ($orderCol === $col) {
                    $sort_column_label = $label;
                }
            }
            $colums_string = rtrim($colums_string, ",") . "]";

            return [
                $rows,
                [
                    'recordsTotal'    => $total_records,
                    'recordsFiltered' => $filtered_count,
                    'recordsReturned' => $returned_count,
                    'currentPage'     => ((int)$requestData['start']) + 1,
                    'totalPages'      => $total_pages,
                    'pageSize'        => (int)$requestData['length'],
                    'columns'         => $colums_string,
                    'column_label'    => $sort_column_label,
                ],
                $requestData
            ];
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    /**
     * SAFE DATATABLE: getFilteredRecords()
     */
    public function getFilteredRecords(string $table, array $filter, array $filter_value, array $filter_operators = []): array
    {
        $tableQ  = $this->db->quoteTable($table);
        $columns = $this->getTableColumnNames($table);

        $sort_column = 'id';
        $sort_order  = 'DESC';
        $requestData = $this->getArr($sort_column, $sort_order, $table);

        [$orderCol, $orderDir] = $this->resolveSafeOrder(
            $requestData['order_column'] ?? $sort_column,
            $requestData['order_dir'] ?? $sort_order,
            $columns,
            $sort_column,
            $sort_order
        );

        $whereSql = " WHERE 1 = 1 ";
        $bindings = [];

        for ($i = 0; $i < count($filter); $i++) {
            $col = (string)$filter[$i];
            $val = $filter_value[$i] ?? null;

            if (!in_array($col, $columns, true)) continue;

            $op = strtoupper(trim((string)($filter_operators[$i] ?? 'AND')));
            if ($i === 0) $op = 'AND';
            if (!in_array($op, ['AND', 'OR'], true)) $op = 'AND';

            $ph = ':f' . $i;

            if ($table === "mx_transaction" && $col === "opt_mx_service_id") {
                $serviceCategoryId = (int)$val;
                $whereSql .= " {$op} " . $this->db->quoteColumn($col) .
                    " IN (SELECT " . $this->db->quoteColumn('id') .
                    " FROM " . $this->db->quoteTable('mx_service') .
                    " WHERE " . $this->db->quoteColumn('opt_mx_service_category_id') . " = {$serviceCategoryId})";
                continue;
            }

            if ($table === "mx_transaction_list_view" && $col === "txt_description") {
                $whereSql .= " {$op} " . $this->db->quoteColumn($col) . " LIKE {$ph}";
                $bindings[$ph] = '%' . (string)$val . '%';
                continue;
            }

            $whereSql .= " {$op} " . $this->db->quoteColumn($col) . " = {$ph}";
            $bindings[$ph] = $val;
        }

        $search = trim((string)($requestData['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $whereSql .= " AND (";
            $j = 0;
            foreach ($columns as $col) {
                if ($this->isDateOrTimeColumn($col)) continue;
                $ph = ':s' . $j;
                $whereSql .= ($j === 0 ? '' : ' OR ') . $this->db->quoteColumn($col) . " LIKE {$ph}";
                $bindings[$ph] = $like;
                $j++;
            }
            $whereSql .= ")";
        }

        if (!empty($requestData['filterable']) && ($requestData['filter'] ?? '') !== '') {
            $filterable = (string)$requestData['filterable'];
            $filterVal  = (string)$requestData['filter'];

            if (in_array($filterable, $columns, true)) {
                $whereSql .= " AND " . $this->db->quoteColumn($filterable) . " = :flt ";
                $bindings[':flt'] = $filterVal;
            }
        }

        $countSql = "SELECT COUNT(*) AS c FROM {$tableQ} " . $whereSql;
        $countRes = $this->db->select($countSql, $bindings);
        $filtered_count = (int)($countRes[0]['c'] ?? 0);

        $sql = "SELECT * FROM {$tableQ} " . $whereSql;
        $sql .= $this->buildOrderSql($table, $orderCol, $orderDir);
        $sql .= $this->buildPaginationSql((int)$requestData['start'], (int)$requestData['length']);

        $rows = $this->db->select($sql, $bindings);
        $returned_count = is_array($rows) ? count($rows) : 0;
        $total_pages = ($filtered_count === 0) ? 1 : (int)ceil($filtered_count / max(1, (int)$requestData['length']));

        $object_name = get_called_class();
        $object = new $object_name();
        $hidden_columns = method_exists($object, 'getHiddenFields') ? $object->getHiddenFields() : [];

        $sort_column_label = 'ID';
        $column_string = "[";
        foreach ($columns as $col) {
            if (in_array($col, $hidden_columns, true)) continue;
            if ($col === 'tim_transaction_time') continue;

            $label = $this->cleanTableColumnName($col);
            $column_string .= "{'column':'{$col}','label':'{$label}'},";
            if ($orderCol === $col) {
                $sort_column_label = $label;
            }
        }
        $column_string = rtrim($column_string, ",") . "]";

        return [
            $rows,
            [
                'recordsTotal'    => $filtered_count,
                'recordsFiltered' => $filtered_count,
                'recordsReturned' => $returned_count,
                'currentPage'     => ((int)$requestData['start']) + 1,
                'totalPages'      => $total_pages,
                'pageSize'        => (int)$requestData['length'],
                'columns'         => $column_string,
                'column_label'    => $sort_column_label,
            ],
            $requestData
        ];
    }

    private function resolveSafeOrder(string $requestedCol, string $requestedDir, array $allowedCols, string $defaultCol, string $defaultDir): array 
    {
        $col = in_array($requestedCol, $allowedCols, true) ? $requestedCol : $defaultCol;
        $dir = strtoupper(trim($requestedDir));
        if (!in_array($dir, ['ASC', 'DESC'], true)) {
            $dir = strtoupper($defaultDir);
        }
        if (!in_array($col, $allowedCols, true)) {
            $col = $allowedCols[0] ?? $defaultCol;
        }
        return [$col, $dir];
    }

    private function buildOrderSql(string $table, string $orderCol, string $orderDir): string
    {
        if ($table === 'mx_transaction' && $orderCol === 'dat_transaction_date') {
            return " ORDER BY " .
                $this->db->quoteColumn($orderCol) . " {$orderDir}, " .
                $this->db->quoteColumn('tim_transaction_time') . " {$orderDir} ";
        }
        return " ORDER BY " . $this->db->quoteColumn($orderCol) . " {$orderDir} ";
    }

    private function buildPaginationSql(int $start, int $length): string
    {
        $start  = max(0, $start);
        $length = max(1, min(500, $length)); 
        $offset = $start;

        $driver = defined('DB_TYPE') ? DB_TYPE : ($_ENV['DB_TYPE'] ?? 'mysql');
        return match ($driver) {
            'mysql' => " LIMIT {$offset}, {$length} ",
            'sqlsrv', 'odbc' => " OFFSET {$offset} ROWS FETCH NEXT {$length} ROWS ONLY ",
            default => " ",
        };
    }

    public function getArr(string $sort_column, string $sort_order, $table): array
    {
        $requestData = [
            'search'       => '',
            'order_column' => $sort_column,
            'order_dir'    => $sort_order,
            'start'        => 0,
            'length'       => 25,
            'location'     => '',
            'current'      => 0,
            'filter'       => '',
            'filterable'   => '',
        ];

        if (isset($_REQUEST['search'])) {
            $requestData['search'] = filter_var((string)$_REQUEST['search'], FILTER_SANITIZE_SPECIAL_CHARS);
            $order = (string)($_REQUEST['order'] ?? $sort_column);
            $dir   = strtoupper((string)($_REQUEST['dir'] ?? $sort_order));
            $requestData['order_column'] = ($order === '' ? $sort_column : $order);
            $requestData['order_dir'] = in_array($dir, ['ASC', 'DESC'], true) ? $dir : $sort_order;
            $start  = (string)($_REQUEST['start'] ?? '0');
            $length = (int)($_REQUEST['length'] ?? 25);
            $requestData['start']  = ($start === 'undefined') ? 0 : (int)$start;
            $requestData['length'] = max(1, min(500, $length));
            $requestData['location'] = (string)($_REQUEST['loc'] ?? '');
            $requestData['current']  = (int)($_REQUEST['current'] ?? 0);
        }

        if (!empty($_REQUEST['filterable']) && !empty($_REQUEST['filter'])) {
            $requestData['filterable'] = filter_var((string)$_REQUEST['filterable'], FILTER_SANITIZE_SPECIAL_CHARS);
            $requestData['filter']     = filter_var((string)$_REQUEST['filter'], FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $requestData;
    }
}
