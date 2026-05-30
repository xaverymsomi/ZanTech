<?php

namespace Modules\EmailContent;

use Exception;
use Http\Controller;
use Authentication\Gate;

class EmailContent extends Controller
{

    public $module = 'EmailContent';
    public $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new EmailContent_Model();
    }

    public function index()
    {
        $this->requirePermission('view_email_contents');
        $data = $this->model->getAllRecords($this->model->getTable());
        $this->view()->title      = "All " . $this->model->getTitle();
        $this->view()->buttons    = $this->model->getControls();
        $this->view()->class      = getClassName(get_class($this->model));
        $this->view()->table      = $this->model->getTable();
        $this->view()->allRecords = $data[0];
        $this->view()->headings   = $this->model->getClassFields($this->model->getTable())['properties'];
        $this->view()->hidden     = $this->model->getHiddenFields();
        $this->view()->actions    = $this->model->getActions();
        $this->view()->resultData = $data[1];
        $this->view()->postData   = $data[2];
        $this->render('index');
    }

    public function profile($id)
    {
        $this->requirePermission('view_email_content');
        $record_id   = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);
        $data = $this->model->getProfileData($returned_id, $this->model->getTable());
        $this->view()->title          = 'Email Content Profile';
        $this->view()->data           = $data;
        $this->view()->tabs           = $this->model->getTabs();
        $this->view()->hidden_columns = $this->model->getProfileHiddenColumns();
        $this->view()->buttons        = [];
        $this->view()->primary_color   = filter_input(INPUT_COOKIE, 'primary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#000000"]]);
        $this->view()->secondary_color = filter_input(INPUT_COOKIE, 'secondary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#FF0000"]]);
        $this->render('profile');
    }

    public function edit($id)
    {
        $this->requirePermission('edit_email_content');
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $email_id  = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
        $data      = $this->model->getRecord($email_id, $this->model->getTable());
        $this->view()->title     = 'Update ' . $this->model->getTitle();
        $this->view()->data      = $data;
        $this->view()->dropdowns = $this->model->getFormDropdowns($data['opt_mx_source_id']);
        $this->render('edit');
    }

    public function edit_email_setup()
    {
        $this->requirePermission('edit_email_setup');
        $this->view()->title     = 'Update ' . $this->model->getTitle();
        $this->view()->data      = $this->model->getMailSetting();
        $this->view()->dropdowns = [];
        $this->render('edit_email_setup');
    }

    public function post_edit_email_setup()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        try {
            if (isset($posted_data['id'])) {
                $id = $posted_data['id'];
                $data = [
                    'txt_host' => filter_var($posted_data['txt_host'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_username' => filter_var($posted_data['txt_username'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_var($posted_data['password'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'int_smtp_debug' => filter_var($posted_data['int_smtp_debug'], FILTER_SANITIZE_NUMBER_INT),
                    'txt_smtp_auth' => filter_var($posted_data['txt_smtp_auth'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_smtp_secure' => filter_var($posted_data['txt_smtp_secure'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_from_email' => filter_var($posted_data['txt_from_email'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_from_name' => filter_var($posted_data['txt_from_name'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'int_port' => filter_var($posted_data['int_port'], FILTER_SANITIZE_NUMBER_INT)
                ];
                $this->model->update($data, 'mx_email', $id);
            } else {
                $data = [
                    'txt_host' => filter_var($posted_data['txt_host'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_username' => filter_var($posted_data['txt_username'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_var($posted_data['password'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'int_smtp_debug' => filter_var($posted_data['int_smtp_debug'], FILTER_SANITIZE_NUMBER_INT),
                    'txt_smtp_auth' => filter_var($posted_data['txt_smtp_auth'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_smtp_secure' => filter_var($posted_data['txt_smtp_secure'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_from_email' => filter_var($posted_data['txt_from_email'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_from_name' => filter_var($posted_data['txt_from_name'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'int_port' => filter_var($posted_data['int_port'], FILTER_SANITIZE_NUMBER_INT),
                    'txt_row_value' => $this->model->getGUID('mx_email')
                ];
                $this->model->create($data, 'mx_email');
            }
            echo json_encode(200);
        } catch (Exception $ex) {
            echo json_encode(100);
            echo $ex->getMessage();
            echo $ex->getLine();
        }
    }

    public function create()
    {
        $this->requirePermission('add_email_content');
        $this->view()->class     = getClassName(get_class($this->model));
        $this->view()->title     = 'New ' . $this->model->getTitle();
        $this->view()->dropdowns = $this->model->getFormDropdowns(0);
        $this->view()->data      = ['has_extra' => 0];
        $this->render('create');
    }

    public function save()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object 
        try {
            $data = [
                'txt_subject' => filter_var($posted_data['txt_subject'], FILTER_SANITIZE_SPECIAL_CHARS),
                'tar_email_body' => filter_var($posted_data['tar_email_body'], FILTER_SANITIZE_SPECIAL_CHARS),
                'txt_email_signature' => filter_var($posted_data['txt_email_signature'], FILTER_SANITIZE_SPECIAL_CHARS),
                'txt_bcc' => filter_var($posted_data['txt_bcc'], FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_user_id' => filter_var($_SESSION['user_id'], FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_email_language_id' => filter_var($posted_data['opt_mx_email_language_id'], FILTER_SANITIZE_NUMBER_INT),
                'opt_mx_source_id' => filter_var($posted_data['opt_mx_source_id'], FILTER_SANITIZE_NUMBER_INT),
                'txt_row_value' => $this->model->getGUID('mx_email_content')
            ];
            $this->model->create($data, $this->model->getTable());
            echo json_encode(200);
        } catch (Exception $ex) {
            echo json_encode(100);
            echo $ex->getMessage();
        }
    }

    public function post_edit()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        try {
            $id = $posted_data['id'];
            $data = [
                //                'opt_mx_institution_id' => filter_var($posted_data['opt_mx_institution_id'], FILTER_SANITIZE_NUMBER_INT),
                'txt_subject' => filter_var($posted_data['txt_subject'], FILTER_SANITIZE_SPECIAL_CHARS),
                'tar_email_body' => filter_var($posted_data['tar_email_body'], FILTER_SANITIZE_SPECIAL_CHARS),
                'txt_email_signature' => filter_var($posted_data['txt_email_signature'], FILTER_SANITIZE_SPECIAL_CHARS),
                'txt_bcc' => filter_var($posted_data['txt_bcc'], FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_email_language_id' => filter_var($posted_data['opt_mx_email_language_id'], FILTER_SANITIZE_NUMBER_INT),
                'opt_mx_source_id' => filter_var($posted_data['opt_mx_source_id'], FILTER_SANITIZE_NUMBER_INT)
            ];
            $this->model->update($data, $this->model->getTable(), $id);
            echo json_encode(201);
        } catch (Exception $ex) {
            echo json_encode(100);
            echo $ex->getMessage();
        }
    }

    public function getContent($source_id)
    {
        $content = $this->model->getFilteredRecords($this->model->getTable(), ['opt_mx_source_id'], [filter_var($source_id, FILTER_SANITIZE_SPECIAL_CHARS)]);
        return $content[0];
    }
}
