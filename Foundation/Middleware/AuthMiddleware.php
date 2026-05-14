<?php

declare(strict_types=1);

namespace Foundation\Middleware;

use Authentication\LoginCheck;
use Closure;
use Foundation\Routing\RouteContext;

final class AuthMiddleware implements Middleware
{
    public function __construct(private readonly LoginCheck $loginCheck = new LoginCheck()) {}

    public function handle(RouteContext $route, Closure $next): mixed
    {
        if ($route->isPublicController()) {
            $this->loginCheck->destroy($route->controller());
        } else {
            $this->loginCheck->protect($route->controller());
        }

        return $next($route);
    }
}
