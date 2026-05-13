<?php

namespace Modules\Dashboard;

use Library\Model;

class Dashboard_Model extends Model
{
    private string $view_dir = "/dashboard/";
    private string $title = "Dashboard";
    private $title_plural;

    public function getTitle($plural = false): string
    {
        return $plural ? ($this->title_plural ?? $this->title . 's') : $this->title;
    }

    public function getViewDir(): string
    {
        return $this->view_dir;
    }

    public function getControls(): array
    {
        return [
            [
                'action' => 'New_Ticket',
                'color' => 'success',
                'title' => 'Create New Business',
                'name' => 'New Business',
                'url' => "'Dashboard'"
            ]
        ];
    }

    public function getFormDropdowns(): array
    {
        $data = [];
        $result = $this->db->select("SELECT txt_row_value, txt_name FROM mx_stakeholder ORDER BY id ASC");

        if ($result) {
            foreach ($result as $value) {
                $item = ['id' => $value['txt_row_value'], 'name' => $value['txt_name']];
                $data['opt_mx_stakeholder_ids'][] = $item;
                $data['opt_mx_area_ids'][] = $item;
            }
        }

        return $data;
    }

    public function getLauncherModules(): array
    {
        return [
            [
                'id' => 'registration',
                'name' => 'REGISTRATION',
                'icon' => 'book-open-reader',
                'color' => '#474a6b', // Deep Muted Indigo
                'link' => 'Registration'
            ],
            [
                'id' => 'academic',
                'name' => 'ACADEMIC RECORDS',
                'icon' => 'file-circle-check',
                'color' => '#38a169', // Muted Green
                'link' => 'Academic'
            ],
            [
                'id' => 'field',
                'name' => 'FIELD & PROJECTS',
                'icon' => 'diagram-project',
                'color' => '#e53e3e', // Muted Red
                'link' => 'Field'
            ],
            [
                'id' => 'accommodation',
                'name' => 'ACCOMMODATION',
                'icon' => 'bed',
                'color' => '#dd6b20', // Muted Orange
                'link' => 'Accommodation'
            ],
            [
                'id' => 'esb',
                'name' => 'ESB',
                'icon' => 'layer-group',
                'color' => '#3182ce', // Muted Blue
                'link' => 'Esb'
            ]
        ];
    }
}
