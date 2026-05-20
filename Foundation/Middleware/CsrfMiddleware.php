<?php

namespace Foundation\Middleware;

use Closure;
use Exceptions\AuthException;
use Foundation\Routing\RouteContext;
use Logging\Log;

final class CsrfMiddleware implements Middleware
{
    public function handle(RouteContext $route, Closure $next): mixed
    {
        $method = $route->request->method();
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true) || session_status() !== \PHP_SESSION_ACTIVE) {
            return $next($route);
        }

        $sessionToken = $_SESSION['csrf_token'] ?? null;
        $provided = $route->request->header('X-CSRF-Token') ?: $route->request->post('_token');

        Log::sysLog("CSRF-DEBUG: session=" . ($sessionToken ? 'exists' : 'null') . " provided=" . ($provided ? 'exists' : 'null'));

        if (empty($sessionToken) || empty($provided) || !hash_equals((string)$sessionToken, (string)$provided)) {
            Log::sysLog("CSRF TOKEN FAIL method={$method}");
            throw new AuthException('CSRF validation failed');
        }

        return $next($route);
    }
}
