<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd.
 *
 * (c) 2023
 *
 */

namespace Modules\Autorun;

use Modules\Dashboard\Dashboard;
use Modules\Login\Login;

class Autorun
{
    public function index(): void
    {
        if (!array_key_exists('id', $_SESSION)) {
            $controller = new Login();
        } else {
            $controller = new Dashboard();
        }
        $controller->index();
    }

}
