<?php



// Load composer (required)
require __DIR__ . '/../vendor/autoload.php';

// Define minimal constants required by your code under test
if (!defined('ZT_APP_ROOT')) {
    define('ZT_APP_ROOT', realpath(__DIR__ . '/..'));
}
if (!defined('ZT_BASE_PATH')) {
    define('ZT_BASE_PATH', realpath(__DIR__ . '/..'));
}
if (!defined('ZT_LOG_ROOT')) {
    define('ZT_LOG_ROOT', sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zantech-test-logs');
}
$sessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'zantech-test-sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0777, true);
}
session_save_path($sessionPath);
if (!defined('ZT_MAX_SEGMENTS')) {
    define('ZT_MAX_SEGMENTS', 20);
}
if (!defined('ZT_MAX_SEGMENT_LENGTH')) {
    define('ZT_MAX_SEGMENT_LENGTH', 80);
}
if (!defined('ZT_MAX_PARAM_LENGTH')) {
    define('ZT_MAX_PARAM_LENGTH', 200);
}
if (!defined('ZT_ROUTE_LOGIN')) {
    define('ZT_ROUTE_LOGIN', 'login');
}
if (!defined('ZT_ROUTE_DASHBOARD')) {
    define('ZT_ROUTE_DASHBOARD', 'dashboard');
}
if (!defined('ZT_ROUTE_LOGOUT')) {
    define('ZT_ROUTE_LOGOUT', 'logout');
}
if (!defined('ZT_BLOCKED_ROUTES')) {
    define('ZT_BLOCKED_ROUTES', [
        'bootstrap',
        'foundation',
        'vendor',
        'configuration',
        'helpers',
        'constants',
    ]);
}
if (!defined('ZT_SESS_AUTH_USER')) {
    define('ZT_SESS_AUTH_USER', 'ZT_AUTH_USER');
}
if (!defined('ZT_SESS_AUTH_FLAG')) {
    define('ZT_SESS_AUTH_FLAG', 'rp_signed_in');
}
if (!defined('ZT_RATE_LIMIT_MAX')) {
    define('ZT_RATE_LIMIT_MAX', 120);
}
