<?php

use Foundation\Routing\Router;
use Http\Request;
use PHPUnit\Framework\TestCase;

final class RouteDefinitionsTest extends TestCase
{
    public function testRouteDefinitionsMapRootToDashboardIndex(): void
    {
        $request = Request::fake([], [], ['REQUEST_URI' => '/'], []);
        $context = (new Router())->context($request);

        $this->assertSame('dashboard', $context->controller());
        $this->assertSame('index', $context->method());
    }

    public function testRouteDefinitionsCaptureParameterizedSegments(): void
    {
        $request = Request::fake([], [], ['REQUEST_URI' => '/settings/profile'], []);
        $context = (new Router())->context($request);

        $this->assertSame('settings', $context->controller());
        $this->assertSame('index', $context->method());
        $this->assertSame(['profile'], $context->params());
    }
}
