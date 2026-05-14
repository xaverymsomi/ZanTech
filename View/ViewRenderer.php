<?php

declare(strict_types=1);

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
            $viewPath = $this->appPath("modules/{$module}/views/{$view}.php");

            $this->assertFileExists($viewPath, "View not found: {$module}/{$view}.php");

            if ($noLayout || $this->isXhrRequest()) {
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
                require $file;
                return;
            }

            $header = $this->appPath('views/header.php');
            $body = $this->appPath('views/body.php');
            $footer = $this->appPath('views/footer.php');

            $this->assertFileExists($header, 'Missing header.php');
            $this->assertFileExists($body, 'Missing body.php');
            $this->assertFileExists($footer, 'Missing footer.php');

            require $header;
            require $body;
            require $file;
            require $footer;
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
            $viewPath = $this->appPath("modules/{$module}/views/{$view}.php");

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
        $header = $this->appPath('views/header.php');
        $body = $this->appPath('views/body.php');
        $footer = $this->appPath('views/footer.php');

        $this->assertFileExists($header, 'Missing header.php');
        $this->assertFileExists($body, 'Missing body.php');
        $this->assertFileExists($footer, 'Missing footer.php');

        ob_start();
        require $viewPath;
        $this->content = ob_get_clean();

        $this->dynamicStyles = \Library\DataView::getStyles();

        require $header;
        require $body;
        require $footer;
    }

    private function renderLoginLayout(string $viewPath): void
    {
        $this->enterRenderGuard('renderLoginLayout');

        try {
            $header = $this->appPath('views/header.php');
            $footer = $this->appPath('views/footer.php');

            if (is_file($header)) {
                require $header;
            }

            require $viewPath;

            if (is_file($footer)) {
                require $footer;
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
}
