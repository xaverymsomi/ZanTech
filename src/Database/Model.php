<?php

namespace Database;

use Database\Database;
use Database\DB;
use Logging\Log;
use PDO;
use Throwable;

use Database\Model\Concerns\HasAttributes;
use Database\Model\Concerns\HasDataTable;
use Database\Model\Concerns\HasRelationships;

abstract class Model
{
    // HasQueryBuilder removed — no module ever calls query()/find()/all();
    // raw $this->db->select() and getAllRecords() are the actual usage pattern.
    use HasAttributes, HasDataTable, HasRelationships;

    public string $sql = '';
    public Database $db;

    /** @var string[] */
    private array $columns = [];

    /** @var string[] */
    private array $whereParts = [];

    private array $bindings = [];
    private int $bindCounter = 0;

    // Builder state
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private ?string $table_builder = null;

    /** @var string Database connection name */
    protected string $connection = 'default';

    /** @var string Primary data table */
    protected string $table = '';

    /** @var string Optimized read-only view */
    protected string $view_table = '';

    /** @var string Human readable name */
    protected string $title = '';

    /** @var array Database table column definitions (for auto-migrations) */
    protected array $schema = [];

    public function getSchema(): array
    {
        return $this->schema;
    }

    /**
     * Change the model's database connection dynamically at runtime.
     */
    public function setConnection(string $connection): static
    {
        $this->connection = $connection;
        $this->db = DB::connection($connection);
        return $this;
    }

    /**
     * Executes queries defined in module models to generate mapped label definitions.
     */
    public function generateTableLabels(array $config): array
    {
        $results = [];
        foreach ($config as $field => $cfg) {
            if (!isset($cfg['query'], $cfg['key'], $cfg['value'])) {
                continue;
            }
            
            try {
                $data = $this->db->select($cfg['query']);
                $mapped = [];
                
                if ($data) {
                    foreach ($data as $row) {
                        $key = $row[$cfg['key']] ?? null;
                        if ($key !== null) {
                            $mapped[$key] = [
                                'label' => $row[$cfg['value']] ?? '',
                                'color' => $row[$cfg['color']] ?? 'default'
                            ];
                        }
                    }
                }
                $results[$field] = $mapped;
            } catch (\Throwable $e) {
                \Logging\Log::sysErr("generateTableLabels Query Failed for field [{$field}]: " . $e->getMessage());
                $results[$field] = [];
            }
        }
        return $results;
    }

    /**
     * Get the model's active database connection name.
     */
    public function getConnection(): string
    {
        return $this->connection;
    }

    public function __construct()
    {
        // Use the shared singleton — one PDO connection per request, not one per Model.
        $this->db = DB::connection($this->connection);
        $this->initializeMetadata();
    }

    /**
     * Automatically detect table and title if not explicitly set.
     */
    private function initializeMetadata(): void
    {
        if ($this->table === '') {
            $class = (new \ReflectionClass($this))->getShortName();
            $base = str_replace('_Model', '', $class);
            $this->table = 'mx_' . strtolower($base);
        }

        if ($this->view_table === '') {
            $this->view_table = $this->table . '_view';
        }

        if ($this->title === '') {
            $this->title = str_replace(['mx_', '_'], ['', ' '], $this->table);
            $this->title = ucwords(trim($this->title));
        }
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Unified Table Resolver
     * @param bool $view If true, returns the read-optimized view table
     */
    public function getTable(bool $view = false): string
    {
        // If a specific table was set via the Query Builder (->table('xyz')), use that.
        // Otherwise, use the model's defined table or view_table.
        $target = ($this->table_builder !== null) ? $this->table_builder : ($view ? $this->view_table : $this->table);
        
        if (empty($target)) {
            throw new \RuntimeException('No table defined for ' . get_class($this));
        }
        
        return $target;
    }

    public function getParentKey(): string
    {
        return str_replace('mx_', '', $this->table) . '_id';
    }

    /* ============================================================
     * Simple builder (safe)
     * ============================================================ */

    public function table(string $table): static
    {
        $this->table_builder = $table;
        return $this;
    }

    public function columns(array $columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Safe where: value is ALWAYS bound as parameter.
     * $join: 'AND' | 'OR' | '' (default AND when there is already a clause)
     */
    public function where(string $column, mixed $value, string $operator = '=', string $join = ''): static
    {
        $operator = strtoupper(trim($operator));
        $join = strtoupper(trim($join));

        $allowedOps = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'IN'];
        if (!in_array($operator, $allowedOps, true)) {
            throw new \InvalidArgumentException("Invalid operator: {$operator}");
        }

        if ($join !== '' && !in_array($join, ['AND', 'OR'], true)) {
            throw new \InvalidArgumentException("Invalid join: {$join}");
        }

        $colQ = $this->db->quoteColumn($column);

        // join handling
        $prefix = '';
        if (!empty($this->whereParts)) {
            $prefix = ($join !== '' ? " {$join} " : " AND ");
        }

        // IN support
        if ($operator === 'IN') {
            if (!is_array($value) || empty($value)) {
                throw new \InvalidArgumentException("IN requires a non-empty array value.");
            }

            $placeholders = [];
            foreach ($value as $v) {
                $ph = $this->nextBindName();
                $placeholders[] = $ph;
                $this->bindings[$ph] = $v;
            }

            $this->whereParts[] = $prefix . "{$colQ} IN (" . implode(',', $placeholders) . ")";
            return $this;
        }

        $ph = $this->nextBindName();
        $this->bindings[$ph] = $value;

        $this->whereParts[] = $prefix . "{$colQ} {$operator} {$ph}";
        return $this;
    }

    public function whereRaw(string $sqlFragment, string $join = 'AND'): static
    {
        $join = strtoupper(trim($join));
        if ($join !== '' && !in_array($join, ['AND', 'OR'], true)) {
            throw new \InvalidArgumentException("Invalid join: {$join}");
        }

        $prefix = '';
        if (!empty($this->whereParts)) {
            $prefix = " {$join} ";
        }

        $this->whereParts[] = $prefix . $sqlFragment;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = $this->db->quoteColumn($column) . " {$direction}";
        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;
        return $this;
    }

    /* ============================================================
     * Builder Execution
     * ============================================================ */

    /**
     * Execute and get all matching records.
     */
    public function get(): array
    {
        return $this->getAllRecordsFiltered($this->getTable());
    }

    /**
     * Execute and get the first matching record.
     */
    public function first(): array
    {
        $this->limit(1);
        $res = $this->get();
        return $res[0] ?? [];
    }

    /**
     * Get record count matching the criteria.
     */
    public function count(): int
    {
        $tableQ = $this->db->quoteTable($this->getTable());
        $sql = "SELECT COUNT(*) as cnt FROM {$tableQ}";

        if (!empty($this->whereParts)) {
            $sql .= " WHERE " . implode('', $this->whereParts);
        }

        $result = $this->db->select($sql, $this->normalizeBindings());
        $this->clear();

        return (int)($result[0]['cnt'] ?? 0);
    }

    public function getAllRecordsFiltered(string $table): array
    {
        $tableQ = $this->db->quoteTable($table);
        $colsSql = $this->buildSelectColumnsSql();
        
        $sql = "SELECT {$colsSql} FROM {$tableQ}";

        if (!empty($this->whereParts)) {
            $sql .= " WHERE " . implode('', $this->whereParts);
        }

        if (!empty($this->orders)) {
            $sql .= " ORDER BY " . implode(', ', $this->orders);
        }

        // Pagination (Driver specific handled by DB if we used QueryBuilder, 
        // but here we are in Model so we add basic LIMIT for MySQL/SQLServer branching)
        if ($this->limit !== null) {
            $driver = method_exists($this->db, 'getDriverType') ? $this->db->getDriverType() : (defined('DB_TYPE') ? DB_TYPE : 'sqlsrv');
            if ($driver === 'sqlsrv' || $driver === 'odbc') {
                if ($this->offset !== null) {
                    $sql .= " OFFSET {$this->offset} ROWS FETCH NEXT {$this->limit} ROWS ONLY";
                } else {
                    $sql = str_replace("SELECT", "SELECT TOP {$this->limit}", $sql);
                }
            } else {
                $sql .= " LIMIT {$this->limit}";
                if ($this->offset !== null) {
                    $sql .= " OFFSET {$this->offset}";
                }
            }
        }

        $result = $this->db->select($sql, $this->normalizeBindings(), PDO::FETCH_ASSOC);
        $this->clear();
        return $result;
    }

    public function getRecordFiltered(string $table): array
    {
        $rows = $this->getAllRecordsFiltered($table);
        return $rows[0] ?? [];
    }

    /* ============================================================
     * Helpers
     * ============================================================ */

    private function buildSelectColumnsSql(): string
    {
        if (empty($this->columns)) return '*';

        $quoted = [];
        foreach ($this->columns as $c) {
            $quoted[] = $this->db->quoteColumn((string)$c);
        }
        return implode(', ', $quoted);
    }

    private function nextBindName(): string
    {
        $this->bindCounter++;
        return ':w' . $this->bindCounter;
    }

    private function normalizeBindings(): array
    {
        return $this->bindings;
    }

    private function clear(): void
    {
        $this->columns = [];
        $this->whereParts = [];
        $this->bindings = [];
        $this->bindCounter = 0;
        $this->orders = [];
        $this->limit = null;
        $this->offset = null;
        $this->sql = '';
    }

    /* ============================================================
     * LEGACY COMPATIBILITY HELPERS (SAFE)
     * ============================================================ */

    public static function generateRandomString(int $length = 8): string
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }
        return $randomString;
    }

    public function getRecord(int|string $id, string $table): array
    {
        $tableQ = $this->db->quoteTable($table);
        $sql = "SELECT * FROM {$tableQ} WHERE " . $this->db->quoteColumn('id') . " = :id";
        $result = $this->db->select($sql, [':id' => $id]);

        if (!empty($result)) {
            unset($result[0]['rowguid']);
            return (array)$result[0];
        }
        return [];
    }

    public function getRecordByRowValue(string $table, string $rowValue): array
    {
        $tableQ = $this->db->quoteTable($table);
        $sql = "SELECT * FROM {$tableQ} WHERE " . $this->db->quoteColumn('txt_row_value') . " = :v";
        $result = $this->db->select($sql, [':v' => $rowValue]);

        if (!empty($result)) {
            unset($result[0]['rowguid']);
            return (array)$result[0];
        }
        return [];
    }

    public function getRecordByFieldName(string $table, string $field_name, mixed $value, bool $single_record = false): array
    {
        $tableQ = $this->db->quoteTable($table);
        $colQ   = $this->db->quoteColumn($field_name);

        $sql = "SELECT * FROM {$tableQ} WHERE {$colQ} = :v";
        $result = $this->db->select($sql, [':v' => $value]);

        return !empty($result) ? (array)$result[0] : [];
    }

    public function getRecordsByFieldNames(string $table, array $field_names): array
    {
        if (empty($field_names)) return [];
        $tableQ = $this->db->quoteTable($table);
        $conditions = [];
        $params = [];
        $i = 0;
        foreach ($field_names as $field => $val) {
            $colQ = $this->db->quoteColumn((string)$field);
            $ph = ':p' . $i;
            $conditions[] = "{$colQ} = {$ph}";
            $params[$ph] = $val;
            $i++;
        }
        $sql = "SELECT * FROM {$tableQ} WHERE " . implode(' AND ', $conditions);
        return $this->db->select($sql, $params) ?: [];
    }

    public function getAllRecordByFieldName(string $table, string $field_name, mixed $value): array
    {
        $tableQ = $this->db->quoteTable($table);
        $colQ   = $this->db->quoteColumn($field_name);
        $sql = "SELECT * FROM {$tableQ} WHERE {$colQ} = :v";
        return $this->db->select($sql, [':v' => $value]) ?: [];
    }

    public function create(array $data, string $table): int|string
    {
        return $this->db->save($table, $data);
    }

    public function updateRecord(array $data, string $table, $id, string $key = 'id'): bool
    {
        return $this->db->update($table, $data, $id, $key);
    }

    /**
     * @deprecated Prefer updateRecord(); kept for module controllers that call update().
     */
    public function update(array $data, string $table, $id, string $key = 'id'): bool
    {
        return $this->updateRecord($data, $table, $id, $key);
    }

    public function deleteRecord(string $table, $id, string $key = 'id'): bool
    {
        $tableQ = $this->db->quoteTable($table);
        $keyQ   = $this->db->quoteColumn($key);
        $sql = "DELETE FROM {$tableQ} WHERE {$keyQ} = :id";
        return $this->db->prepare($sql)->execute([':id' => $id]);
    }

    /* ============================================================
     * ORM CONTRACT — required by all module controllers
     * ============================================================ */

    /**
     * Returns all column names for a given table/view.
     * Bridges HasDataTable/HasAttributes (which call this name)
     * with HasRelationships::getTableColumns() (the implementation).
     */
    public function getTableColumnNames(string $table): array
    {
        return $this->getTableColumns($table);
    }

    /**
     * Looks up the primary `id` of a record by its `txt_row_value` column.
     * Returns -1 when no record is found (expected by Controller::getProfile).
     */
    public function getRecordIdByRowValue(string $table, string $rowValue): int
    {
        $tableQ = $this->db->quoteTable($table);
        $sql    = "SELECT id FROM {$tableQ}"
                . " WHERE " . $this->db->quoteColumn('txt_row_value') . " = :rv";

        $result = $this->db->select($sql, [':rv' => $rowValue]);

        if (!empty($result) && isset($result[0]['id'])) {
            return (int) $result[0]['id'];
        }

        return -1;
    }

    /**
     * Fetches a single record by primary key for profile display.
     * Removes internal housekeeping columns (rowguid) before returning.
     */
    public function getProfileData(int $id, string $table): array
    {
        $tableQ = $this->db->quoteTable($table);
        $sql    = "SELECT * FROM {$tableQ}"
                . " WHERE " . $this->db->quoteColumn('id') . " = :id";

        $result = $this->db->select($sql, [':id' => $id]);

        if (empty($result)) {
            return [];
        }

        $row = (array) $result[0];
        unset($row['rowguid']); // remove SQL Server internal column

        return $row;
    }
}
