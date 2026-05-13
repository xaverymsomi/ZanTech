<?php

declare(strict_types=1);

// Load composer (required)
require __DIR__ . '/../vendor/autoload.php';

// Define minimal constants required by your code under test
if (!defined('ZT_APP_ROOT')) {
    define('ZT_APP_ROOT', realpath(__DIR__ . '/..'));
}
if (!defined('ZT_BASE_PATH')) {
    define('ZT_BASE_PATH', realpath(__DIR__ . '/..'));
}
