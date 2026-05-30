<?php

namespace Modules\UserGroup;

use Database\Model;

/**
 * UserGroup_Model
 */
class UserGroup_Model extends Model
{
    protected string $table = "mx_group";
    protected string $title = "User Group";
    protected string $title_plural = "User Groups";
    protected string $parent_key = "group_id";

    public function getHiddenFields(): array
    {
        return [
            'id', 'dat_date_added', 'int_added_by', 'Status', 'txt_row_value', 'row_id', 'status_id'
        ];
    }

    public function getFormHiddenFields(): array
    {
        return array('id');
    }

    public function getControls(): array
    {
        return [
            [
                'action' => 'create',
                'color' => 'success',
                'title' => 'Add New Group',
                "permission" => "add_user_group",
                'name' => 'New Group',
                'url' => "'UserGroup'"
            ]
        ];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "Edit_UserGroup",
                "name" => "Edit",
                "icon" => "fa-edit",
                "color" => "blue",
                "url" => "UserGroup",
                "disabled" => [
                    'OR' => [
                        'opt_mx_status_id' => [4]
                    ]
                ]
            ],
            [
                "action" => "Delete_UserGroup",
                "name" => "Delete",
                "icon" => "fa-trash",
                "color" => "danger",
                "url" => "UserGroup",
                "disabled" => [
                    'OR' => [
                        'id' => [1, 2, 3] // usually protect root/admin groups
                    ]
                ]
            ]
        ];
    }

    public function getProfileButtons(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [];
    }

    public function getProfileHiddenColumns(): array
    {
        return ["id", 'txt_added_by', 'txt_row_value', 'row_id', 'status_id'];
    }

    public function getTable($view_table = false): string
    {
        return $this->table;
    }

    public function getTitle($plural = false): string
    {
        if ($plural) {
            return $this->title_plural;
        }
        return $this->title;
    }

    public function getParentKey(): string
    {
        return $this->parent_key;
    }

    public function getFormDropdowns(): array
    {
        return [];
    }

    public function getAssociatedRecordActions($caller): array
    {
        return [];
    }

    public function getInputFilters(): array
    {
        return [
            "id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 1,
                    "max_range" => 2147483647
                ]
            ],
            "txt_name" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_color" => FILTER_SANITIZE_SPECIAL_CHARS
        ];
    }

    public function getTableLabels(): array
    {
        $labels = [
            'opt_mx_status_id' => [
                'query' => "SELECT id, txt_name, txt_color FROM mx_status",
                'key' => "id",
                'value' => "txt_name",
                'color' => 'txt_color'
            ]
        ];
        return parent::generateTableLabels($labels);
    }
}
