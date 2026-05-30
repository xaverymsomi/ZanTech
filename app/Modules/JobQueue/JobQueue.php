<?php

namespace Modules\JobQueue;

use Foundation\BaseModuleController;
use Logging\Log;

/**
 * JobQueue Module
 * View-only module to monitor the background job queue.
 */
class JobQueue extends BaseModuleController
{
    public function profile($id): void
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = 'view_job_queue';
        $extra_data = [];
        parent::getProfile($record_id, $permission, $extra_data);
    }

    public function associated_records($id, $caller): void
    {
        $call_mappers = [];
        $permission = 'view_job_queue';
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $valid_caller = filter_var($caller, FILTER_SANITIZE_SPECIAL_CHARS);
        parent::getAssociatedRecords($record_id, $valid_caller, $call_mappers, $permission);
    }

    public function retryJob()
    {
        $posted_data = $this->request()->all();
        $id = isset($posted_data['id']) ? (int)$posted_data['id'] : 0;

        if ($id > 0) {
            try {
                $this->model->updateRecord([
                    'status' => 'pending',
                    'attempts' => 0,
                    'error_message' => null
                ], 'mx_job_queue', $id);

                Log::sysLog("JobQueue ID={$id} retried by Admin ID=" . ($_SESSION['id'] ?? 'System'));
                return $this->responseSuccess(200, "Job queued for retry successfully.");
            } catch (\Exception $e) {
                return $this->responseError("Failed to retry job: " . $e->getMessage());
            }
        }
        return $this->responseError("Invalid job ID.", 400);
    }
}
