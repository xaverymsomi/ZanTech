<?php

namespace Foundation;

use Http\Controller;
use Authentication\Auth;
use Authentication\Gate;
use Logging\Log;
use ReflectionClass;

/**
 * BaseModuleController: The "Automatic" Module Engine
 * 
 * Provides standard CRUD and Listing behavior for modules without 
 * requiring redundant method definitions.
 */
abstract class BaseModuleController extends Controller
{
    /**
     * Define which methods require Dual Control (Maker-Checker).
     * Example: ['save', 'delete', 'updateStatus']
     */
    protected array $dualControl = [];

    /**
     * Default permission prefix for this module.
     * If null, it will be derived from the module name (e.g. 'view_users').
     */
    protected ?string $permissionPrefix = null;

    public function __construct()
    {
        parent::__construct();
        $this->initializeModel();
    }

    /**
     * Automatically discover and instantiate the module's model.
     */
    private function initializeModel(): void
    {
        if ($this->model !== null) return;

        $class = new ReflectionClass($this);
        $namespace = $class->getNamespaceName();
        $shortName = $class->getShortName();
        
        $modelClass = "{$namespace}\\{$shortName}_Model";

        if (class_exists($modelClass)) {
            $this->model = new $modelClass();
        }
    }

    /**
     * Generic Index: Lists all records.
     */
    public function index(): void
    {
        $this->listRecords("All " . $this->model->getTitle());
    }

    /**
     * Generic Active: Lists only active records.
     */
    public function active(): void
    {
        $this->listRecords("Active " . $this->model->getTitle(), [
            'columns' => ['opt_mx_status_id'],
            'values'  => [ACTIVE]
        ]);
    }

    /**
     * Generic Inactive: Lists only inactive records.
     */
    public function inactive(): void
    {
        $this->listRecords("Inactive " . $this->model->getTitle(), [
            'columns' => ['opt_mx_status_id'],
            'values'  => [INACTIVE]
        ]);
    }

    /**
     * Common listing logic used by index, active, inactive.
     */
    protected function listRecords(string $title, array $filters = []): void
    {
        $permission = $this->permissionPrefix ?? "view_" . strtolower($this->model->getTitle() . 's');
        
        if (empty($filters)) {
            $data = $this->model->getAllRecords($this->model->getTable(true));
        } else {
            $data = $this->model->getFilteredRecords(
                $this->model->getTable(true), 
                $filters['columns'], 
                $filters['values']
            );
        }

        $this->pageFilter($title, $data, $permission);
    }

    /**
     * Generic Create: Renders the create form view with dropdown data.
     * Renders full layout so FormLoader.load() can find #page-content as a descendant.
     * Modules may override this to inject custom logic.
     */
    public function create(): void
    {
        $this->view()->data = [];
        $this->view()->dropdowns = method_exists($this->model, 'getFormDropdowns')
            ? ($this->model->getFormDropdowns() ?? [])
            : [];
        $this->render('create');
    }

    /**
     * Generic Edit: Loads an existing record and renders the edit form.
     * Renders full layout so FormLoader.load() can find #page-content as a descendant.
     * Modules may override this to inject custom logic.
     */
    public function edit(string $id = ''): void
    {
        $safeId = filter_var($id, FILTER_SANITIZE_SPECIAL_CHARS);

        $record = [];
        if ($safeId !== '') {
            $record = $this->model->db->select(
                "SELECT * FROM " . $this->model->getTable() . " WHERE id = :id",
                [':id' => $safeId]
            )[0] ?? [];
        }

        $this->view()->data = $record;
        $this->view()->dropdowns = method_exists($this->model, 'getFormDropdowns')
            ? ($this->model->getFormDropdowns() ?? [])
            : [];

        $this->render('edit');
    }

    /**
     * Wrapper for Dual Control awareness.
     * Oryn kernel calls this if Dual Control is triggered.
     */
    public function isDualControlRequired(string $method): bool
    {
        return in_array($method, $this->dualControl, true);
    }
}
