<?php
include '../inc/config.php';
include_once '../inc/sys_pref.php';
$classesDir = array(
    MX17_APP_ROOT . '/models/',
    MX17_APP_ROOT . '/libs/'
);

spl_autoload_register(function ($class_name) {
    global $classesDir;
    foreach ($classesDir as $directory) {

        if (file_exists($directory . $class_name . '.php')) {
            require_once ($directory . $class_name . '.php');
            return;
        }
    }
});
$init = new Init();

$usr = filter_input(INPUT_POST, 'token');
$pass = filter_input(INPUT_POST, 'password');
$pass2 = filter_input(INPUT_POST, 'password2');
//$passcode = filter_input(INPUT_POST, 'passcode');

//$init->reset($usr, $pass);

$init->reset($usr, $pass, $pass2);
