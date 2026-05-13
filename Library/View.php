<?php

declare(strict_types=1);

namespace Library;

use AllowDynamicProperties;
use Authentication\Auth;
use Authentication\Session;
use Exceptions\ZantechException;
use Loggers\Log;

#[AllowDynamicProperties]
class View
{
    /** ================================
     *  PUBLIC VIEW PROPERTIES
     * ================================ */
    public string  $title = '';
    public ?string $subtitle = null;
    public string $controller;
    public string $action;
    public array $disabled;
    public mixed   $permission_details = null;
    public string $content = '';

    public array $data = [];
    public array $dropdowns = [];
    public array $hidden = [];
    public array $hidden_columns = [];
    public array $actions = [];
    public array $labels = [];
    public array $tabs = [];
    public array $buttons = [];

    public string  $msg  = '';
    public ?string $sub  = null;
    public ?string $icon = null;

    /** ================================
     *  INTERNAL SAFETY GUARDS
     * ================================ */
    private static int $renderDepth = 0;
    private const MAX_RENDER_DEPTH = 10;

    /** ================================
     *  MAIN RENDERER
     * ================================ */
    public function render(string $module, string $view, bool $noLayout = false): void
    {
        $this->enterRenderGuard('render');

        try {
            // Ensure session is available (needed for login layout logic)
            Session::init();

            $module = trim($module, "/\\");
            $view   = trim($view, "/\\");
            $viewPath = ZT_APP_ROOT . "/modules/{$module}/views/{$view}.php";

            $this->assertFileExists($viewPath, "View not found: {$module}/{$view}.php");

            if ($noLayout || $this->isXhrRequest()) {
                require $viewPath;
                return;
            }

            // Use Auth if available, fallback to legacy flag
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

    /** ================================
     *  BACKOFFICE LAYOUT
     * ================================ */
    private function renderFullLayout(string $viewPath): void
    {
        $header = ZT_APP_ROOT . "/views/header.php";
        $body   = ZT_APP_ROOT . "/views/body.php";
        $footer = ZT_APP_ROOT . "/views/footer.php";

        $this->assertFileExists($header, 'Missing header.php');
        $this->assertFileExists($body, 'Missing body.php');
        $this->assertFileExists($footer, 'Missing footer.php');

        // ✅ capture module view output into $this->content
        ob_start();
        require $viewPath;
        $this->content = ob_get_clean();

        // ✅ Prepare dynamic styles
        $this->dynamicStyles = \Library\DataView::getStyles();

        require $header;
        require $body;
        require $footer;
    }

    public string $dynamicStyles = '';

    /** ================================
     *  LOGIN LAYOUT
     * ================================ */
    private function renderLoginLayout(string $viewPath): void
    {
        $this->enterRenderGuard('renderLoginLayout');

        try {
            $header = ZT_APP_ROOT . "/views/header.php";
            $footer = ZT_APP_ROOT . "/views/footer.php";

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

    /** ================================
     *  DIRECT FULL TEMPLATE
     * ================================ */
    public function renderFull(string $path): void
    {
        $this->enterRenderGuard('renderFull');

        try {
            $file = ZT_APP_ROOT . "/" . ltrim($path, "/\\");
            $this->assertFileExists($file, "Full view missing: {$path}");

            if ($this->isXhrRequest()) {
                require $file;
                return;
            }

            $header = ZT_APP_ROOT . "/views/header.php";
            $body   = ZT_APP_ROOT . "/views/body.php";
            $footer = ZT_APP_ROOT . "/views/footer.php";

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

    /** ================================
     *  JSON TEMPLATE
     * ================================ */
    public function renderJson(string $module, string $view): void
    {
        $this->enterRenderGuard('renderJson');

        try {
            $module = trim($module, "/\\");
            $view   = trim($view, "/\\");
            $viewPath = ZT_APP_ROOT . "/modules/{$module}/views/{$view}.php";

            if (!is_file($viewPath)) {
                http_response_code(500);
                echo json_encode([
                    'status' => false,
                    'error'  => 'JSON template missing'
                ]);
                return;
            }

            require $viewPath;
        } finally {
            $this->leaveRenderGuard();
        }
    }

    /** ================================
     *  FILE ASSERTION
     * ================================ */
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

    /** ================================
     *  RECURSION GUARD
     * ================================ */
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

    /** ================================
     *  XHR DETECTION
     * ================================ */
    private function isXhrRequest(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /** ================================
     *  ESCAPE HELPER
     * ================================ */
    public static function e(?string $val): string
    {
        return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
    }
}
