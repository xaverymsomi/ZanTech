<?php

namespace Modules\Finance;

use Foundation\BaseModuleController;
use Logging\Log;

/**
 * Finance Module Example
 * Demonstrates Automatic Dual Control (Maker-Checker).
 */
class Finance extends BaseModuleController
{
    /**
     * SENSITIVE ACTIONS: These will automatically trigger the 
     * Maker-Checker workflow without any additional code.
     */
    protected array $dualControl = [
        'updateSalary', 
        'disburseFunds'
    ];

    /**
     * This method will ONLY execute after a second person (Checker) 
     * approves the request.
     */
    public function updateSalary(): void
    {
        $payload = $this->request()->all();
        Log::sysLog("FINANCE DIAGNOSTIC: Received Payload: " . json_encode($payload));
        
        $employeeId = $payload['employee_id'] ?? null;
        $newSalary  = $payload['amount'] ?? 0;

        // The actual database update logic
        // This is safe because the Kernel ensures it only runs on approved requests.
        $this->model->db->update('mx_employee_salary', [
            'dbl_amount' => $newSalary,
            'dat_date_updated' => date('Y-m-d H:i:s')
        ], $employeeId);

        Log::sysLog("Salary Updated for Employee ID: {$employeeId} to {$newSalary}");
        
        $this->responseSuccess(200, "Salary update completed successfully.");
    }

    public function disburseFunds(): void
    {
        // Another sensitive operation protected by Dual Control
        $this->responseSuccess(200, "Funds disbursed.");
    }
}
