<?php

namespace Modules\Report;

use Http\Controller;
use Authentication\Gate;

class Report extends Controller {

    public $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Report_Model();
    }

    public function index() {
        if (!Gate::allowsAny(['view_reports', 'view_statements'])) {
            $this->permissionDenied();
        }
        $this->view()->report_types = $this->model->getReportTypes();
        if (sizeof($this->view()->report_types)) {
            $this->render('index');
        } else {
            $this->permissionDenied();
        }
    }

    public function get_form_fields()
    {
        if (!Gate::allowsAny(['view_reports', 'view_statements'])) {
            $this->permissionDenied();
        }
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $type = $posted_data['report_type'];
        $this->view()->form_fields = $this->model->getReportFormfields($type);
        $this->render('get_form_fields');
    }

    public function get_filtering_fields()
    {
        if (!Gate::allowsAny(['view_reports', 'view_statements'])) {
            $this->permissionDenied();
        }
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $filter = $posted_data['filter_criteria'];
        $type = $posted_data['report_type'];
        $category = $posted_data['report_category'];
        $this->view()->filtering_fields = $this->model->getReportFilterValues($filter, $type, $category);
        $this->render('get_filtering_fields');
    }

    public function get_audit_actions()
    {
        if (!Gate::allowsAny(['view_reports', 'view_statements'])) {
            $this->permissionDenied();
        }
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $table = $posted_data['filter_value'];
        echo json_encode($this->model->getAuditActions($table));
    }

    public function generate_report() {
        if (!Gate::allowsAny(['view_reports', 'view_statements'])) {
            $this->permissionDenied();
        }
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $this->view()->posted_data = $posted_data;

        // Fallback for missing 'report' key (report name)
        if (!isset($posted_data['report']) || empty($posted_data['report'])) {
            // Try to find report name by ID
            if (isset($posted_data['report_type'])) {
                $reportTypes = $this->model->getReportTypes();
                foreach ($reportTypes as $rt) {
                    if ($rt['report_id'] == $posted_data['report_type']) {
                        $posted_data['report'] = $rt['report_type'];
                        break;
                    }
                }
            }
        }
        
        // Default if still missing (prevent crash)
        if (!isset($posted_data['report'])) {
             echo json_encode(['status' => 100, 'message' => 'Report type not specified']);
             return;
        }

        $class_object= 'Modules\\Report\\Reports\\generate'. str_replace('_', '', $posted_data['report']);
        
        if (class_exists($class_object)) {
            $report = (new $class_object)->init($posted_data);
        } else {
             echo json_encode(['status' => 100, 'message' => 'Report generator class not found: ' . $class_object]);
        }
    }

    public function subscribers()
    {
        if (!Gate::allows('view_subscribers')) {
            $this->permissionDenied();
        }
        $this->view()->title = 'Report Subscribers';
        $this->view()->buttons = $this->model->getControls('subscribers');
        $this->view()->class = get_class($this->model);
        $this->view()->actions = $this->model->getActions('subscribers');
        $this->view()->fields = $this->model->getClassFields("mx_report_subscriber");
        $this->view()->formHiddenFields = $this->model->getFormHiddenFields();
        $this->render('subscription/subscribers');
    }

}
