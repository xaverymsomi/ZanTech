<?php

declare(strict_types=1);

namespace Foundation\Middleware;

use Closure;
use Foundation\Routing\RouteContext;

final class Pipeline
{
    /**
     * @param Middleware[] $middleware
     */
    public function __construct(private readonly array $middleware) {}

    public function handle(RouteContext $route, Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            fn (Closure $next, Middleware $middleware): Closure =>
                fn (RouteContext $route): mixed => $middleware->handle($route, $next),
            $destination
        );

        return $pipeline($route);
    }
}
