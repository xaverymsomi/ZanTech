<?php

namespace Modules\Report;

use Database\Model;
use Authentication\Gate;

/**
 * Report_Model
 */
class Report_Model extends Model
{

    protected string $table = "";
    private string $view_dir = "report/";
    protected string $title = "Reports";
    public $no_old_data = ['generate_report'];

    public function getHiddenFields(): array
    {
        return [];
    }

    public function getFormHiddenFields(): array
    {
        return [];
    }

    public function getControls(): array
    {
        $controls = [];
        return $controls;
    }

    public function getActions(): array
    {
        $actions = [];
        return $actions;
    }

    public function getTable($view_table = false): string
    {
        return $this->table;
    }

    public function getTitle($plural = false): string
    {
        return $this->title;
    }

    public function getViewDir(): string
    {
        return $this->view_dir;
    }

    public function getReportTypes(): array
    {
        $permitted_section = [];
        $data = [
            ['report_type' => 'General_Report',     'permission' => 'print_general_report',       'report_title' => 'General',      'report_id' => 1,  'report_header' => 'general.html'],
            ['report_type' => 'Applicant_Report',   'permission' => 'generate_applicant_report',  'report_title' => 'Applicants',   'report_id' => 2,  'report_header' => 'applicant.html'],
            ['report_type' => 'Permit_Report',      'permission' => 'generate_permission_report', 'report_title' => 'Permit',       'report_id' => 11, 'report_header' => 'permit.html'],
            ['report_type' => 'Application_Report', 'permission' => 'generate_application_report','report_title' => 'Applications', 'report_id' => 3,  'report_header' => 'application.html'],
            ['report_type' => 'Invoice_Report',     'permission' => 'generate_invoice_report',    'report_title' => 'Invoice',      'report_id' => 4,  'report_header' => 'invoice.html'],
            ['report_type' => 'Receipt_Report',     'permission' => 'generate_receipt_report',    'report_title' => 'Receipt',      'report_id' => 8,  'report_header' => 'receipt.html'],
            ['report_type' => 'Finance_Report',     'permission' => 'generate_finance_report',    'report_title' => 'Finances',     'report_id' => 10, 'report_header' => 'finance.html'],
        ];

        foreach ($data as $value) {
            if (Gate::allows($value['permission'])) {
                $permitted_section[] = $value;
            }
        }
        return $permitted_section;
    }

    public function getReportFormfields($type): array
    {
        $formfields = [];
        if ($type == "General_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary']
                ],
                'title' => 'GENERAL REPORT'];
        } elseif ($type == "Applicant_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [
                    ['Id' => 0, 'Name' => 'No Filter'],
                    ['Id' => 1, 'Name' => 'Gender']

                ],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary'],
                    ['Id' => 1, 'Name' => 'Detailed']
                ],
                'nationalities' => $this->getNationality(),
                'title' => 'APPLICANT REPORT'];

        } elseif ($type == "Receipt_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [
                    ['Id' => 0, 'Name' => 'No Filter'],
                    ['Id' => 2, 'Name' => 'Payment Provider'],

                ],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary'],
                    ['Id' => 1, 'Name' => 'Detailed']
                ],
                'title' => 'RECEIPT REPORT'];

        } elseif ($type == "Permit_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [
                    ['Id' => 0, 'Name' => 'No Filter'],
                    ['Id' => 1, 'Name' => 'Institution']
                ],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary'],
                    ['Id' => 1, 'Name' => 'Detailed']
                ],
                'permittypes' => $this->getPermitTypes(),
                'permitstatuses' => $this->getPermitStatus(),
                'title' => 'PERMIT REPORT'];

        } elseif ($type == "Application_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [
                    ['Id' => 0, 'Name' => 'No Filter'],
                    ['Id' => 1, 'Name' => 'Institution']

                ],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary'],
                    ['Id' => 1, 'Name' => 'Detailed']
                ],
                'applications' => $this->getApplication(),
                'title' => 'APPLICATION REPORT'
            ];

        } elseif ($type == "Invoice_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [
                    ['Id' => 0, 'Name' => 'No Filter'],
                    ['Id' => 1, 'Name' => 'Status'],
                ],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary'],
                    ['Id' => 1, 'Name' => 'Detailed']
                ],
                'invoices' => $this->getInvoice(),
                'title' => 'INVOICE REPORT'
            ];
        } elseif ($type == "Finance_Report") {
            $formfields = [
                'group_by' => [],
                'filters' => [
                    ['Id' => 0, 'Name' => 'No Filter'],
                    ['Id' => 1, 'Name' => 'Status'],
                ],
                'categories' => [
                    ['Id' => 0, 'Name' => 'Summary'],
                    ['Id' => 1, 'Name' => 'Detailed']
                ],
                'paymentproviders' => $this->getProviders(),
                'title' => 'FINANCE REPORT'
            ];
        }
        return $formfields;
    }

    public function getReportFilterValues($filter, $type, $category): array
    {
        $data = [];
        if ($type == 2) {
            switch ($filter) {
                case 1:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_gender"));
                    break;

            }
        }
        if ($type == 3) {
            switch ($filter) {
                case 1:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_institution"));
                    break;
            }
        }
        if ($type == 4) {

            switch ($filter) {
                case 1:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_invoice_status"));
                    break;
            }
        }
        if ($type == 8) {

            switch ($filter) {
                case 2:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_payment_provider"));
                    break;
            }

        }
        if ($type == 10) {
            switch ($filter) {
                case 1:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_payment_status"));
                    break;
                case 2:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_payment_provider"));
                    break;
            }
        }
        if ($type == 11) {

            switch ($filter) {
                case 1:
                    $data = array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS [Id], txt_name AS [Name] FROM mx_institution"));
                    break;
            }

        }
        return $data;
    }

    public function getAuditActions($table)
    {
        $results = $this->db->select("SELECT 1 AS [Id], REPLACE(txt_action,'_',' ') AS [Name] FROM mx_audit_trail WHERE txt_table=:table GROUP BY txt_action ORDER BY txt_action ASC", [':table' => filter_var($table, FILTER_SANITIZE_SPECIAL_CHARS)]);
        if ($results) {
            $data = array_merge([['Id' => 0, 'Name' => 'All Actions']], $results);
        } else {
            $data = $results;
        }
        return $data;
    }

    private function getProviders() : array
    {
        return array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS Id, txt_name AS 'Name' FROM mx_payment_provider"));
    }

    private function getNationality(): array
    {
        return array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS Id, txt_nationality AS 'Name' FROM mx_nationality"));
    }

    private function getPermitStatus(): array
    {
        return array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS Id, txt_name AS 'Name' FROM mx_permit_status"));
    }


    private function getPermitTypes() : array
    {
        return array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS Id, txt_name AS 'Name' FROM mx_permit_type"));
    }

    private function getApplication(): array
    {
        return array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS Id, txt_name AS 'Name' FROM mx_application_status"));
    }

    private function getInvoice(): array
    {
        return array_merge([['Id' => 0, 'Name' => 'All']], $this->db->select("SELECT id AS Id, txt_name AS 'Name' FROM mx_invoice_type"));
    }

}
