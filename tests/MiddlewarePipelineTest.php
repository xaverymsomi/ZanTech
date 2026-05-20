<?php



use Foundation\Middleware\Middleware;
use Foundation\Middleware\Pipeline;
use Foundation\Middleware\CsrfMiddleware;
use Foundation\Middleware\RateLimitMiddleware;
use Foundation\Routing\RouteContext;
use Http\Request;
use PHPUnit\Framework\TestCase;
use Exceptions\AuthException;
use Exceptions\ZantechException;

final class MiddlewarePipelineTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        $_SESSION = [];
    }

    public function testPipelineRunsMiddlewareInOrder(): void
    {
        $events = [];
        $route = new RouteContext(Request::fake(), []);

        $middleware = new class($events) implements Middleware {
            public function __construct(private array &$events) {}
            public function handle(RouteContext $route, Closure $next): mixed
            {
                $this->events[] = 'before';
                $result = $next($route);
                $this->events[] = 'after';
                return $result;
            }
        };

        $result = (new Pipeline([$middleware]))->handle($route, function () use (&$events): string {
            $events[] = 'destination';
            return 'ok';
        });

        $this->assertSame('ok', $result);
        $this->assertSame(['before', 'destination', 'after'], $events);
    }

    public function testCsrfMiddlewareAllowsValidHeaderToken(): void
    {
        session_start();
        $_SESSION['csrf_token'] = 'known-token';

        $route = new RouteContext(Request::fake(
            server: [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_CSRF_TOKEN' => 'known-token',
            ]
        ), ['dashboard', 'save']);

        $result = (new CsrfMiddleware())->handle($route, fn (): string => 'ok');

        $this->assertSame('ok', $result);
    }

    public function testCsrfMiddlewareRejectsMissingToken(): void
    {
        session_start();
        $_SESSION['csrf_token'] = 'known-token';

        $route = new RouteContext(Request::fake(
            server: ['REQUEST_METHOD' => 'POST']
        ), ['dashboard', 'save']);

        $this->expectException(AuthException::class);

        (new CsrfMiddleware())->handle($route, fn (): string => 'ok');
    }

    public function testRateLimitMiddlewareThrowsAfterLimit(): void
    {
        session_start();

        $route = new RouteContext(Request::fake(
            server: ['REMOTE_ADDR' => '127.0.0.1']
        ), ['dashboard']);

        $middleware = new RateLimitMiddleware(1);

        $this->assertSame('ok', $middleware->handle($route, fn (): string => 'ok'));

        $this->expectException(ZantechException::class);
        $middleware->handle($route, fn (): string => 'blocked');
    }
}
