<?php

namespace Modules\CommandCenter;

use Http\Controller;
use Authentication\Gate;

/**
 * CommandCenter Module
 * Developer/Admin dashboard for framework operations.
 * Accessible to super admins or users with the 'view_command_center' permission.
 */
class CommandCenter extends Controller
{
    public function index()
    {
        if (!Gate::allows('view_command_center') && !Gate::isSuperAdmin()) {
            $this->permissionDenied();
        }

        $service = new CommandCenterService();

        $this->view()->title       = "Framework Command Center";
        $this->view()->user        = \Authentication\Auth::user();
        $this->view()->environment = defined('APP_ENV') ? APP_ENV : 'production';

        $this->view()->dbIntel          = $service->getDatabaseIntelligence();
        $this->view()->moduleInventory  = $service->getModuleInventory();
        $this->view()->securityOverview = $service->getSecurityOverview();
        $this->view()->activityFeed     = $service->getRecentActivity(10);
        $this->view()->runtimeHealth    = $service->getRuntimeHealth();
        $this->view()->warnings         = $service->getWarnings();

        $this->render('index');
    }
}
