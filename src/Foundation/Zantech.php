<?php

namespace Foundation;

use Authentication\Auth;
use Authentication\DualControl;
use Authentication\LoginCheck;
use Exceptions\RedirectException;
use Exceptions\RouterException;
use Exceptions\ValidationException;
use Foundation\Middleware\AuthMiddleware;
use Foundation\Middleware\AuthThrottlingMiddleware;
use Foundation\Middleware\CsrfMiddleware;
use Foundation\Middleware\Pipeline;
use Foundation\Middleware\RateLimitMiddleware;
use Foundation\Middleware\SecurityHeadersMiddleware;
use Foundation\Middleware\AuditMiddleware;
use Foundation\Middleware\SessionSecurityMiddleware;
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
        $this->modulesPath = rtrim(ZT_APP_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR;
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

        Log::info([
            'stage'      => 'request-initialized',
            'uri'        => $this->rawUrl,
            'segments'   => $this->segments,
            'query'      => $_GET,
            'payload'    => $_POST,
            'request_id' => $_SERVER['ZT_REQUEST_ID'] ?? null,
        ]);

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
        $this->runMiddleware(fn(): null => $this->dispatchRoute());

        Log::sysLog("REQUEST-END: " . ($controllerSlug ?: 'root'));
    }

    private function runMiddleware(callable $destination): mixed
    {
        $middleware = [
            new SecurityHeadersMiddleware(),
            new AuditMiddleware(),
            new SessionSecurityMiddleware(),
            new AuthThrottlingMiddleware()
        ];

        if ($this->rateLimitEnabled) {
            $middleware[] = new RateLimitMiddleware($this->rateLimitMaxPerMinute);
        }
        if ($this->csrfEnabled) {
            $middleware[] = new CsrfMiddleware();
        }
        $middleware[] = new AuthMiddleware(new LoginCheck());

        return (new Pipeline($middleware))->handle($this->route, fn(): mixed => $destination());
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
        $payload = $this->getRequestPayloadForLog();
        $safe = $payload !== null ? RouterSecurity::redactSensitive($payload) : null;

        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            Log::savePlainLog(str_repeat('*', 150));
            Log::sysLog("REQUEST-START");
            Log::sysLog("CALLER: " . (\Authentication\Auth::user()['txt_username'] ?? 'GUEST'));
            Log::sysLog("URL-RAW: " . $this->rawUrl);
            Log::sysLog("URL-PARSED: " . json_encode($this->segments, JSON_UNESCAPED_SLASHES));
            Log::sysLog("AUTH-STATE: " . json_encode(Auth::isLogged()));
            Log::sysLog("MODE: WEB");

            if ($safe !== null) {
                Log::sysLog("REQUEST-PAYLOAD: " . json_encode($safe, JSON_UNESCAPED_SLASHES));
            } else {
                Log::sysLog("REQUEST-PAYLOAD: <none>");
            }
        }

        if ($safe !== null) {
            $this->scanMaliciousInputs($safe);
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
            Log::info([
                'stage'      => 'controller-loaded',
                'controller' => get_class($this->controllerInstance),
                'module'     => $this->currentControllerSlug(),
                'method'     => $this->currentMethodSlug(),
                'request_id' => $_SERVER['ZT_REQUEST_ID'] ?? null,
            ]);
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
        $fileToLoad = $this->modulesPath . $moduleName . DIRECTORY_SEPARATOR . $moduleName . '.php';

        if (!is_file($fileToLoad)) {
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

        // 1. Check if the controller itself declares this method as Dual Control required
        $codeRequired = false;
        if ($this->controllerInstance instanceof BaseModuleController) {
            if ($this->controllerInstance->isDualControlRequired($method)) {
                $codeRequired = true;
                Log::sysLog("DUAL-CONTROL: Required via BaseModuleController property for {$module}::{$method}");
            }
        }

        // 2. Check Database rules
        $dc = new DualControl($module, $method);
        $dbRequired = $dc->getResult();

        Log::sysLog("DUAL-CONTROL RESULT: code={$codeRequired}, db={$dbRequired}");

        // If either requires dual control, the action is blocked until approved
        if (!$codeRequired && !$dbRequired) {

            $this->callMethod();
        } else {
            // If it was only code-required but not in DB, we still need to create the log entry
            if ($codeRequired && !$dbRequired) {
                // We use a virtual dual_activity_id (0) or we could auto-insert a rule.
                // For this framework, we'll allow DualControl to handle the log creation 
                // by passing a flag to force creation even without a DB rule.
                $dc->forceCreateApprovalRequest();
            }
            
            // Output a "Pending Approval" response if it's an AJAX request
            if ($this->request->isAjax()) {
                \Http\Response::json(['status' => 'pending', 'message' => 'Action queued for approval.'], 202)->send();
                exit;
            } else {
                // For standard requests, set a session message and redirect
                \Authentication\Session::set('returned', 6000); // generic "Pending" code
                header("Location: " . URL . "/dashboard");
                exit;
            }
        }
    }

    private function callMethod(): void
    {
        $methodSlug = $this->currentMethodSlug();
        RouterSecurity::validateMethodName($methodSlug);
        if (!is_object($this->controllerInstance)) {
            throw new RouterException('Controller not initialized', 'Resource Not Found', 404);
        }

        Log::debug([
            'stage'      => 'method-invocation',
            'controller' => get_class($this->controllerInstance),
            'method'     => $methodSlug,
            'params'     => $this->currentParams(),
            'request_id' => $_SERVER['ZT_REQUEST_ID'] ?? null,
        ]);

        // 1. Try raw slug (get_all_menus)
        $method = $methodSlug;
        if (!method_exists($this->controllerInstance, $method)) {
            // 2. Try camelCase version (getAllMenus)
            $method = RouterSecurity::slugToCamel($methodSlug);
        }

        RouterSecurity::assertPublicCallable($this->controllerInstance, $method);

        $params = RouterSecurity::sanitizeParams($this->currentParams());

        try {
            $result = call_user_func_array([$this->controllerInstance, $method], $params);
        } catch (Throwable $e) {
            Log::exception($e, 'CONTROLLER_METHOD_EXCEPTION', [
                'controller' => get_class($this->controllerInstance),
                'method'     => $method,
                'params'     => $params,
                'request_id' => $_SERVER['ZT_REQUEST_ID'] ?? null,
            ]);
            throw $e;
        }

        Log::debug([
            'stage'        => 'method-returned',
            'controller'   => get_class($this->controllerInstance),
            'method'       => $method,
            'responseType' => is_object($result) ? get_class($result) : gettype($result),
            'request_id'   => $_SERVER['ZT_REQUEST_ID'] ?? null,
        ]);

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
