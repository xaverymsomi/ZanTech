<?php

declare(strict_types=1);

namespace Foundation\Middleware;

use Authentication\Auth;
use Closure;
use Exceptions\ZantechException;
use Foundation\Routing\RouteContext;
use Logging\Log;

final class RateLimitMiddleware implements Middleware
{
    public function __construct(private readonly int $maxPerMinute = ZT_RATE_LIMIT_MAX) {}

    public function handle(RouteContext $route, Closure $next): mixed
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            return $next($route);
        }

        $bucket = date('YmdHi');
        $key = Auth::isLogged()
            ? 'u:' . (string)(Auth::id() ?? 'unknown')
            : 'ip:' . $route->request->ip();

        $_SESSION['ZT_RATE'] ??= [];
        $_SESSION['ZT_RATE'][$key] ??= [];
        $_SESSION['ZT_RATE'][$key][$bucket] = (int)($_SESSION['ZT_RATE'][$key][$bucket] ?? 0) + 1;

        if (count($_SESSION['ZT_RATE'][$key]) > 5) {
            ksort($_SESSION['ZT_RATE'][$key]);
            $_SESSION['ZT_RATE'][$key] = array_slice($_SESSION['ZT_RATE'][$key], -5, null, true);
        }

        $count = (int)$_SESSION['ZT_RATE'][$key][$bucket];
        if ($count > $this->maxPerMinute) {
            Log::sysLog("RATE-LIMIT TRIGGERED key={$key} count={$count}");
            throw new ZantechException(
                "Rate limit exceeded key={$key} bucket={$bucket} count={$count}",
                'Too many requests. Please slow down and try again.',
                429,
                ['key' => $key, 'bucket' => $bucket, 'count' => $count]
            );
        }

        return $next($route);
    }
}
