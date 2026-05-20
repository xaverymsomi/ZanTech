<?php

namespace Modules\Approval;

use Foundation\BaseModuleController;
use Authentication\Auth;
use Logging\Log;
use Throwable;

class Approval extends BaseModuleController
{
    public function index(): void
    {
        $this->requirePermission('view_approvals');
        
        $checkerId = Auth::id();
        $requests = $this->model->getPendingRequests($checkerId);
        
        $this->view()->title = "Pending Approvals";
        $this->view()->requests = $requests;
        
        $this->render('index');
    }

    /**
     * Approve and Execute the pending action.
     */
    public function approve($id): void
    {
        $this->requirePermission('approve_activity');
        
        $requestId = (int)filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        $request = $this->model->getRequestDetails($requestId);

        if (!$request || (int)$request['int_status'] !== 0) {
            $this->responseError("Request not found or already processed.");
            return;
        }

        // 1. Prepare the execution environment
        $module = $request['txt_module_name'];
        $method = $request['txt_method_name'];
        $payload = json_decode($request['txt_payload'], true);

        // 2. Mock the request data so the target controller can "see" it
        $_POST = $payload; 
        
        try {
            // 3. Dynamically execute the target module action
            $moduleClass = "\\Modules\\" . ucwords($module) . "\\" . ucwords($module);
            
            if (!class_exists($moduleClass)) {
                 throw new \Exception("Target module class {$moduleClass} not found.");
            }

            $instance = new $moduleClass();
            
            // Execute!
            Log::sysLog("DUAL-CONTROL: Replaying Action {$module}::{$method} for Approval ID: {$requestId}");
            
            // We call the method. The target method should use $this->request()->all() or $_POST
            if (method_exists($instance, $method)) {
                $instance->$method();
            } else {
                throw new \Exception("Method {$method} not found in {$moduleClass}.");
            }

            // 4. Mark as Approved
            $this->model->updateRecord([
                'int_status' => 1,
                'dat_approved_date' => date('Y-m-d H:i:s'),
                'int_approved_by' => Auth::id()
            ], 'mx_dual_activity_log', $requestId);

            Log::sysLog("DUAL-CONTROL: Approval Successful ID: {$requestId}");

            \Services\AuditTrail::log(
                'DUAL_CONTROL_APPROVED', 
                "Approved ID: {$requestId} ({$module}::{$method})", 
                $payload
            );

        } catch (Throwable $e) {
            Log::exception($e, "APPROVAL_EXECUTION_FAILED", ['request_id' => $requestId]);
            $this->responseError("Execution failed: " . $e->getMessage());
        }
    }

    /**
     * Reject the pending action.
     */
    public function reject($id): void
    {
        $this->requirePermission('approve_activity');
        
        $requestId = (int)filter_var($id, FILTER_SANITIZE_NUMBER_INT);
        
        $this->model->updateRecord([
            'int_status' => 2, // Rejected
            'dat_approved_date' => date('Y-m-d H:i:s'),
            'int_approved_by' => Auth::id()
        ], 'mx_dual_activity_log', $requestId);

        \Services\AuditTrail::log('DUAL_CONTROL_REJECTED', "Rejected ID: {$requestId}");

        $this->responseSuccess(200, "Request rejected.");
    }
}
