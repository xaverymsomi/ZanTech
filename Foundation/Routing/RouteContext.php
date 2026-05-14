<?php

declare(strict_types=1);

namespace Foundation\Routing;

use Http\Request;

final class RouteContext
{
    /**
     * @param string[] $segments
     */
    public function __construct(
        public readonly Request $request,
        public readonly array $segments,
        public readonly int $offset = 0
    ) {}

    public function controller(): string
    {
        return strtolower($this->segments[$this->offset] ?? '');
    }

    public function method(): string
    {
        return (string)($this->segments[$this->offset + 1] ?? 'index');
    }

    public function params(): array
    {
        return array_slice($this->segments, $this->offset + 2);
    }

    public function isPublicController(): bool
    {
        return in_array($this->controller(), [ZT_ROUTE_LOGIN, ZT_ROUTE_LOGOUT, 'autorun'], true);
    }
}
