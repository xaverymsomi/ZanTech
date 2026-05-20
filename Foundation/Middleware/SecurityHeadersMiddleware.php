<?php

namespace Foundation\Middleware;

use Closure;
use Foundation\Routing\RouteContext;

/**
 * Enforces browser security headers (HSTS, CSP, X-Frame-Options, etc.)
 */
class SecurityHeadersMiddleware implements Middleware
{
    public function handle(RouteContext $route, Closure $next): mixed
    {
        // 1. Strict-Transport-Security (HSTS)
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');

        // 2. Clickjacking Protection
        header('X-Frame-Options: SAMEORIGIN');

        // 3. MIME-Sniffing Protection
        header('X-Content-Type-Options: nosniff');

        // 4. Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // 5. Permissions Policy (Restrict sensitive browser features)
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

        // 6. Content Security Policy (CSP)
        // Note: Allowing 'unsafe-inline' for styles/scripts due to heavy AngularJS and inline styling usage.
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://code.jquery.com; ";
        $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; ";
        $csp .= "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; ";
        $csp .= "frame-ancestors 'self';";
        
        header("Content-Security-Policy: {$csp}");

        return $next($route);
    }
}
