<?php

use Logging\Log;
use Exceptions\ZantechException;
use Exceptions\ExceptionHandler;

function mxPublicError(int $errno, string $errstr, string $errfile, int $errline): bool
{
    // Respect error_reporting level
    if (!(error_reporting() & $errno)) {
        return false;
    }

    // ✅ Ignore deprecations (PHP 8.2 dynamic property, etc.) so dev doesn’t break constantly
    if (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        try {
            Log::sysLog([
                'type'      => 'PHP_DEPRECATED',
                'errno'     => $errno,
                'message'   => $errstr,
                'file'      => $errfile,
                'line'      => $errline,
                'request_id'=> $_SERVER['ZT_REQUEST_ID'] ?? null,
            ]);
        } catch (Throwable) {}
        return true; // handled
    }

    // Log full internal details
    try {
        Log::sysLog([
            'type'       => 'PHP_ERROR',
            'errno'      => $errno,
            'message'    => $errstr,
            'file'       => $errfile,
            'line'       => $errline,
            'request_id' => $_SERVER['ZT_REQUEST_ID'] ?? null,
        ]);
    } catch (Throwable) {}

    // Convert PHP error → Exception
    throw new ZantechException(
        $errstr,
        'A system error occurred.',
        500,
        [
            'errno' => $errno,
            'file'  => $errfile,
            'line'  => $errline,
        ]
    );
}

function mxFatalErrorHandler(): void
{
    $error = error_get_last();
    if ($error === null) return;

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array((int)($error['type'] ?? 0), $fatalTypes, true)) return;

    try {
        Log::sysLog([
            'type'       => 'PHP_FATAL',
            'message'    => $error['message'] ?? 'Fatal error',
            'file'       => $error['file'] ?? 'unknown',
            'line'       => $error['line'] ?? 0,
            'request_id' => $_SERVER['ZT_REQUEST_ID'] ?? null,
        ]);
    } catch (Throwable) {}

    $ex = new ZantechException(
        (string)($error['message'] ?? 'Fatal error'),
        'A fatal system error occurred.',
        500,
        [
            'file' => $error['file'] ?? null,
            'line' => $error['line'] ?? null,
        ]
    );

    // ✅ Properly delegate to your central handler
    if (class_exists(ExceptionHandler::class)) {
        ExceptionHandler::handle($ex);
        return;
    }

    // Fallback (should be rare)
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'A fatal system error occurred.';
    exit;
}
