<?php

namespace Modules\Dashboard;

use Authentication\Auth;
use Authentication\Perm_Auth;
use Http\Controller;
use Exception;
use Logging\Log;

class Dashboard extends Controller
{
    public string $module = 'Dashboard';

    public function __construct()
    {
        parent::__construct();
        $this->model = new Dashboard_Model();
        Auth::isLogged();
    }

    public function index()
    {
        $this->view()->title = 'Dashboard';
        $this->view()->modules = $this->model->getLauncherModules();
        $this->render('index');
    }

    /**
     * API: Fetches optimized launcher dashboard data.
     */
    public function getLauncherData()
    {
        try {
            $data = $this->model->getLauncherModules();
            return $this->responseSuccess(200, 'Launcher modules loaded', ['data' => $data]);
        } catch (Exception $e) {
            Log::sysLog("DASHBOARD_LAUNCHER_DATA_ERROR: " . $e->getMessage());
            return $this->responseError('Failed to load launcher modules', 500);
        }
    }

    /**
     * API: Fetches optimized admin dashboard data.
     */
    public function getAdminData()
    {
        try {
            $data = $this->model->getAdminData();
            return $this->responseSuccess(200, 'Admin data loaded', ['data' => $data]);
        } catch (Exception $e) {
            Log::sysLog("DASHBOARD_ADMIN_DATA_ERROR: " . $e->getMessage());
            return $this->responseError('Failed to load admin data', 500);
        }
    }

    public function fetchDashboardMedicalData()
    {
        // To be optimized in next phase
        $this->renderJson('fetch_dashboard_medical_data');
    }

    public function createNewTransaction()
    {
        try {
            $permission = Perm_Auth::getPermissions();
            if ($permission->verifyPermission('create_transaction')) {
                $this->view()->title = 'Create New Business';
                $this->view()->controller = 'Dashboard';
                $this->view()->action = 'postCreateTransaction';
                $this->view()->icon = 'transaction';
                $this->view()->data = ['has_extra' => 0];
                $this->view()->dropdowns = $this->model->getFormDropdowns();
                $this->view()->disabled = [];
                $this->render('create_transaction');
            } else {
                $this->permissionDenied();
            }
        } catch (Exception $e) {
            Log::sysLog("DASHBOARD_CREATE_TRANSACTION_ERROR: " . $e->getMessage());
            $this->render('templates/error');
        }
    }
}
