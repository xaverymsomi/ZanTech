<?php

declare(strict_types=1);

/**
 * ========================================================================
 *  Zantech v4 - System Preferences / Global Constants
 * ========================================================================
 *
 *  ✔ Central definition of ALL universal constants
 *  ✔ New ZT_ prefix for framework-wide consistency
 *  ✔ Backward-compatible with MX17 legacy constants
 *  ✔ Improves readability, grouping and organization
 *
 *  NOTE:
 *  Only system-wide/global constants should live here.
 *  Module-specific constants belong in their respective modules.
 *
 * ========================================================================
 */


/* ========================================================================
 |  1. APPLICATION META
 * ======================================================================== */

if (!defined('ZT_APP_NAME')) {
    define('ZT_APP_NAME', 'ZANTECH');
}

if (!defined('ZT_APP_VERSION')) {
    define('ZT_APP_VERSION', '4.0.0');
}

if (!defined('ZT_PUBLIC_PATH')) {
    define('ZT_PUBLIC_PATH', ZT_APP_ROOT . DS . 'public');
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}


/* ========================================================================
 |  2. ENVIRONMENT MODE HELPERS
 * ======================================================================== */

if (!defined('ZT_MODE_LIVE')) {
    define('ZT_MODE_LIVE', 'live');
}

if (!defined('ZT_MODE_SANDBOX')) {
    define('ZT_MODE_SANDBOX', 'sandbox');
}

if (!defined('ZT_SYSTEM_MODE')) {
    // This allows system-wide toggling like LIVE / SANDBOX
    define('ZT_SYSTEM_MODE', zt_env('SYSTEM_MODE', ZT_MODE_LIVE));
}

if (!defined('APP_ENV')) {
    define('APP_ENV', zt_env('APP_ENV', 'production')); // local|staging|production
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', zt_env('APP_DEBUG', '0') === '1');
}


/* ========================================================================
 |  3. API / WEB CONTEXT FLAGS
 * ======================================================================== */

if (!defined('ZT_IS_API')) {
    define('ZT_IS_API', isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/api'));
}

if (!defined('ZT_IS_WEB')) {
    define('ZT_IS_WEB', !ZT_IS_API);
}


/* ========================================================================
 |  3.0 ROUTE & NAVIGATION NAMES
 * ======================================================================== */

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


/* ========================================================================
 |  3.1 URL / DOMAIN / PORTAL SETTINGS
 * ======================================================================== */

if (!defined('URL')) {
    define('URL', rtrim(zt_env('URL1', ''), '/'));
}

if (!defined('PORTAL_URL')) {
    define('PORTAL_URL', rtrim(zt_env('PORTAL_URL', ''), '/'));
}

if (!defined('API_URL')) {
    define('API_URL', rtrim(zt_env('API_URL', ''), '/'));
}

if (!defined('ATTACHMENT_URL')) {
    define('ATTACHMENT_URL', rtrim(zt_env('ATTACHMENT_URL', ''), '/'));
}

if (!defined('APP_DIR')) {
    define('APP_DIR', zt_env('APP_DIR', '')); // optional subfolder path
}


/* ========================================================================
 |  4. CORE BOOLEAN CONSTANTS
 * ======================================================================== */

if (!defined('ZT_HAS_ACTION')) {
    define('ZT_HAS_ACTION', true);
}

if (!defined('ZT_NO_ACTION')) {
    define('ZT_NO_ACTION', false);
}

if (!defined('HAS_ACTION')) {
    define('HAS_ACTION', ZT_HAS_ACTION);
}

if (!defined('NO_ACTION')) {
    define('NO_ACTION', ZT_NO_ACTION);
}


/* ========================================================================
 |  4.1 SECURITY KEYS
 * ======================================================================== */

if (!defined('APP_KEY')) {
    define('APP_KEY', zt_env('APP_KEY', ''));
}

if (!defined('PASS_SALT')) {
    define('PASS_SALT', zt_env('PASS_SALT', ''));
}

if (!defined('HASH_ALGO')) {
    define('HASH_ALGO', zt_env('HASH_ALGO', 'sha256'));
}


/* ========================================================================
 |  5. GLOBAL STATES (Universal System IDs)
 * ======================================================================== */

if (!defined('ZT_STATE_ACTIVE')) {
    define('ZT_STATE_ACTIVE', 1);
}

if (!defined('ZT_STATE_INACTIVE')) {
    define('ZT_STATE_INACTIVE', 4);
}

if (!defined('ZT_STATE_PENDING')) {
    define('ZT_STATE_PENDING', 2);
}

if (!defined('ZT_STATE_DISABLED')) {
    define('ZT_STATE_DISABLED', 0);
}

if (!defined('ZT_STATE_DELETED')) {
    define('ZT_STATE_DELETED', 9);
}


/* ========================================================================
 |  6. LEGACY COMPATIBILITY (MX17 state constants)
 * ======================================================================== */

if (!defined('ACTIVE'))         define('ACTIVE', ZT_STATE_ACTIVE);
if (!defined('INACTIVE'))       define('INACTIVE', ZT_STATE_INACTIVE);


/* ========================================================================
 |  7. LABEL HELPERS (Legacy UI)
 * ======================================================================== */

if (!defined('ZT_LABEL_SMALL')) {
    define('ZT_LABEL_SMALL', 'getSmallLabel');
}

if (!defined('ZT_LABEL_BIG')) {
    define('ZT_LABEL_BIG', 'getBigLabel');
}


/* ========================================================================
 |  8. CURRENCIES
 * ======================================================================== */

if (!defined('ZT_CURRENCY_TZS')) {
    define('ZT_CURRENCY_TZS', 1);
}

if (!defined('ZT_CURRENCY_USD')) {
    define('ZT_CURRENCY_USD', 2);
}

if (!defined('ZT_CURRENCY_EUR')) {
    define('ZT_CURRENCY_EUR', 3);
}


/* ========================================================================
 |  8.0 SESSION & AUTH KEYS
 * ======================================================================== */

if (!defined('ZT_SESS_AUTH_USER')) {
    define('ZT_SESS_AUTH_USER', 'ZT_AUTH_USER');
}

if (!defined('ZT_SESS_AUTH_FLAG')) {
    define('ZT_SESS_AUTH_FLAG', 'rp_signed_in');
}

if (!defined('ZT_SESS_INIT_KEY')) {
    define('ZT_SESS_INIT_KEY', 'ZT_SESS_INIT');
}

if (!defined('ZT_SESS_REGEN_TIME')) {
    define('ZT_SESS_REGEN_TIME', 900); // 15 minutes
}

if (!defined('ZT_SESS_CAPTCHA_TS')) {
    define('ZT_SESS_CAPTCHA_TS', 'capture_activity_ts');
}

if (!defined('ZT_SESS_CAPTCHA_CODE')) {
    define('ZT_SESS_CAPTCHA_CODE', 'captcha_string');
}

if (!defined('ZT_CAPTCHA_TIMEOUT')) {
    define('ZT_CAPTCHA_TIMEOUT', 30); // minutes
}


/* ========================================================================
 |  8.1 DATABASE SETTINGS
 * ======================================================================== */

if (!defined('DB_TYPE')) {
    define('DB_TYPE', strtolower(zt_env('DB_TYPE', 'sqlsrv'))); // sqlsrv|mysql|odbc
}

if (!defined('DB_HOST')) {
    define('DB_HOST', zt_env('DB_HOST', 'localhost'));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', zt_env('DB_PORT', DB_TYPE === 'mysql' ? '3306' : '1433'));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', zt_env('DB_NAME', ''));
}

if (!defined('DB_USER')) {
    define('DB_USER', zt_env('DB_USER', ''));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', zt_env('DB_PASS', ''));
}


/* ========================================================================
 |  9. TRANSACTION FLAGS (NEW)
 * ======================================================================== */

if (!defined('ZT_TRANSACTIONS_ENABLED')) {
    define('ZT_TRANSACTIONS_ENABLED', true); // can disable all txns system-wide
}

if (!defined('ZT_TRANSACTION_LOGGING')) {
    define('ZT_TRANSACTION_LOGGING', true); // log all txn operations
}


/* ========================================================================
 |  10. SYSTEM QUEUE / JOB FLAGS
 * ======================================================================== */

if (!defined('ZT_QUEUE_ENABLED')) {
    define('ZT_QUEUE_ENABLED', true);
}


/* ========================================================================
 |  10.1 ROUTER & SECURITY LIMITS
 * ======================================================================== */

if (!defined('ZT_MAX_SEGMENTS')) {
    define('ZT_MAX_SEGMENTS', 20);
}

if (!defined('ZT_MAX_SEGMENT_LENGTH')) {
    define('ZT_MAX_SEGMENT_LENGTH', 80);
}

if (!defined('ZT_MAX_PARAM_LENGTH')) {
    define('ZT_MAX_PARAM_LENGTH', 200);
}

if (!defined('ZT_RATE_LIMIT_MAX')) {
    define('ZT_RATE_LIMIT_MAX', 120);
}

if (!defined('ZT_NOTIFICATIONS_ENABLED')) {
    define('ZT_NOTIFICATIONS_ENABLED', true);
}


/* ========================================================================
 |  11. EMAIL PROVIDERS (Mailgun)
 * ======================================================================== */

if (!defined('MAILGUN_API_KEY')) {
    define('MAILGUN_API_KEY', zt_env('MAILGUN_API_KEY', ''));
}

if (!defined('MAILGUN_API_HOSTNAME')) {
    define('MAILGUN_API_HOSTNAME', zt_env('MAILGUN_API_HOSTNAME', ''));
}

if (!defined('MAILGUN_DOMAIN')) {
    define('MAILGUN_DOMAIN', zt_env('MAILGUN_DOMAIN', ''));
}


/* ========================================================================
 |  END OF FILE
 * ======================================================================== */
