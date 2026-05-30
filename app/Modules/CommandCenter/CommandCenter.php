<?php

namespace Modules\CommandCenter;

use Http\Controller;
use Authentication\Perm_Auth;

/**
 * CommandCenter Module
 * Developer/Admin dashboard for framework operations.
 */
class CommandCenter extends Controller
{
    public function index()
    {
        $perm = Perm_Auth::getPermissions();
        // Assume developer/admin has 'view_command_center' or we check a specific role.
        // For now, we will use a dedicated permission, or fallback to super admin check.
        if ($perm->verifyPermission('view_command_center') || $this->isDeveloper()) {
            
            $service = new CommandCenterService();
            
            $this->view()->title = "Framework Command Center";
            $this->view()->user = \Authentication\Auth::user();
            $this->view()->environment = defined('APP_ENV') ? APP_ENV : 'production';
            
            // Gather data
            $this->view()->dbIntel = $service->getDatabaseIntelligence();
            $this->view()->moduleInventory = $service->getModuleInventory();
            $this->view()->securityOverview = $service->getSecurityOverview();
            $this->view()->activityFeed = $service->getRecentActivity(10);
            $this->view()->runtimeHealth = $service->getRuntimeHealth();
            $this->view()->warnings = $service->getWarnings();
            
            $this->render('index');
        } else {
            $this->permissionDenied();
        }
    }

    /**
     * Helper to check if the current user is a developer/super admin.
     * Adapt this to the framework's actual root role ID or convention.
     */
    private function isDeveloper(): bool
    {
        $user = \Authentication\Auth::user();
        if (!$user) return false;
        
        // Usually role 1 is Super Admin / Developer in Oryn/ZanTech
        if (isset($user['opt_mx_group_id']) && $user['opt_mx_group_id'] == 1) {
            return true;
        }
        
        return false;
    }
}
