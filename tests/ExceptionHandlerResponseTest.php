<?php

declare(strict_types=1);

use Exceptions\AuthException;
use Exceptions\ExceptionHandler;
use Exceptions\RedirectException;
use Exceptions\RouterException;
use PHPUnit\Framework\TestCase;

final class ExceptionHandlerResponseTest extends TestCase
{
    private array $serverBackup;

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
        $_SERVER['ZT_REQUEST_ID'] = 'test-request';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testJsonRenderUsesConsistentShape(): void
    {
        $_SERVER['HTTP_ACCEPT'] = 'application/json';

        $response = ExceptionHandler::render(
            new RouterException('Missing route', 'Not found.', 404, ['errors' => ['route' => ['missing']]])
        );

        $this->assertSame(404, $response->status());
        $this->assertSame('application/json; charset=UTF-8', $response->headers()['Content-Type']);

        $payload = json_decode($response->content(), true);
        $this->assertSame(404, $payload['code']);
        $this->assertSame('Not found.', $payload['message']);
        $this->assertSame('test-request', $payload['request_id']);
        $this->assertSame(['route' => ['missing']], $payload['errors']);
    }

    public function testHtmlAuthRenderRedirectsToLogin(): void
    {
        unset($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_X_REQUESTED_WITH']);

        $response = ExceptionHandler::render(new AuthException());

        $this->assertSame(302, $response->status());
        $this->assertSame('/login', $response->headers()['Location']);
    }

    public function testRedirectExceptionSanitizesTarget(): void
    {
        unset($_SERVER['HTTP_ACCEPT'], $_SERVER['HTTP_X_REQUESTED_WITH']);

        $response = ExceptionHandler::render(new RedirectException('//evil.example'));

        $this->assertSame(302, $response->status());
        $this->assertSame('/dashboard', $response->headers()['Location']);
    }
}
