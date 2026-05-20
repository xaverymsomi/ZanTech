<?php
namespace Modules\DualActivity;

use Authentication\Perm_Auth;
use Exception;
use Http\Controller;

/**
 * Description of utility
 *
 * @author abdirahmanhassan
 */
class DualActivity extends Controller {

    public  $model;

    public function __construct() {
        parent::__construct();
        $this->model = new DualActivity_Model();
    }

    public function index() {
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('view_dual_activity')) {
            $this->view->title = $this->model->getTitle();
            $data = $this->model->getAllRecords($this->model->getTable());
//            print_r($data);
            $this->view->buttons = $this->model->getControls();
            $this->view->class = getClassName(get_class($this->model));
            $this->view->allRecords = $data[0];
            $this->view->headings = $this->model->getClassFields($this->model->getTable())['properties'];
            $this->view->hidden = $this->model->getHiddenFields();
            $this->view->actions = $this->model->getActions();
            $this->view->table = $this->model->getTable();
            $this->view->resultData = $data[1];
            $this->view->postData = $data[2];
            $this->render('index');
        } else {
            $this->permissionDenied();
        }
    }

    public function edit($id) {
        $user_id = filter_var($_SESSION['id'], FILTER_SANITIZE_SPECIAL_CHARS);
        $posted_id = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);
        $permission = Perm_Auth::getPermissions();
        if ($permission->verifyPermission('edit_dual_activity')) { //checking permission 
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            if ($returned_id > -1) {
                $data = $this->model->getRecord($returned_id, $this->model->getTable());
                $view_data = [
                    'id' => $posted_id,
                    'int_require_dual_activity' => $data['int_require_dual_activity'],
                    'has_extra' => 0
                ];
                $this->view->title = 'Change Dual Control Setting';
                $this->view->model = $data['txt_model'];
                $this->view->actionname = $data['txt_action_name'];
                $this->view->data = $view_data;
                $this->view->dropdowns = $this->model->getFormDropdowns();
                $this->view->councils_groups = $this->model->getCouncilsGroups($returned_id);

                $this->render('edit');
            } else {
                $this->view->subtitle = "User Editing Error";
                $this->view->renderFull('views/templates/not_found');
            }
        } else {
            $this->permissionDenied();
        }
    }

    public function post_edit() {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        $this->model->db->beginTransaction();
        try {
            $posted_id = filter_var($posted_data['id'], FILTER_SANITIZE_SPECIAL_CHARS);
            $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $posted_id);
            $data = [
                'int_require_dual_activity' => filter_var($posted_data['int_require_dual_activity'], FILTER_SANITIZE_NUMBER_INT)
            ];
            $groups = $posted_data['groups'];
            $this->model->db->update($this->model->getTable(), $data, $returned_id);
            $this->model->manageDualActvitySetting($returned_id, $groups);
            $this->model->db->commit();
            echo json_encode(201);
        } catch (Exception $ex) {
            $this->model->db->rollback();
            echo json_encode(100);
            echo $ex->getMessage();
        }
    }
}
