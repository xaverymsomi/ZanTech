<?php
namespace Modules\EmailContent;

use Database\Model;

class EmailContent_Model extends Model {

    protected string $table = "mx_email_content";
    private $view_dir = "/emailcontent/";
    protected string $title = "Email Settings";

    public function getHiddenFields() {
        return ['txt_row_value'];
    }

    public function getFormHiddenFields() {
        return [];
    }

    public function getControls() {
        return [
            ['action' => 'Add_Email_Content', 'color' => 'success', 'title' => 'Add Email Content',
                'name' => 'New Email Content', 'url' => "'EmailContent'"],
            ['action' => 'Edit_Email_Setup', 'color' => 'primary', 'title' => 'Edit Email Setup',
                'name' => 'Edit Email Setup', 'url' => "'EmailContent'"]
        ];
    }

    public function getActions() {
        return [
            ["action" => "Edit_Email_Content", "name" => "Edit", "icon" => "fa-edit", "color" => "blue", "url" => "EmailContent"]
        ];
    }

    public function getTabs() {
        return [];
    }

    public function getProfileHiddenColumns() {
        return ["id"];
    }

    public function getAssociatedRecordHiddenColumns() {
        return [];
    }

    public function getTable($view_table = false): string
    {
        if ($view_table && property_exists($this, 'view_table') && !empty($this->view_table)) {
            return $this->view_table;
        }
        return $this->table;
    }

    public function getTitle($plural = false): string {
        return $this->title;
    }

    public function getViewDir() {
        return $this->view_dir;
    }

    public function getFormDropdowns($caller = 0) {
        $reasons = [];
        $languages = [];
        $sql = 'SELECT mx_source.*, COUNT(mx_email_content.opt_mx_source_id) total 
                FROM mx_source LEFT OUTER JOIN mx_email_content ON mx_email_content.opt_mx_source_id = mx_source.id 
                GROUP BY mx_source.id, mx_source.txt_name, mx_source.txt_row_value ORDER BY mx_source.txt_name';
        $result = $this->db->select($sql);

        if ($result) {
            switch ($caller) {
                case 0:
                    foreach ($result as $value) {
                        if ($value['total'] == 0) {
                            $reasons[] = ['id' => $value['id'], 'name' => $value['txt_name']];
                        }
                    }
                    break;
                default:
                    foreach ($result as $value) {
                        if ($value['total'] == 0 || $value['id'] == $caller) {
                            $reasons[] = ['id' => $value['id'], 'name' => $value['txt_name']];
                        }
                    }
                    break;
            }
        }

        $result2 = $this->db->select("SELECT * FROM mx_email_language ORDER BY id ASC");
        if ($result2) {
            foreach ($result2 as $value) {
                $languages[] = ['id' => $value['id'], 'name' => $value['txt_name']];
            }
        }
        $data = [
            'opt_mx_source_ids' => $reasons,
            'opt_mx_email_language_ids' => $languages
        ];

        return $data;
    }

    public function getInputFilters() {
        $filters = [
            "id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 1,
                    "max_range" => 2147483647
                ]
            ],
            "txt_subject" => FILTER_SANITIZE_SPECIAL_CHARS,
            "tar_email_body" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_email_signature" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_bcc" => FILTER_VALIDATE_EMAIL,
            "txt_mx_user_id" => FILTER_SANITIZE_SPECIAL_CHARS,
            "opt_mx_email_reason_id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => $this->getMinEmailReasonId(),
                    "max_range" => $this->getMaxEmailReasonId()
                ]
            ]
        ];

        return $filters;
    }

    public function getEmailSetupInputFilters() {
        $filters = [
            "id" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 1,
                    "max_range" => 2147483647
                ]
            ],
            "txt_host" => FILTER_VALIDATE_URL,
            "txt_username" => FILTER_VALIDATE_EMAIL,
            "password" => FILTER_SANITIZE_SPECIAL_CHARS,
            "int_smtp_debug" => FILTER_SANITIZE_NUMBER_INT,
            "txt_smtp_auth" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_smtp_secure" => FILTER_SANITIZE_SPECIAL_CHARS,
            "txt_from_email" => FILTER_VALIDATE_EMAIL,
            "txt_from_name" => FILTER_SANITIZE_SPECIAL_CHARS,
            "int_port" => [
                "filter" => FILTER_SANITIZE_NUMBER_INT,
                "options" => [
                    "min_range" => 1,
                    "max_range" => 999
                ]
            ]
        ];

        return $filters;
    }

    private function getMinEmailReasonId() {
        return $this->db->select("SELECT MIN(id) id FROM mx_email_reason")[0]['id'];
    }

    private function getMaxEmailReasonId() {
        return $this->db->select("SELECT MAX(id) id FROM mx_email_reason")[0]['id'];
    }
}
