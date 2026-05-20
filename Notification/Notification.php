<?php

namespace Notification;

use Authentication\Session;
use View\ViewRenderer as View;

class Notification
{
    /**
     * Status code → UI definition
     */
    private const MAP = [
        200  => ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'msg' => 'data saved successfully.'],
        201  => ['type' => 'success', 'icon' => 'bi-check-circle-fill', 'msg' => 'data updated successfully.'],

        100  => ['type' => 'danger',  'icon' => 'bi-x-circle-fill',     'msg' => 'data could not be saved.'],
        101  => ['type' => 'danger',  'icon' => 'bi-x-circle-fill',     'msg' => 'data could not be updated.'],

        3000 => ['type' => 'danger',  'icon' => 'bi-shield-exclamation','msg' => 'data validation failed.'],
        10   => ['type' => 'danger',  'icon' => 'bi-lock-fill',         'msg' => 'email or password is incorrect.'],
        1993 => ['type' => 'danger',  'icon' => 'bi-robot',             'msg' => 'please verify you are not a robot.'],

        5000 => ['type' => 'success', 'icon' => 'bi-envelope-check',    'msg' => 'password recovery email sent successfully.'],
        6000 => ['type' => 'success', 'icon' => 'bi-key-fill',          'msg' => 'password changed successfully.'],

        6060 => ['type' => 'danger',  'icon' => 'bi-person-x-fill',     'msg' => 'user account is not active.'],
        6061 => ['type' => 'danger',  'icon' => 'bi-person-x-fill',     'msg' => 'user account does not exist.'],
        6062 => ['type' => 'danger',  'icon' => 'bi-person-lock',       'msg' => 'user account is active in another browser.'],
        6063 => ['type' => 'danger',  'icon' => 'bi-shield-lock-fill',  'msg' => 'passwords do not match.'],
    ];

    /**
     * Show notification based on session "returned" value
     */
    public static function show(string $class): void
    {
        $code = Session::get('returned');

        if ($code === null || $code === '') {
            return;
        }

        // Normalize title
        $title = '<b class="notification-title">'
            . View::e(str_replace('_Model', '', $class))
            . '</b>';

        $conf = self::MAP[$code] ?? [
            'type' => 'danger',
            'icon' => 'bi-exclamation-triangle-fill',
            'msg'  => is_string($code) ? $code : 'An unexpected error occurred.'
        ];

        $message = '<p class="notification-msg">'
            . View::e($title . ' ' . $conf['msg'])
            . '</p>';

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var interval = setInterval(function() {
                    if (window.angular && angular.element(document.body).injector()) {
                        clearInterval(interval);
                        var toaster = angular.element(document.body).injector().get('toaster');
                        toaster.pop('{$conf['type']}', '" . addslashes(str_replace('_Model', '', $class)) . "', '" . addslashes($conf['msg']) . "');
                    }
                }, 100);
                setTimeout(function() { clearInterval(interval); }, 5000);
            });
        </script>";

        Session::set('returned', null);
    }

    /**
     * Progress notification (used during long actions)
     */
    public static function progress(string $action): void
    {
        $safe = addslashes(View::e($action));

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                var interval = setInterval(function() {
                    if (window.angular && angular.element(document.body).injector()) {
                        clearInterval(interval);
                        var toaster = angular.element(document.body).injector().get('toaster');
                        toaster.pop('info', 'Progress', '{$safe}');
                    }
                }, 100);
                setTimeout(function() { clearInterval(interval); }, 5000);
            });
        </script>";
    }
}
