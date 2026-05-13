<?php

declare(strict_types=1);

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

// 1. Define the absolute base path of the project
if (!defined('ZT_BASE_PATH')) {
    define('ZT_BASE_PATH', dirname(__DIR__));
}

// 2. Delegate to the Foundation Loader
// The loader handles namespace detection (web/api), security, and kernel booting.
$loader = ZT_BASE_PATH . DIRECTORY_SEPARATOR . 'Foundation' . DIRECTORY_SEPARATOR . 'AppLoader.php';

if (!is_file($loader) || !is_readable($loader)) {
    http_response_code(500);
    echo 'Zantech Fatal Error: Foundation Loader missing. Check your directory structure.';
    exit;
}

require_once $loader;
