<?php

declare(strict_types=1);

namespace Library;

use Authentication\Auth;
use Authentication\LoginCheck;
use Exceptions\RedirectException;
use Exceptions\RouterException;
use Exceptions\ValidationException;
use Foundation\Middleware\AuthMiddleware;
use Foundation\Middleware\CsrfMiddleware;
use Foundation\Middleware\Pipeline;
use Foundation\Middleware\RateLimitMiddleware;
use Foundation\Routing\Router;
use Foundation\Routing\RouterSecurity;
use Foundation\Routing\RouteContext;
use Http\Request;
use Http\Response;
use Config\Config;
use Modules\Login\Login;
use Throwable;
use Logging\Log;

final class Zantech
{
    private array $segments = [];
    private string $rawUrl = '';
    private Request $request;
    private RouteContext $route;
    private mixed $controllerInstance = null;
    private string $modulesPath;
    private string $publicPath;
    private array $allowedModules = [];
    private int $routeOffset = 0;
    private bool $rateLimitEnabled = false;
    private int  $rateLimitMaxPerMinute = ZT_RATE_LIMIT_MAX;
    private bool $csrfEnabled = false;

    public function __construct()
    {
        $this->request = Request::capture();
        $this->modulesPath = rtrim(ZT_APP_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR;
        $this->publicPath  = rtrim(ZT_BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR;

        $this->rateLimitEnabled = Config::get('ZT_RATE_LIMIT', false);
        $this->csrfEnabled      = Config::get('ZT_CSRF_ENABLED', false);

        // Optional module allowlist: "login,dashboard,users"
        $allow = (string)($_ENV['ZT_ALLOWED_MODULES'] ?? '');
        if ($allow !== '') {
            $items = array_filter(array_map('trim', explode(',', $allow)));
            $this->allowedModules = array_values(array_unique(array_map('strtolower', $items)));
        }
    }

    public function init(): void
    {
        $this->route = (new Router())->context($this->request, $this->routeOffset);
        $this->segments = $this->route->segments;

        $this->rawUrl = $this->request->uri();


        // If rewrite accidentally sent an existing static file to PHP, let IIS serve it by returning 404 only
        // when file truly exists (prevents breaking app routes like /assets that are virtual).
        if ($this->isExistingPublicFileRequest($this->rawUrl)) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        // 2026-01-21 Fixed: Block missing static files (e.g. favicon.ico) from falling through to the controller router
        if (RouterSecurity::isStaticRequest($this->rawUrl)) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $this->logRequestStart();

        $controllerSlug = $this->currentControllerSlug();
        $this->runMiddleware(fn (): null => $this->dispatchRoute());

        Log::sysLog("REQUEST-END: " . ($controllerSlug ?: 'root'));
    }

    private function runMiddleware(callable $destination): mixed
    {
        $middleware = [];
        if ($this->rateLimitEnabled) {
            $middleware[] = new RateLimitMiddleware($this->rateLimitMaxPerMinute);
        }
        if ($this->csrfEnabled) {
            $middleware[] = new CsrfMiddleware();
        }
        $middleware[] = new AuthMiddleware(new LoginCheck());

        return (new Pipeline($middleware))->handle($this->route, fn (): mixed => $destination());
    }

    private function dispatchRoute(): null
    {
        if (!$this->route->isPublicController()) {
            $this->routeAuthenticated();
        } else {
            $this->routePublic();
        }

        return null;
    }


    private function isExistingPublicFileRequest(string $rawUrl): bool
    {
        $path = (string)(parse_url($rawUrl, PHP_URL_PATH) ?? '');
        if ($path === '' || $path === '/') return false;

        $path = rawurldecode($path);
        $path = str_replace(["\0", '\\'], ['', '/'], $path);
        $path = '/' . ltrim($path, '/');

        // join safely into public dir
        $candidate = $this->publicPath . ltrim($path, '/');
        $real = realpath($candidate);

        if ($real === false) return false;

        $publicReal = realpath($this->publicPath);
        if ($publicReal === false) return false;

        $prefix = rtrim($publicReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncmp($real, $prefix, strlen($prefix)) !== 0) return false;

        return is_file($real);
    }

    private function currentControllerSlug(): string
    {
        return $this->route->controller();
    }

    private function currentMethodSlug(): string
    {
        return $this->route->method();
    }

    private function currentParams(): array
    {
        return $this->route->params();
    }

    private function logRequestStart(): void
    {
        Log::savePlainLog(str_repeat('*', 150));
        Log::sysLog("REQUEST-START");
        Log::sysLog("CALLER: " . (\Authentication\Auth::user()['txt_username'] ?? 'GUEST'));
        Log::sysLog("URL-RAW: " . $this->rawUrl);
        Log::sysLog("URL-PARSED: " . json_encode($this->segments, JSON_UNESCAPED_SLASHES));
        Log::sysLog("AUTH-STATE: " . json_encode(Auth::isLogged()));
        Log::sysLog("MODE: WEB");

        $payload = $this->getRequestPayloadForLog();
        if ($payload !== null) {
            $safe = RouterSecurity::redactSensitive($payload);
            Log::sysLog("REQUEST-PAYLOAD: " . json_encode($safe, JSON_UNESCAPED_SLASHES));
            $this->scanMaliciousInputs($safe);
        } else {
            Log::sysLog("REQUEST-PAYLOAD: <none>");
        }
    }

    private function getRequestPayloadForLog(): ?array
    {
        if (!empty($_POST)) return $_POST;

        // Avoid logging the routing param "url" (it duplicates URL-RAW anyway)
        if (!empty($_GET)) {
            $copy = $_GET;
            unset($copy['url']);
            return $copy ?: null;
        }

        return null;
    }

    private function scanMaliciousInputs(array $data): void
    {

        $scan = function ($value) use (&$scan): void {
            $patterns = [
                '/<script\b[^>]*>/i',
                '/on\w+\s*=/i',
                '/javascript\s*:/i',
            ];
            if (is_array($value)) {
                foreach ($value as $v) $scan($v);
                return;
            }
            if (!is_string($value)) return;

            foreach ($patterns as $p) {
                if (preg_match($p, $value)) {
                    Log::sysLog("BLOCKED INPUT PATTERN DETECTED");
                    throw new ValidationException([
                        'input' => ['The request contains invalid or dangerous content.']
                    ]);
                }
            }
        };

        $scan($data);
    }

    private function routeAuthenticated(): void
    {
        Log::sysLog("AUTH-USER: " . json_encode(Auth::user()));

        // Just load it normally, no special-case
        if ($this->loadController()) {
            $this->handleDualControl();
        }
    }


    private function routePublic(): void
    {
        if ($this->currentControllerSlug() === '') {
            if (Auth::isLogged()) {
                throw new RedirectException('dashboard');
            }
            $this->loadLogin();
            return;
        }

        if ($this->loadController()) {
            $this->callMethod();
        }
    }


    private function loadController(): bool
    {
        $moduleSlug = $this->currentControllerSlug();
        RouterSecurity::validateControllerSlug($moduleSlug);

        if (!empty($this->allowedModules) && !in_array($moduleSlug, $this->allowedModules, true)) {
            throw new RouterException("Blocked module: {$moduleSlug}", 'Resource Not Found', 404);
        }

        $moduleName = RouterSecurity::slugToStudly($moduleSlug); // Dashboard
        $classStudly = "Modules\\{$moduleName}\\{$moduleName}";

        // Define potential file locations
        $candidates = [
            $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . $moduleName . '.php',
            $this->modulesPath . strtolower($moduleSlug) . DIRECTORY_SEPARATOR . strtolower($moduleSlug) . '.php',
        ];

        $fileToLoad = null;
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $fileToLoad = $candidate;
                break;
            }
        }

        if ($fileToLoad === null) {
            throw new RouterException(
                "Controller file not found for module={$moduleSlug}.",
                'Resource Not Found',
                404,
                ['module' => $moduleSlug, 'searched' => $candidates]
            );
        }

        require_once $fileToLoad;

        // Prefer the new class naming
        if (class_exists($classStudly)) {
            $class = $classStudly;
        } else {
            // If legacy file defines a different class name, fail loudly (so you can fix properly)
            throw new RouterException(
                "Controller class not found: {$classStudly} (file loaded: {$fileToLoad})",
                'Failed to load requested module.',
                500,
                ['expected_class' => $classStudly, 'file' => $fileToLoad]
            );
        }

        try {
            $this->controllerInstance = new $class();
        } catch (Throwable $e) {
            throw new RouterException(
                "Controller init failed: {$class}",
                'Failed to initialize requested module.',
                500,
                ['class' => $class],
                $e
            );
        }

        return true;
    }

    private function handleDualControl(): void
    {
        $module = $this->segments[$this->routeOffset] ?? '';
        $method = $this->segments[$this->routeOffset + 1] ?? '';

        $dc = new DualControl($module, $method);
        $result = $dc->getResult();

        Log::sysLog("DUAL-CONTROL RESULT: " . json_encode($result));

        if (!$result) {
            $this->callMethod();
        }
    }

    private function callMethod(): void
    {
        $methodSlug = $this->currentMethodSlug();
        RouterSecurity::validateMethodName($methodSlug);

        if (!is_object($this->controllerInstance)) {
            throw new RouterException('Controller not initialized', 'Resource Not Found', 404);
        }

        // 1. Try raw slug (get_all_menus)
        $method = $methodSlug;
        if (!method_exists($this->controllerInstance, $method)) {
            // 2. Try camelCase version (getAllMenus)
            $method = RouterSecurity::slugToCamel($methodSlug);
        }

        RouterSecurity::assertPublicCallable($this->controllerInstance, $method);

        $params = RouterSecurity::sanitizeParams($this->currentParams());

        $result = call_user_func_array([$this->controllerInstance, $method], $params);

        if ($result instanceof Response) {
            $result->send();
        }
    }

    private function loadLogin(): void
    {
        try {
            (new Login())->index();
        } catch (Throwable $e) {
            throw new RouterException('Failed to load login', 'Resource Not Found', 404, [], $e);
        }
    }
}
