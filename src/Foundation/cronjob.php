<?php

use Foundation\Console\CronRunner;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$basePath = dirname(__DIR__, 2);

if (!class_exists(CronRunner::class)) {
    require_once $basePath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
}

if (!function_exists('zt_env')) {
    require_once $basePath . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'config.php';
}

exit((new CronRunner())->run($basePath));
