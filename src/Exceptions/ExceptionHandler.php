<?php

namespace Exceptions;

use Logging\Log;
use Http\Response;
use Modules\Error\Error;
use Throwable;

final class ExceptionHandler
{
    private const SEVERITY_MAP = [
        'info' => ['icon' => 'bi-info-circle-fill', 'level' => 'INFO'],
        'warning' => ['icon' => 'bi-exclamation-circle-fill', 'level' => 'WARNING'],
        'danger' => ['icon' => 'bi-exclamation-triangle-fill', 'level' => 'ERROR'],
    ];

    private const EXCEPTION_SEVERITY = [
        ValidationException::class => 'warning',
        AuthException::class       => 'warning',
        ForbiddenException::class  => 'warning',
        NotFoundException::class   => 'info',
        RouterException::class     => 'danger',
        ZantechException::class    => 'danger',
        DatabaseException::class   => 'danger',
    ];

    public static function handle(Throwable $e): void
    {
        self::render($e)->send();
        exit;
    }

    public static function render(Throwable $e): Response
    {
        $requestId = isset($_SERVER['ZT_REQUEST_ID']) ? (string)$_SERVER['ZT_REQUEST_ID'] : null;

        if (!$e instanceof ZantechException) {
            $fullMessage = $e->getMessage();
            if ($prev = $e->getPrevious()) {
                $fullMessage .= " (Original: " . $prev->getMessage() . ")";
            }

            $e = new ZantechException(
                $fullMessage,
                'An unexpected error occurred.',
                500,
                [
                    'type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
                $e
            );
        }

        $severity = self::resolveSeverity($e);
        $icon     = self::SEVERITY_MAP[$severity]['icon'];
        $logLevel = self::SEVERITY_MAP[$severity]['level'];

        try {
            Log::exception($e, 'APPLICATION_EXCEPTION');
        } catch (Throwable) {
            error_log($e->getMessage());
        }

        if (self::expectsJson()) {
            return self::jsonResponse($e, $requestId);
        }

        return self::htmlResponse($e, $severity, $icon);
    }

    private static function expectsJson(): bool
    {
        $uri  = (string)($_SERVER['REQUEST_URI'] ?? '');
        $path = (string)(parse_url($uri, PHP_URL_PATH) ?? '');

        if (str_starts_with(trim($path, '/'), 'api/')) {
            return true;
        }

        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        if (str_contains($accept, 'application/json')) {
            return true;
        }

        return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    private static function jsonResponse(ZantechException $e, ?string $requestId): Response
    {
        $ctx = $e->getContext();
        $redirect = isset($ctx['redirect']) ? self::sanitizeRedirectTarget((string)$ctx['redirect']) : null;

        return Response::json([
            // consistent shape
            'code'       => $e->getStatusCode(),               // you can replace later with internal error codes
            'message'    => $e->getPublicMessage(),
            'errors'     => $ctx['errors'] ?? null,
            'request_id' => $requestId,
            'redirect'   => $redirect,
        ], $e->getStatusCode());
    }

    private static function htmlResponse(ZantechException $e, string $severity, string $icon): Response
    {
        $ctx = $e->getContext();

        // 401 -> redirect to login (WEB only)
        if ($e->getStatusCode() === 401) {
            $loginRoute = defined('ZT_ROUTE_LOGIN') ? ZT_ROUTE_LOGIN : 'login';
            return self::redirectTo('/' . $loginRoute, 302);
        }

        // 302 -> redirect intent
        if ($e->getStatusCode() === 302 && !empty($ctx['redirect'])) {
            $fallback = defined('ZT_ROUTE_DASHBOARD') ? ZT_ROUTE_DASHBOARD : 'dashboard';
            $target = self::sanitizeRedirectTarget((string)$ctx['redirect']) ?? $fallback;
            return self::redirectTo('/' . ltrim($target, '/'), 302);
        }

        $err = new Error(
            self::titleFromSeverity($severity),
            $e->getPublicMessage(),
            null,
            $icon
        );

        ob_start();
        $err->index();
        return Response::html((string)ob_get_clean(), $e->getStatusCode());
    }

    /**
     * Allow only safe internal relative paths like:
     * - dashboard
     * - dashboard/index
     * - transactions/view/123
     * Reject:
     * - //evil.com
     * - http://evil.com
     * - \r\n header injection
     */
    private static function sanitizeRedirectTarget(string $target): ?string
    {
        $target = trim($target);

        // strip CRLF to prevent header injection
        $target = str_replace(["\r", "\n"], '', $target);

        // reject absolute URLs or scheme-relative
        if (preg_match('#^(https?:)?//#i', $target)) {
            return null;
        }

        // normalize leading slash off
        $target = ltrim($target, '/');

        // allow only [a-z0-9/_-]
        if ($target === '' || !preg_match('#^[a-z0-9/_-]+$#i', $target)) {
            return null;
        }

        // optional: allowlist top-level controllers
        $first = strtolower(explode('/', $target, 2)[0]);

        $allowedFirst = [
            defined('ZT_ROUTE_DASHBOARD') ? ZT_ROUTE_DASHBOARD : 'dashboard',
            defined('ZT_ROUTE_LOGIN') ? ZT_ROUTE_LOGIN : 'login',
            defined('ZT_ROUTE_LOGOUT') ? ZT_ROUTE_LOGOUT : 'logout',
            'autorun'
        ];

        if (!in_array($first, $allowedFirst, true)) {
            return null;
        }

        return $target;
    }

    private static function redirectTo(string $path, int $statusCode = 302): Response
    {
        // Use URL constant if defined, else fallback to relative redirect
        $base = defined('URL') ? rtrim((string)URL, '/') : '';
        $location = $base . $path;

        return Response::redirect($location, $statusCode);
    }

    private static function resolveSeverity(ZantechException $e): string
    {
        foreach (self::EXCEPTION_SEVERITY as $class => $severity) {
            if ($e instanceof $class) {
                return $severity;
            }
        }
        return 'danger';
    }

    private static function titleFromSeverity(string $severity): string
    {
        return match ($severity) {
            'info'    => 'Information',
            'warning' => 'Attention Required',
            default   => 'Application Error',
        };
    }
}
