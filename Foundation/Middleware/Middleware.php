<?php

namespace Foundation\Middleware;

use Closure;
use Foundation\Routing\RouteContext;

interface Middleware
{
    public function handle(RouteContext $route, Closure $next): mixed;
}
