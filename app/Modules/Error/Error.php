<?php

namespace Modules\Error;

use Http\Controller;

/**
 * ============================================================================
 *  ORYN - Error Controller
 * ============================================================================
 *  ✔ Displays framework/system/module errors
 *  ✔ Uses Bootstrap Icons (bi-*)
 *  ✔ Safe with lazy-loaded View
 * ============================================================================
 */
class Error extends Controller
{
    /** @var string */
    public string $module = 'Error';

    private string $title;
    private string $msg;
    private ?string $sub;
    private string $icon;

    /**
     * @param string      $title  Error title
     * @param string      $msg    Main error message
     * @param string|null $sub    Optional sub-message
     * @param string      $icon   Bootstrap Icon (bi-*) OR legacy pe-7s-*
     */
    public function __construct(
        string $title,
        string $msg,
        ?string $sub = null,
        string $icon = 'bi-exclamation-triangle-fill'
    ) {
        parent::__construct();

        $this->title = $title;
        $this->msg   = $msg;
        $this->sub   = $sub;
        $this->icon  = $icon;
    }

    /**
     * Render the error page using the module's index.php view
     */
    public function index(): void
    {
        $view = $this->view(); // lazy-safe

        $view->title     = $this->title;
        $view->msg       = $this->msg;
        $view->sub       = $this->sub;
        $view->icon      = $this->icon;

        // keep legacy expectations intact
        $view->data      = [];
        $view->dropdowns = [];
        $view->hidden    = [];

        $this->render('index');
    }
}
