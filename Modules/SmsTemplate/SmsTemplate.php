<?php

namespace Modules\SmsTemplate;

use Exception;
use Http\Controller;
use Database\Database;
use Authentication\Perm_Auth;

class SmsTemplate extends Controller
{

    public $model;

    public function __construct()
    {
        $this->model = new SmsTemplate_Model();
        parent::__construct();
    }

    public function index()
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('view_sms_templates')) {
            $data = $this->model->getAllRecords($this->model->getTable());

            $this->view()->title = "All " . $this->model->getTitle();
            $this->view()->buttons = $this->model->getControls();
            $this->view()->class = getClassName(get_class($this->model));
            $this->view()->table = $this->model->getTable();
            $this->view()->allRecords = $data[0];
            $this->view()->headings = $this->model->getClassFields($this->model->getTable())['properties'];
            $this->view()->hidden = $this->model->getHiddenFields();
            $this->view()->actions = $this->model->getActions();
            $this->view()->table = $this->model->getTable();
            $this->view()->resultData = $data[1];
            $this->view()->postData = $data[2];
            $this->render('index');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function profile($id)
    {
        $record_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('view_sms_templates')) {
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);
            $data = $this->model->getProfileData($returned_id, $this->model->getTable());
            $this->view()->title = 'SMS Template Profile';
            $this->view()->data = $data;
            $this->view()->tabs = $this->model->getTabs();
            $this->view()->hidden_columns = $this->model->getProfileHiddenColumns();
            $this->view()->buttons = $this->model->getProfileButtons();
            $this->view()->primary_color = filter_input(INPUT_COOKIE, 'primary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#000000"]]);
            $this->view()->secondary_color = filter_input(INPUT_COOKIE, 'secondary', FILTER_SANITIZE_SPECIAL_CHARS, ["options" => ["default" => "#FF0000"]]);
            $this->render('profile');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function edit($id)
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('edit_sms_template')) {
            $sms_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            $data = $this->model->getRecord($sms_id, $this->model->getTable());
            $this->view()->title = 'Update ' . $this->model->getTitle();
            $this->view()->data = $data;
            $this->view()->dropdowns = $this->model->getFormDropdowns($data['opt_mx_source_id']);
            $this->render('edit');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function edit_sms_setup()
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('edit_sms_setup')) {
            $this->view()->title = 'Update ' . $this->model->getTitle();
            $this->view()->data = $this->model->getSMSSetting();
            $this->view()->dropdowns = [];
            $this->render('edit_sms_setup');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function post_edit_sms_setup()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object
        try {
            if (isset($posted_data['id'])) {
                $id = $posted_data['id'];
                $data = [
                    'txt_host' => filter_var($posted_data['txt_host'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'int_port' => filter_var($posted_data['int_port'], FILTER_SANITIZE_NUMBER_INT),
                    'txt_username' => filter_var($posted_data['txt_username'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_var($posted_data['password'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_msg_type' => filter_var($posted_data['txt_msg_type'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_delivery' => filter_var($posted_data['txt_delivery'], FILTER_SANITIZE_SPECIAL_CHARS)
                ];
                $this->model->update($data, 'mx_sms', $id);
            } else {
                $data = [
                    'txt_host' => filter_var($posted_data['txt_host'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'int_port' => filter_var($posted_data['int_port'], FILTER_SANITIZE_NUMBER_INT),
                    'txt_username' => filter_var($posted_data['txt_username'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'password' => filter_var($posted_data['password'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_msg_type' => filter_var($posted_data['txt_msg_type'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'txt_delivery' => filter_var($posted_data['txt_delivery'], FILTER_SANITIZE_SPECIAL_CHARS)
                ];
                $this->model->create($data, 'mx_sms');
            }
            echo json_encode(200);
        } catch (Exception $ex) {
            echo json_encode(100);
            echo $ex->getMessage();
        }
    }

    public function create()
    {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('add_sms_template')) { //checking permission
            $this->view()->class = get_class($this->model);
            $this->view()->title = 'New SMS Template';
            $this->view()->dropdowns = $this->model->getFormDropdowns(0); // Get only unused reasons
            $this->view()->data = ['has_extra' => 0];
            $this->render('create');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function save()
    {
        $posted_data = json_decode(file_get_contents("php://input"), true); //convert json object 
        try {
            $data = [
                'txt_sender' => filter_var($posted_data['txt_sender'], FILTER_SANITIZE_SPECIAL_CHARS),
                'tar_sms_content' => filter_var($posted_data['tar_sms_content'], FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_source_id' => filter_var($posted_data['opt_mx_source_id'], FILTER_SANITIZE_NUMBER_INT),
                'opt_mx_sms_language_id' => filter_var($posted_data['opt_mx_sms_language_id'], FILTER_SANITIZE_NUMBER_INT),
                'txt_row_value' => $this->model->getGUID('mx_sms_template')
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
                'opt_mx_council_id' => filter_var($posted_data['opt_mx_council_id'], FILTER_SANITIZE_NUMBER_INT),
                'tar_sms_content' => filter_var($posted_data['tar_sms_content'], FILTER_SANITIZE_SPECIAL_CHARS),
                'opt_mx_sms_language_id' => filter_var($posted_data['opt_mx_sms_language_id'], FILTER_SANITIZE_NUMBER_INT)
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
        $query = "SELECT * FROM mx_sms_template WHERE opt_mx_source_id = $source_id";
        $db = new Database();
        $content = $db->select($query);
        return $content[0];
    }

}
