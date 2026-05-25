<?php

use Authentication\Session;
use Exceptions\ExceptionHandler;
use Foundation\Zantech;
use Modules\Error\Error;
use Logging\Log;

// Constants are handled in index.php and config.php.

/**
 * Bootstrap abort for missing core includes.
 * Uses Error module UI (not plain text).
 */
if (!function_exists('ztBootstrapAbort')) {
    function ztBootstrapAbort(string $message, int $statusCode = 500): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        $debugVal = $_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG');
        $debug = is_string($debugVal) && in_array(strtolower($debugVal), ['1', 'true', 'yes', 'on'], true);

        $err = $debug
            ? new Error('Zantech Bootstrap Error', $message, null, 'bi-exclamation-triangle-fill')
            : new Error('Application Error', 'Please try again later.', null, 'bi-exclamation-triangle-fill');

        $err->index();
        exit;
    }
}

// Timezone is set in bootstrap/config.php.

// Output buffering (optional but ok)
if (!ob_get_level()) {
    ob_start();
}

/**
 * SINGLE SOURCE for session hardening is Authentication\Session::init()
 * If you rely on Session early (notifications, csrf, etc), keep it here.
 */
if (PHP_SAPI !== 'cli') {
    Session::init();
}

/**
 * Request tracking (Log request number)
 */
if (!empty($_SERVER['ZT_REQUEST_ID']) && is_string($_SERVER['ZT_REQUEST_ID'])) {
    Log::$request_number = $_SERVER['ZT_REQUEST_ID'];
} else {
    try {
        Log::$request_number = bin2hex(random_bytes(16));
    } catch (Throwable) {
        Log::$request_number = (time() . '-' . mt_rand(1000, 9999));
    }
    $_SERVER['ZT_REQUEST_ID'] = Log::$request_number;
}

if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION['ZT_REQUEST_ID'] ??= $_SERVER['ZT_REQUEST_ID'];
}

/**
 * IMPORTANT:
 * Do NOT register set_exception_handler / set_error_handler here anymore.
 * That is already handled globally in public/index.php.
 *
 * Here we only ensure any boot/runtime Throwable is handled gracefully.
 */
try {
    (new Zantech())->init();
} catch (Throwable $e) {
    ExceptionHandler::handle($e);
}
