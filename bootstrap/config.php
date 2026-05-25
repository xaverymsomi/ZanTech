<?php

use Dotenv\Dotenv;
use Logging\Log;

/**
 * ---------------------------------------------------------
 *  Zantech Global Configuration Bootstrap (v4)
 * ---------------------------------------------------------
 *
 * Responsibilities:
 *  - Load and normalize environment variables from .env
 *  - Provide the zt_env() helper for safe environment access
 *  - Standardize environment/mode detection
 */

// ---------------------------------------------------------
// 1. Base Application Paths (Initial bootstrap)
// ---------------------------------------------------------

if (!defined('ZT_APP_ROOT')) {
    define('ZT_APP_ROOT', dirname(__DIR__));
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}


// ---------------------------------------------------------
// 2. Safe ENV Helper
// ---------------------------------------------------------

if (!function_exists('zt_env')) {

    /**
     * Fetch .env variables safely
     */
    function zt_env(string $key, $default = null): string
    {
        if (isset($_ENV[$key])) {
            $val = $_ENV[$key];

            if ($val === 'true') return '1';
            if ($val === 'false') return '0';

            return (string) $val;
        }

        return (string) ($default ?? '');
    }
}


// ---------------------------------------------------------
// 3. Load .env
// ---------------------------------------------------------

try {
    if (class_exists(Dotenv::class)) {
        $dotenv = Dotenv::createImmutable(ZT_APP_ROOT);
        $dotenv->load();
    } else {
        Log::sysErr('Zantech config warning: Dotenv class missing');
    }
} catch (Throwable $e) {
    Log::sysErr('Zantech config error: ' . $e->getMessage());
}

/**
 * NOTE: 
 * All system-wide constants are now defined in bootstrap/sys_pref.php.
 * This file (config.php) should remain focused on environment loading.
 */
