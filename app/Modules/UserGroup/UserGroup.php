<?php

namespace Modules\UserGroup;

use Foundation\BaseModuleController;
use Authentication\Gate;
use Exception;
use Logging\Log;

/**
 * UserGroup Module
 * Handles management of User Groups (Roles).
 */
class UserGroup extends BaseModuleController
{
    protected array $dualControl = ['delete'];

    public function profile($id): void
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = 'view_user_groups';
        $extra_data = [];
        parent::getProfile($record_id, $permission, $extra_data);
    }

    public function associated_records($id, $caller): void
    {
        $call_mappers = [];
        $permission = 'view_user_groups';
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $valid_caller = filter_var($caller, FILTER_SANITIZE_SPECIAL_CHARS);
        parent::getAssociatedRecords($record_id, $valid_caller, $call_mappers, $permission);
    }

    public function save()
    {
        $posted_data = $this->request()->all();
        $id       = isset($posted_data['id']) ? (int)$posted_data['id'] : 0;
        $name     = isset($posted_data['txt_name']) ? trim((string)$posted_data['txt_name']) : '';
        $color    = isset($posted_data['txt_color']) ? trim((string)$posted_data['txt_color']) : '#000000';

        if ($name === '') {
            return $this->responseError("Group name is required.", 400);
        }

        try {
            if ($id > 0) {
                // UPDATE FLOW
                $conflict = $this->model->db->select("SELECT id FROM mx_group WHERE txt_name = :name AND id != :id", [
                    ':name' => $name,
                    ':id'   => $id
                ]);
                if (!empty($conflict)) {
                    return $this->responseError("A group with this name already exists.");
                }

                $this->model->updateRecord([
                    'txt_name'  => $name,
                    'txt_color' => $color
                ], 'mx_group', $id);

                Log::sysLog("UserGroup ID={$id} updated successfully by Admin ID=" . ($_SESSION['id'] ?? 'System'));
                return $this->responseSuccess(200, "Group updated successfully.");
            } else {
                // CREATE FLOW
                $conflict = $this->model->db->select("SELECT id FROM mx_group WHERE txt_name = :name", [':name' => $name]);
                if (!empty($conflict)) {
                    return $this->responseError("A group with this name already exists.");
                }

                $newId = $this->model->create([
                    'txt_name'          => $name,
                    'txt_color'         => $color,
                    'opt_mx_status_id'  => 1, // Active status
                    'int_added_by'      => $_SESSION['id'] ?? 1
                ], 'mx_group');

                Log::sysLog("UserGroup ID={$newId} successfully created by Admin ID=" . ($_SESSION['id'] ?? 'System'));
                return $this->responseSuccess(201, "Group created successfully.");
            }
        } catch (Exception $ex) {
            Log::sysLog("UserGroup saving error: " . $ex->getMessage());
            return $this->responseError("System error occurred: " . $ex->getMessage());
        }
    }
}
