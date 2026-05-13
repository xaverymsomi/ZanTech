<?php

declare(strict_types=1);

/**
 * -------------------------------------------------------------------------
 * ZANTECH FRAMEWORK - APPLICATION FOUNDATION ROUTER
 * -------------------------------------------------------------------------
 *
 * Responsibilities:
 *  - Normalize the incoming URI
 *  - Prevent directory traversal attacks
 *  - Detect the correct entry namespace (web/api/cronjob)
 *  - Securely load the appropriate kernel file
 */

// -------------------------------------------------------------------------
// 0. CORE BOOTSTRAPPING
// -------------------------------------------------------------------------

if (!defined('ZT_BASE_PATH')) {
    define('ZT_BASE_PATH', dirname(__DIR__));
}

// A. Composer Autoload
$autoload = ZT_BASE_PATH . '/vendor/autoload.php';
if (!is_file($autoload) || !is_readable($autoload)) {
    http_response_code(500);
    echo 'Zantech Foundation Error: Run "composer install" to generate autoload.php';
    exit;
}
require_once $autoload;

// B. Exception & Error Mapping
use Exceptions\ExceptionHandler;

set_exception_handler(static function (Throwable $e): void {
    ExceptionHandler::handle($e);
});

$customErrorHandler = ZT_BASE_PATH . '/helpers/ErrorHandler.php';
if (is_file($customErrorHandler)) {
    require_once $customErrorHandler;
    if (function_exists('mxPublicError')) {
        set_error_handler('mxPublicError');
    }
    if (function_exists('mxFatalErrorHandler')) {
        register_shutdown_function('mxFatalErrorHandler');
    }
}

// C. Configuration, Constants & Helpers
$configFile  = ZT_BASE_PATH . '/configuration/config.php';
$sysPrefFile = ZT_BASE_PATH . '/constants/sys_pref.php';
$helpersFile = ZT_BASE_PATH . '/helpers/helpers.php';

foreach ([$configFile, $sysPrefFile, $helpersFile] as $file) {
    if (is_file($file)) {
        require_once $file;
    }
}

require_once __DIR__ . '/AppLoaderFunctions.php';

// -------------------------------------------------------------------------
// 1. REQUEST NORMALIZATION
// -------------------------------------------------------------------------

$rawUri = (string)($_SERVER['REQUEST_URI'] ?? '/');
$path   = zt_normalize_path($rawUri);

if (zt_is_forbidden_boot_probe($path)) {
    http_response_code(404);
    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo '404 Not Found';
    exit;
}

$namespace = zt_detect_namespace($path);

// Fail-safe: only allow known namespaces
if (!in_array($namespace, ['web', 'api', 'cronjob'], true)) {
    $namespace = 'web';
}

$debug = function_exists('zt_env_bool') && zt_env_bool('APP_DEBUG', false);

try {
    $entry = zt_resolve_entry_file(__DIR__, $namespace);
} catch (Throwable $e) {
    http_response_code(500);

    if (!headers_sent()) {
        header('Content-Type: text/plain; charset=UTF-8');
    }

    if ($debug) {
        $safePath = str_replace(["\r", "\n"], '', $path);

        echo "Zantech Foundation Error\n";
        echo "Namespace: {$namespace}\n";
        echo "Path: {$safePath}\n";
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        echo 'Internal Server Error';
    }

    exit;
}

require_once $entry;
