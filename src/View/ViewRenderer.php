<?php



namespace View;

use AllowDynamicProperties;
use Authentication\Auth;
use Authentication\Session;
use Exceptions\ZantechException;
use Logging\Log;

#[AllowDynamicProperties]
class ViewRenderer
{
    private static int $renderDepth = 0;
    private const MAX_RENDER_DEPTH = 10;

    public function __construct(private readonly ?string $appRoot = null) {}

    public function render(string $module, string $view, bool $noLayout = false): void
    {
        $this->enterRenderGuard('render');

        try {
            Session::init();

            $module = trim($module, "/\\");
            $view = trim($view, "/\\");
            Log::debug("RENDER_REQUEST: {$module}/{$view}");
            $viewPath = $this->resolveTemplatePath($module, $view);
            Log::debug("RESOLVED_VIEW_PATH: {$viewPath}");

            $this->assertFileExists($viewPath, "View not found: {$module}/{$view}.php");

            if ($noLayout || $this->isXhrRequest()) {
                if (!headers_sent()) {
                    header('Content-Type: text/html; charset=UTF-8');
                }
                $ui = \View\Component::class;
                require $viewPath;
                return;
            }

            $isLogged = Auth::isLogged() || (bool)Session::get('rp_signed_in');

            if (!$isLogged && strtolower($module) === 'login') {
                $this->renderLoginLayout($viewPath);
                return;
            }

            $this->renderFullLayout($viewPath);
        } finally {
            $this->leaveRenderGuard();
        }
    }

    public function renderFull(string $path): void
    {
        $this->enterRenderGuard('renderFull');

        try {
            $file = $this->appPath(ltrim($path, "/\\"));
            $this->assertFileExists($file, "Full view missing: {$path}");

            if ($this->isXhrRequest()) {
                $ui = \View\Component::class;
                require $file;
                return;
            }

            $header = $this->appPath('resources/views/header.php');
            $body = $this->appPath('resources/views/body.php');
            $footer = $this->appPath('resources/views/footer.php');

            $this->assertFileExists($header, 'Missing header.php');
            $this->assertFileExists($body, 'Missing body.php');
            $this->assertFileExists($footer, 'Missing footer.php');

            $ui = \View\Component::class;
            require $header;
            require $body;
            require $file;
            require $footer;

            if (class_exists('\\Foundation\\Profiler')) {
                echo \Foundation\Profiler::renderToolbar();
            }
        } finally {
            $this->leaveRenderGuard();
        }
    }

    public function renderJson(string $module, string $view): void
    {
        $this->enterRenderGuard('renderJson');

        try {
            $module = trim($module, "/\\");
            $view = trim($view, "/\\");
            Log::debug("RENDER_JSON_REQUEST: {$module}/{$view}");
            $viewPath = $this->resolveTemplatePath($module, $view);
            Log::debug("RESOLVED_JSON_PATH: {$viewPath}");

            if (!is_file($viewPath)) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'error' => 'JSON template missing',
                ]);
                return;
            }

            require $viewPath;
        } finally {
            $this->leaveRenderGuard();
        }
    }

    public static function e(?string $val): string
    {
        return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
    }

    private function renderFullLayout(string $viewPath): void
    {
        $header = $this->appPath('resources/views/header.php');
        $body = $this->appPath('resources/views/body.php');
        $footer = $this->appPath('resources/views/footer.php');

        Log::debug("RENDER_FULL_LAYOUT: {$viewPath}");

        $this->assertFileExists($header, 'Missing header.php');
        $this->assertFileExists($body, 'Missing body.php');
        $this->assertFileExists($footer, 'Missing footer.php');

        ob_start();
        $ui = \View\Component::class;
        require $viewPath;
        $this->content = ob_get_clean();

        try {
            $this->dynamicStyles = \View\DataView::getStyles();
        } catch (\Throwable $e) {
            $this->dynamicStyles = '';
        }

        // Inject dynamic launcher modules for the global Apps Modal
        try {
            $dashboardModel = new \Modules\Dashboard\Dashboard_Model();
            $this->appsModules = $dashboardModel->getLauncherModules();
        } catch (\Throwable $e) {
            $this->appsModules = []; // Fallback
        }

        require $header;
        require $body;
        require $footer;

        if (class_exists('\\Foundation\\Profiler')) {
            echo \Foundation\Profiler::renderToolbar();
        }
    }

    private function renderLoginLayout(string $viewPath): void
    {
        $this->enterRenderGuard('renderLoginLayout');

        try {
            $header = $this->appPath('resources/views/header.php');
            $footer = $this->appPath('resources/views/footer.php');

            if (is_file($header)) {
                $ui = \View\Component::class;
                require $header;
            }

            $ui = \View\Component::class;
            require $viewPath;

            if (is_file($footer)) {
                $ui = \View\Component::class;
                require $footer;
            }

            if (class_exists('\\Foundation\\Profiler')) {
                echo \Foundation\Profiler::renderToolbar();
            }
        } finally {
            $this->leaveRenderGuard();
        }
    }

    private function assertFileExists(string $file, string $reason): void
    {
        if (!is_file($file)) {
            Log::sysErr("VIEW ERROR: {$reason}");

            throw new ZantechException(
                $reason,
                'A system view could not be loaded.',
                500,
                ['file' => $file]
            );
        }
    }

    private function enterRenderGuard(string $context): void
    {
        self::$renderDepth++;

        if (self::$renderDepth > self::MAX_RENDER_DEPTH) {
            throw new ZantechException(
                "View recursion detected in {$context}",
                'A rendering error occurred.',
                500,
                ['context' => $context]
            );
        }
    }

    private function leaveRenderGuard(): void
    {
        self::$renderDepth--;
        if (self::$renderDepth < 0) {
            self::$renderDepth = 0;
        }
    }

    private function isXhrRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function appPath(string $path): string
    {
        return rtrim($this->appRoot ?? ZT_APP_ROOT, "/\\") . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function resolveTemplatePath(string $module, string $view): string
    {
        // Try project standard (Uppercase)
        $path = "app/Modules/{$module}/Views/{$view}.php";
        $full = $this->appPath($path);
        Log::debug("CHECKING_PATH: {$full}");
        if (is_file($full)) return $full;

        // Try lowercase fallback
        $path = "app/modules/{$module}/views/{$view}.php";
        $full = $this->appPath($path);
        Log::debug("CHECKING_PATH_FALLBACK: {$full}");
        if (is_file($full)) return $full;

        // Default to project standard (will trigger assertFileExists if missing)
        $final = $this->appPath("app/Modules/{$module}/Views/{$view}.php");
        Log::debug("FALLING_BACK_TO_DEFAULT: {$final}");
        return $final;
    }
}
