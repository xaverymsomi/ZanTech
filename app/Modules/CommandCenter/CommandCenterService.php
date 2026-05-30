<?php

namespace Modules\CommandCenter;

use Database\DB;
use Logging\Log;

/**
 * Service for collecting system operations data securely.
 */
class CommandCenterService
{
    private array $warnings = [];

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * Defensively check if a table exists.
     */
    public function tableExists(string $table): bool
    {
        try {
            DB::select("SELECT 1 FROM {$table} WHERE 1=0");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Defensively count rows. Returns -1 if table doesn't exist or query fails.
     */
    public function safeCount(string $table, string $where = '', array $params = []): int
    {
        try {
            $sql = "SELECT COUNT(*) as c FROM {$table}" . ($where ? " WHERE {$where}" : "");
            $res = DB::select($sql, $params);
            return (int)($res[0]['c'] ?? 0);
        } catch (\Exception $e) {
            return -1;
        }
    }

    /**
     * Collects general database stats without doing full schema introspection.
     */
    public function getDatabaseIntelligence(): array
    {
        $coreTables = [
            'mx_user',
            'mx_group',
            'mx_permission',
            'mx_menu',
            'mx_audit_trail',
            'mx_login_credential',
            'mx_job_queue'
        ];

        $results = [];
        $missingCore = [];

        foreach ($coreTables as $table) {
            $exists = $this->tableExists($table);
            $count = $exists ? $this->safeCount($table) : -1;
            
            if (!$exists) {
                $missingCore[] = $table;
                $this->addWarning("Missing core table: {$table}");
            } else if ($count === 0 && in_array($table, ['mx_user', 'mx_group', 'mx_permission'])) {
                $this->addWarning("Core table is empty: {$table}");
            }

            $results['core_tables'][$table] = [
                'exists' => $exists,
                'count' => $count
            ];
        }

        $results['missing_core'] = $missingCore;
        $results['engine'] = defined('DB_TYPE') ? DB_TYPE : 'Unknown';

        return $results;
    }

    /**
     * Scans the Modules directory to build an inventory of modules and routes.
     */
    public function getModuleInventory(): array
    {
        $modulesPath = rtrim(ZT_APP_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR;
        $inventory = [];

        if (!is_dir($modulesPath)) {
            $this->addWarning("Modules directory not found at {$modulesPath}");
            return $inventory;
        }

        $iterator = new \DirectoryIterator($modulesPath);
        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDot() || !$fileinfo->isDir()) {
                continue;
            }

            $moduleName = $fileinfo->getFilename();
            // Skip the command center itself
            if ($moduleName === 'CommandCenter') {
                continue;
            }

            $hasController = is_file($fileinfo->getPathname() . DIRECTORY_SEPARATOR . $moduleName . '.php');
            $hasModel = is_file($fileinfo->getPathname() . DIRECTORY_SEPARATOR . $moduleName . '_Model.php');
            $hasView = is_file($fileinfo->getPathname() . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . 'index.php');

            $status = 'Healthy';
            if (!$hasController) {
                $status = 'Broken';
                $this->addWarning("Module {$moduleName} is missing its Controller file.");
            } else if (!$hasView && !$hasModel) {
                $status = 'Partial';
            }

            $inventory[] = [
                'name' => $moduleName,
                'controller' => $hasController,
                'model' => $hasModel,
                'view' => $hasView,
                'status' => $status
            ];
        }

        return $inventory;
    }

    public function getSecurityOverview(): array
    {
        // Try to get login failures today
        $failedLoginsToday = -1;
        $successfulLoginsToday = -1;
        
        if ($this->tableExists('mx_audit_trail')) {
            $today = date('Y-m-d');
            $failedLoginsToday = $this->safeCount('mx_audit_trail', "txt_action LIKE '%login%failed%' AND dat_created_at >= ?", [$today . ' 00:00:00']);
            $successfulLoginsToday = $this->safeCount('mx_audit_trail', "txt_action LIKE '%login%success%' AND dat_created_at >= ?", [$today . ' 00:00:00']);
        }

        $lockedUsers = -1;
        if ($this->tableExists('mx_login_credential')) {
            // Assuming status 0 means inactive/locked based on typical conventions
            $lockedUsers = $this->safeCount('mx_login_credential', "opt_mx_status_id = 0");
        }

        return [
            'failed_logins_today' => max(0, $failedLoginsToday), // -1 fallback to 0 for UI clarity if missing
            'successful_logins_today' => max(0, $successfulLoginsToday),
            'locked_users' => max(0, $lockedUsers)
        ];
    }

    public function getRecentActivity(int $limit = 5): array
    {
        if (!$this->tableExists('mx_audit_trail')) {
            return [];
        }

        try {
            $sql = "SELECT TOP {$limit} * FROM mx_audit_trail ORDER BY id DESC";
            if (defined('DB_TYPE') && strtolower(DB_TYPE) === 'mysql') {
                $sql = "SELECT * FROM mx_audit_trail ORDER BY id DESC LIMIT {$limit}";
            }
            return DB::select($sql);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getRuntimeHealth(): array
    {
        $health = [];
        
        // PHP Version
        $health['php_version'] = PHP_VERSION;

        // DB Connection
        try {
            $db = DB::connection();
            $health['db_connection'] = $db ? 'Healthy' : 'Warning';
        } catch (\Exception $e) {
            $health['db_connection'] = 'Broken';
            $this->addWarning("Database connection failed: " . $e->getMessage());
        }

        // Directories
        $publicAssets = rtrim(ZT_BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets';
        if (is_dir($publicAssets) && is_writable($publicAssets)) {
            $health['assets_writable'] = 'Healthy';
        } else {
            $health['assets_writable'] = 'Warning';
            $this->addWarning("Public assets directory is not writable or missing.");
        }

        // Required Configs
        $configPath = rtrim(ZT_APP_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'config.php';
        $health['config_present'] = is_file($configPath) ? 'Healthy' : 'Broken';

        return $health;
    }
}
