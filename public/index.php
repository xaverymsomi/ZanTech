<?php



/**
 * -------------------------------------------------------------------------
 * ZANTECH FRAMEWORK - PUBLIC ENTRY POINT
 * -------------------------------------------------------------------------
 *
 * This file is the entry point for all web requests. It defines the base
 * application path and delegates the execution to the Foundation layer.
 *
 * @author Zantech Team
 * @version 4.0.0
 */

// 0. Initialize Developer Profiler
if (!defined('ZT_BASE_PATH')) {
    define('ZT_BASE_PATH', dirname(__DIR__));
}

$profiler = ZT_BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Foundation' . DIRECTORY_SEPARATOR . 'Profiler.php';
if (is_file($profiler)) {
    require_once $profiler;
    \Foundation\Profiler::start();
}

// 2. Delegate to the Foundation Loader
// The loader handles namespace detection (web/api), security, and kernel booting.
$loader = ZT_BASE_PATH . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Foundation' . DIRECTORY_SEPARATOR . 'AppLoader.php';

if (!is_file($loader) || !is_readable($loader)) {
    http_response_code(500);
    echo 'Zantech Fatal Error: Foundation Loader missing. Check your directory structure.';
    exit;
}

require_once $loader;
