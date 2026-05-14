<?php

declare(strict_types=1);

namespace Foundation\Routing;

use Http\Request;

final class Router
{
    public function context(Request $request, int $offset = 0): RouteContext
    {
        $segments = RouterSecurity::parseSegments($request->path());
        return new RouteContext($request, $segments, $offset);
    }
}
