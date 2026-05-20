<?php

namespace Foundation\Routing;

use Http\Request;

final class Router
{
    public function context(Request $request, int $offset = 0): RouteContext
    {
        $segments = RouterSecurity::parseSegments($request->path());

        $routeDefinitions = $this->loadRouteDefinitions();
        if (!empty($routeDefinitions)) {
            $mapped = $this->matchRouteDefinition($request->path(), $routeDefinitions);
            if ($mapped !== null) {
                $segments = $mapped;
            }
        }

        return new RouteContext($request, $segments, $offset);
    }

    private function loadRouteDefinitions(): array
    {
        $routePath = rtrim(ZT_APP_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'routes.php';
        if (!is_file($routePath)) {
            return [];
        }

        $definitions = require $routePath;
        return is_array($definitions) ? $definitions : [];
    }

    private function matchRouteDefinition(string $path, array $definitions): ?array
    {
        $path = $this->normalizeRoutePath($path);

        foreach ($definitions as $pattern => $target) {
            $pattern = $this->normalizeRoutePath($pattern);

            if (!str_contains($pattern, '{')) {
                if ($pattern === $path) {
                    return $this->mapTargetToSegments($target, []);
                }

                continue;
            }

            $match = $this->matchParameterizedRoute($pattern, $path);
            if ($match !== null) {
                return $this->mapTargetToSegments($target, $match);
            }
        }

        return null;
    }

    private function matchParameterizedRoute(string $pattern, string $path): ?array
    {
        $patternSegments = array_filter(explode('/', trim($pattern, '/')));
        $pathSegments = array_filter(explode('/', trim($path, '/')));

        if (count($patternSegments) !== count($pathSegments)) {
            return null;
        }

        $params = [];
        foreach ($patternSegments as $i => $patternSeg) {
            if (str_starts_with($patternSeg, '{') && str_ends_with($patternSeg, '}')) {
                $params[] = $pathSegments[$i];
            } elseif ($patternSeg !== $pathSegments[$i]) {
                return null;
            }
        }

        return $params;
    }

    private function normalizeRoutePath(string $path): string
    {
        $raw = (string) parse_url($path, PHP_URL_PATH);
        $normalized = '/' . trim($raw, '/');
        return $normalized === '' ? '/' : $normalized;
    }

    private function mapTargetToSegments(string $target, array $params): array
    {
        $target = trim($target);
        $parts = explode('@', $target, 2);
        $controller = strtolower($parts[0]);
        $method = $parts[1] ?? 'index';

        return array_merge([$controller, $method], $params);
    }
}
