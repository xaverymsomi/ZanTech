<?php
namespace Modules\Miscellaneous;

use Exception;
use Http\Controller;
use Authentication\Perm_Auth;

/**
 * Description of Miscellaneous
 *
 * @author Developer
 */
class Miscellaneous extends Controller {
    public  $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Miscellaneous_Model();
    }

    public function index() {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_configurations')) {
//            $this->view->data = $this->model->getConfigurationData(filter_var($_SESSION['council'], FILTER_SANITIZE_NUMBER_INT));
            $this->view()->data = $this->model->getConfigurationData();
            $this->view()->dropdowns = $this->model->getMiscellaneousDropdowns();
            $this->render('index');
        } else {
            $this->_permissionDenied(__METHOD__);
        }
    }

    public function save()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        try {
            $rule_id = $this->model->getRecordIdByRowValue('mx_rule', $data['int_mx_rule_id']);
            $value =  $data['txt_value'];
            $dat_effective_start_date = date('Y-m-d', strtotime($data['dat_effective_start_date']));
            if (array_key_exists('dat_effective_end_date', $data)){
                $dat_effective_end_date = date('Y-m-d', strtotime($data['dat_effective_end_date']));
            } else {
                $dat_effective_end_date = null;
            }

            $this->model->create([
                'int_mx_rule_id' => $rule_id,
                
                'txt_value' => $value,
                'dat_effective_start_date' => $dat_effective_start_date,
                'dat_effective_end_date' => $dat_effective_end_date,
            ], 'mx_rule_configuration');

            echo 200;
        } catch (Exception $ex) {
            echo 300;
        }
    }
    
    public function changeMiscellaneous() {
        $posted_data = json_decode(file_get_contents("php://input"), true);
        echo json_encode($this->model->updateConfiguration($posted_data));
    }
}
