<?php

namespace Database\Model\Concerns;

trait HasAttributes
{
    /**
     * Converts DB column names to human-readable labels.
     */
    public function cleanTableColumnName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        if (strtolower($name) === 'email') {
            return 'Email';
        }

        $prefixes = ['dat_', 'tim_', 'txt_', 'int_', 'dbl_', 'opt_', 'tar_', 'bit_'];
        foreach ($prefixes as $p) {
            if (str_starts_with($name, $p)) {
                $name = substr($name, strlen($p));
                break;
            }
        }

        if (str_starts_with($name, 'mx_')) {
            $name = substr($name, 3);
        }

        if (str_ends_with($name, '_id')) {
            $name = substr($name, 0, -3);
        }

        $name = str_replace('_', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return ucwords(strtolower(trim($name)));
    }

    public function getClassFields(string $table): array
    {
        $columns = $this->getTableColumnNames($table);
        $object_name = get_called_class();
        $object = new $object_name();
        $hidden = method_exists($object, 'getHiddenFields') ? $object->getHiddenFields() : [];
        
        $properties = [];
        foreach ($columns as $col) {
            if (in_array($col, $hidden, true)) continue;
            $label = $this->cleanTableColumnName($col);
            $properties[] = ['column' => $col, 'label' => $label];
        }
        
        return ['properties' => $properties];
    }

    protected function isDateOrTimeColumn(string $col): bool
    {
        return str_starts_with($col, 'dat_') || str_starts_with($col, 'tim_');
    }

    protected function findFirstSearchableColumnIndex(array $columns): int
    {
        foreach ($columns as $idx => $col) {
            if ($this->isDateOrTimeColumn($col)) {
                continue;
            }
            return $idx;
        }
        return 0;
    }
}
