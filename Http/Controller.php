<?php

namespace Http;

use Authentication\Perm_Auth;
use Logging\Log;
use Modules\Error\Error;
use View\ViewRenderer;

/**
 * Zantech Base Controller
 * - Prevents recursive instantiation
 * - Lazy-loads View
 */
class Controller
{
    protected ?ViewRenderer $view = null;
    protected $model = null;

    /**
     * Prevent runaway recursion (hard safety guard)
     */
    private static int $instanceCounter = 0;

    public function __construct()
    {
        self::$instanceCounter++;

        if (self::$instanceCounter > 50) {
            Log::sysLog('FATAL: Controller recursion detected');
            http_response_code(500);
            echo 'Internal framework error.';
            exit;
        }
    }

    public function __destruct()
    {
        self::$instanceCounter--;
    }

    /* ======================================
     * VIEW ACCESS (LAZY)
     * ====================================== */

    protected function view(): ViewRenderer
    {
        if ($this->view === null) {
            $this->view = new ViewRenderer();
        }

        return $this->view;
    }

    /* ======================================
     * RENDER HELPERS
     * ====================================== */

    protected function render(string $viewName): void
    {
        $module = $this->resolveModuleName();
        $this->view()->render($module, $viewName);
    }

    protected function renderFull(string $path): void
    {
        $this->view()->renderFull($path);
    }

    protected function renderJson(string $viewName): void
    {
        $module = $this->resolveModuleName();
        $this->view()->renderJson($module, $viewName);
    }

    protected function responseView(string $viewName, int $status = 200): Response
    {
        $module = $this->resolveModuleName();

        ob_start();
        $this->view()->render($module, $viewName);
        return Response::html((string)ob_get_clean(), $status);
    }

    protected function responseFullView(string $path, int $status = 200): Response
    {
        ob_start();
        $this->view()->renderFull($path);
        return Response::html((string)ob_get_clean(), $status);
    }

    protected function responseJson(array $payload, int $status = 200): Response
    {
        return Response::json($payload, $status);
    }

    protected function responseSuccess(int $status, string $msg, array $extra = []): Response
    {
        return $this->responseJson(array_merge([
            'status'  => $status,
            'ok'      => true,
            'title'   => $msg,
            'code'    => $status,
            'message' => $msg,
        ], $extra), $status);
    }

    protected function responseError(string $msg, int $status = 500, array $extra = []): Response
    {
        return $this->responseJson(array_merge([
            'status'  => $status,
            'ok'      => false,
            'title'   => $msg,
            'code'    => $status,
            'message' => $msg,
        ], $extra), $status);
    }

    protected function responseRedirect(string $to, int $status = 302): Response
    {
        return Response::redirect($this->normalizeRedirectTarget($to), $status);
    }

    /* ======================================
     * PERMISSIONS
     * ====================================== */

    protected function requirePermission(string $permissionKey): void
    {
        $permissions = Perm_Auth::getPermissions();

        if (!$permissions->verifyPermission($permissionKey)) {
            $this->permissionDenied();
        }
    }

    /** @deprecated Use permissionDenied() instead — kept for backward compatibility only */
    public function _permissionDenied($unauthorized_task = null): void
    {
        if ($unauthorized_task !== null && $unauthorized_task !== '') {
            Log::sysLog('No permission to access: ' . $unauthorized_task);
        }
        $this->permissionDenied();
    }

    protected function permissionDenied(): void
    {
        Log::sysLog(
            "PERMISSION DENIED: " .
            (debug_backtrace()[1]['class'] ?? '') . "::" .
            (debug_backtrace()[1]['function'] ?? '')
        );

        (new Error(
            "Error 007",
            "Permission Denied",
            "You are not authorised to perform this action",
            "bi-lock-fill"
        ))->index();

        exit;
    }

    /* ======================================
     * PROFILE PAGE
     * ====================================== */

    protected function loadProfile(string $rowValue, string $permissionKey, array $extra = []): void
    {
        $this->requirePermission($permissionKey);

        if (!$this->model) {
            Log::sysLog("PROFILE ERROR: Model not set");
            $this->renderFull('views/templates/not_found');
            return;
        }

        $recordId = $this->model->getRecordIdByRowValue(
            $this->model->getTable(),
            $rowValue
        );

        if ($recordId < 0) {
            $this->view()->subtitle = "Record not found";
            $this->renderFull('views/templates/not_found');
            return;
        }

        $profile = $this->model->getProfileData(
            $recordId,
            $this->model->getTable()
        );

        $this->view()->title = $this->model->getTitle() . " Profile";
        $this->view()->data = array_merge($profile, $extra);
        $this->view()->tabs = $this->model->getTabs();
        $this->view()->hidden_columns = $this->model->getProfileHiddenColumns();
        $this->view()->buttons = $this->model->getProfileButtons();

        $this->render('profile/profile');
    }

    /* ======================================
     * LEGACY MIGRATION HELPERS
     * ====================================== */

    public function pageFilter(string $title, array $data, string $permission, bool $view = true): void
    {
        $this->requirePermission($permission);

        $this->view()->title = $title;
        
        if ($this->model) {
            $this->view()->buttons = method_exists($this->model, 'getControls') ? $this->model->getControls() : [];
            $this->view()->class = getClassName(get_class($this->model));
            $this->view()->table = $this->model->getTable($view);
            $this->view()->allRecords = $data[0] ?? [];
            
            $schema = method_exists($this->model, 'getClassFields') ? $this->model->getClassFields($this->model->getTable($view)) : [];
            $this->view()->headings = $schema['properties'] ?? [];
            
            $this->view()->hidden = method_exists($this->model, 'getHiddenFields') ? $this->model->getHiddenFields() : [];
            $this->view()->actions = method_exists($this->model, 'getActions') ? $this->model->getActions() : [];
            $this->view()->resultData = $data[1] ?? [];
            $this->view()->postData = $data[2] ?? [];
            $this->view()->labels = method_exists($this->model, 'getTableLabels') ? ($this->model->getTableLabels() ?? []) : [];
        }

        $this->render('index');
    }

    public function getProfile(string $record_id, string $permission, array $extra_data = []): void
    {
        $this->requirePermission($permission);

        if (!$this->model) {
            $this->renderFull('views/templates/not_found');
            return;
        }

        $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);
        if ($returned_id > -1) {
            $data = $this->model->getProfileData($returned_id, $this->model->getTable());

            $this->view()->title = $this->model->getTitle() . ' Profile';
            $this->view()->data = array_merge($data, $extra_data);
            $this->view()->tabs = method_exists($this->model, 'getTabs') ? $this->model->getTabs() : [];
            $this->view()->hidden_columns = method_exists($this->model, 'getProfileHiddenColumns') ? $this->model->getProfileHiddenColumns() : [];
            $this->view()->buttons = method_exists($this->model, 'getProfileButtons') ? $this->model->getProfileButtons() : [];
            
            $this->render('profile/profile');
        } else {
            $this->view()->subtitle = "Record not found";
            $this->renderFull('views/templates/not_found');
        }
    }

    public function getAssociatedRecords(string $record_id, string $valid_caller, array $call_mappers, string $permission): void
    {
        $this->requirePermission($permission);

        if (!$this->model) {
             $this->renderFull('views/templates/not_found');
             return;
        }

        $returned_id = $this->model->getRecordIdByRowValue($this->model->getTable(), $record_id);
        if ($returned_id > -1) {
            $normalized = strtolower(trim($valid_caller));
            $singular = (str_ends_with($normalized, 's') && !str_ends_with($normalized, 'ss')) ? substr($normalized, 0, -1) : $normalized;
            $table = 'mx_' . $singular;

            foreach ($call_mappers as $k => $v) {
                $kNorm = strtolower(trim((string)$k));
                $kSing = (str_ends_with($kNorm, 's') && !str_ends_with($kNorm, 'ss')) ? substr($kNorm, 0, -1) : $kNorm;
                if ($kSing === $singular) {
                    $table = $v;
                    break;
                }
            }

            $associated_details = method_exists($this->model, 'getAssociatedRecordDetails') ? $this->model->getAssociatedRecordDetails($valid_caller) : [];
            $this->view()->hiddens = $associated_details['hiddens'] ?? [];
            $this->view()->labels = $associated_details['labels'] ?? [];
            $this->view()->formatters = $associated_details['formatters'] ?? [];
            $this->view()->data = $this->model->getAssociatedRecords($returned_id, $table, $this->model->getParentKey());
            $this->view()->table_headers = $this->model->getTableColumns($table . '_view');
            $this->view()->caller = str_replace("_", " ", filter_var($valid_caller, FILTER_SANITIZE_SPECIAL_CHARS));
            $this->view()->actions = method_exists($this->model, 'getAssociatedRecordActions') ? $this->model->getAssociatedRecordActions($valid_caller) : [];
            $this->view()->show_cards = false;
            
            $this->render('associated_records/main');
        } else {
            $this->view()->subtitle = "Record not found";
            $this->renderFull('views/templates/not_found');
        }
    }

    /* ======================================
     * HELPERS
     * ====================================== */

    private function resolveModuleName(): string
    {
        $class = get_class($this);
        $parts = explode("\\", $class);

        return $parts[1] ?? 'Unknown';
    }

    private function normalizeRedirectTarget(string $to): string
    {
        $to = trim(str_replace(["\r", "\n"], '', $to));

        if ($to === '') {
            return '/';
        }

        if (preg_match('#^(https?:)?//#i', $to)) {
            return '/';
        }

        return '/' . ltrim($to, '/');
    }
}
