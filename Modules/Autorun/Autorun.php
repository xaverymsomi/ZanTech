<?php
/*
 * This file is part of the Mabrex package.
 * It is strictly a property of Rahisi Solution Ltd.
 *
 * (c) 2023
 *
 */

namespace Modules\Autorun;

use Library\\Log;

class Autorun
{
    public function index(): void
    {
//        $request_number = hrtime(true);
//
//        $dir = MX17_APP_ROOT . '/logs/_phpcrons/';
//        mkdirIfNotExists($dir);
//
//        $log = '[' . date('Y-m-d H:i:s') . '] | ' . $request_number . ' | [PHPCRON] | ' . '[' . json_encode(['title' => 'PHP Cronjob Test', 'location' => __METHOD__]) . ']' . "\n";
//        $log .= '******************************************************************************************************************************************' . "\n\n";
//
//        file_put_contents($dir . date('Y-m-d H') . '_cron.log', $log, FILE_APPEND);

        if (!array_key_exists('id', $_SESSION)) {
            $controller = new \Modules\Login\Login();
        } else {
            $controller = new \Modules\Dashboard\Dashboard();
        }
        $controller->index();
    }

}
