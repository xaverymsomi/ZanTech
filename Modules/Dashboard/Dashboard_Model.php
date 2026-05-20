<?php

namespace Modules\Dashboard;

use Database\Model;

class Dashboard_Model extends Model
{
    private string $view_dir = "/dashboard/";
    protected string $title = "Dashboard";
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
            ],
            [
                'id' => 'menu',
                'name' => 'MENU BUILDER',
                'icon' => 'sitemap',
                'color' => '#008080', // Teal
                'link' => 'Menu'
            ],
            [
                'id' => 'user',
                'name' => 'USER MANAGEMENT',
                'icon' => 'users-gear',
                'color' => '#6366f1', // Indigo
                'link' => 'User'
            ],
            [
                'id' => 'permission',
                'name' => 'ACCESS CONTROL',
                'icon' => 'shield-halved',
                'color' => '#f43f5e', // Rose
                'link' => 'Permission'
            ]
        ];
    }
}
