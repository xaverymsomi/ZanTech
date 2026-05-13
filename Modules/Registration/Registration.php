<?php

namespace Modules\Registration;

use Library\Controller;

class Registration extends Controller
{
    public string $module = 'Registration';

    public function __construct()
    {
        parent::__construct();
        $this->model = new Registration_Model();
    }

    public function index(): void
    {
        $this->view()->title = 'Registration';
        $this->render('index');
    }
}
