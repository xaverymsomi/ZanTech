<?php

namespace Modules\AuditTrail;

use Database\Model;

/**
 * AuditTrail_Model
 */
class AuditTrail_Model extends Model
{
    protected string $table = "mx_audit_trail";
    protected string $title = "Audit Trail";
    protected string $title_plural = "Audit Trails";
    protected string $parent_key = "id";

    public function getHiddenFields(): array
    {
        return [
            'id', 'opt_mx_login_credential_id', 'txt_payload', 'txt_request_id'
        ];
    }

    public function getFormHiddenFields(): array
    {
        return [];
    }

    public function getControls(): array
    {
        // Audit trails are view-only
        return [];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "View_AuditTrail",
                "name" => "View Details",
                "icon" => "fa-eye",
                "color" => "blue",
                "url" => "AuditTrail",
                "disabled" => []
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
        return ["id"];
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
        return [];
    }

    public function getTableLabels(): array
    {
        return [];
    }
}
