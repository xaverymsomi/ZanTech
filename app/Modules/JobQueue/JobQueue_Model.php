<?php

namespace Modules\JobQueue;

use Database\Model;

/**
 * JobQueue_Model
 */
class JobQueue_Model extends Model
{
    protected string $table = "mx_job_queue";
    protected string $title = "Job Queue";
    protected string $title_plural = "Job Queues";
    protected string $parent_key = "id";

    public function getHiddenFields(): array
    {
        return [
            'id', 'payload', 'error_message'
        ];
    }

    public function getFormHiddenFields(): array
    {
        return [];
    }

    public function getControls(): array
    {
        return [];
    }

    public function getActions(): array
    {
        return [
            [
                "action" => "View_JobQueue",
                "name" => "View Details",
                "icon" => "fa-eye",
                "color" => "blue",
                "url" => "JobQueue",
                "disabled" => []
            ],
            [
                "action" => "Retry_JobQueue",
                "name" => "Retry Job",
                "icon" => "fa-sync",
                "color" => "success",
                "url" => "JobQueue",
                "disabled" => [
                    'NOT' => [
                        'status' => ['failed', 'error']
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
