<?php

namespace Modules\AuditTrail;

use Foundation\BaseModuleController;

/**
 * AuditTrail Module
 * View-only module for system audit logs.
 */
class AuditTrail extends BaseModuleController
{
    public function profile($id): void
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = 'view_audit_trail';
        $extra_data = [];
        parent::getProfile($record_id, $permission, $extra_data);
    }

    public function associated_records($id, $caller): void
    {
        $call_mappers = [];
        $permission = 'view_audit_trail';
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $valid_caller = filter_var($caller, FILTER_SANITIZE_SPECIAL_CHARS);
        parent::getAssociatedRecords($record_id, $valid_caller, $call_mappers, $permission);
    }
}
