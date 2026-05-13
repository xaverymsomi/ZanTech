<?php

namespace Modules\Settings;

use Library\Controller;

class Settings extends Controller
{
    public string $module = 'Settings';

    public function __construct()
    {
        parent::__construct();
        $this->model = new Settings_Model();
    }

    public function index(): void
    {
        $this->view()->title = 'System Settings';
        $this->view()->settings = $this->model->getAllSettings();
        $this->render('index');
    }

    public function update(): void
    {
        $this->requirePermission('edit_settings');
        $data = $this->validator()->validate([
            'txt_key' => 'required',
            'txt_value' => 'required'
        ]);

        $this->model->where('txt_key', $data['txt_key'])->update($data);
        $this->jsonSuccess('Setting updated successfully');
    }
}
