<?php

namespace Modules\SystemHealth;

use Http\Controller;
use Authentication\Perm_Auth;
use Database\DB;

/**
 * SystemHealth Module
 * Displays system diagnostics and health metrics.
 */
class SystemHealth extends Controller
{
    public function index()
    {
        $perm = Perm_Auth::getPermissions();
        if ($perm->verifyPermission('view_system_health')) {
            $this->view()->title = "System Health & Diagnostics";
            
            // Gather Diagnostics
            $data = [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'app_env' => defined('APP_ENV') ? APP_ENV : 'production',
                'app_debug' => defined('APP_DEBUG') ? (APP_DEBUG ? 'Enabled' : 'Disabled') : 'Disabled',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'db_connection' => $this->checkDbConnection(),
                'disk_free_space' => $this->formatBytes(disk_free_space('/')),
                'disk_total_space' => $this->formatBytes(disk_total_space('/')),
            ];

            $this->view()->diagnostics = $data;
            $this->render('index');
        } else {
            $this->permissionDenied();
        }
    }

    private function checkDbConnection(): string
    {
        try {
            $db = DB::getInstance();
            if ($db) {
                return 'Connected successfully';
            }
            return 'Connection instance null';
        } catch (\Exception $e) {
            return 'Failed: ' . $e->getMessage();
        }
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
