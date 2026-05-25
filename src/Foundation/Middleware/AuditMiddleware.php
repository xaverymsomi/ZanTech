<?php

namespace Foundation\Middleware;

use Foundation\Routing\RouteContext;
use Services\AuditTrail;
use Closure;

/**
 * AuditMiddleware
 * 
 * Automatically captures every system action across all modules.
 */
final class AuditMiddleware implements Middleware
{
    /**
     * Handle the request
     */
    public function handle(RouteContext $route, Closure $next): mixed
    {
        // 1. Execute the next middleware / controller
        $result = $next($route);

        // 2. Post-execution: Automatically audit the action
        // We only audit if it's NOT a public controller (e.g. login page view) 
        // OR if it's a POST request (even on public routes like /login)
        if (!$route->isPublicController() || $_SERVER['REQUEST_METHOD'] === 'POST') {
            
            $module = strtoupper($route->controller() ?: 'ROOT');
            $method = strtoupper($route->method() ?: 'INDEX');
            $action = "{$module}_{$method}";

            // Don't log noisy or internal background actions to keep audit clean
            $excluded = [
                'MENU_GETUSERMENUS',
                'DASHBOARD_REFRESHSTATUS',
                'AUTORUN_EXECUTE'
            ];

            if (!in_array($action, $excluded, true)) {
                AuditTrail::log(
                    $action,
                    "Auto-Audited Action: {$action}",
                    $_POST ?: ($_GET ?: null)
                );
            }
        }

        return $result;
    }
}
