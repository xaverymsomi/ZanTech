<?php

use Database\Database;
use Library\Model;
use Loggers\Log;

/**
 * ============================================================
 *  DIRECTORY UTILITIES
 * ============================================================
 */
function mkdirIfNotExists(string $dir): void
{
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            Log::sysErr("FAILED TO CREATE DIRECTORY: {$dir}");
        }
    }
}

/**
 * ============================================================
 *  SESSION HELPERS
 * ============================================================
 */
function user_log(): string
{
    return getSession();
}

function getSession(): string
{
    if (!\Authentication\Auth::isLogged()) {
        return 'GSTUSR';
    }

    $user = \Authentication\Auth::user();
    return $user['txt_username'] ?? 'GSTUSR';
}

/**
 * ============================================================
 *  ARRAY HELPERS
 * ============================================================
 */
function pluck(string $key, string $value, array $array): array
{
    $result = [];

    foreach ($array as $row) {
        if (isset($row[$key], $row[$value])) {
            $result[$row[$key]] = $row[$value];
        }
    }

    return $result;
}

function reduceArray(array $mapping, array $rows): array
{
    $result = [];

    foreach ($rows as $row) {
        $item = [];
        foreach ($mapping as $alias => $col) {
            $item[$alias] = $row[$col] ?? null;
        }
        $result[] = $item;
    }

    return $result;
}

function searcher(string $column, mixed $value, array $rows): array
{
    foreach ($rows as $row) {
        if (isset($row[$column]) && $row[$column] == $value) {
            return $row;
        }
    }

    return [];
}

/**
 * ============================================================
 *  SAFE SELECTOR
 * ============================================================
 */
function selector(Database $db, string $sql, array $params = []): array
{
    try {
        $res = $db->select($sql, $params);
        return is_array($res) ? $res : [];
    } catch (Throwable $e) {
        Log::sysErr("selector: {$e->getMessage()}");
        return [];
    }
}

/**
 * ============================================================
 *  LAST INSERT ID (SAFE)
 * ============================================================
 */
function lastInsertId(Database $db, array $data = [], ?string $table = null): int
{
    if ($data === []) {
        return (int) $db->lastInsertId();
    }

    if (!$table || !preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        Log::sysErr("Invalid table name in lastInsertId: {$table}");
        return 0;
    }

    $where = [];
    $params = [];

    foreach ($data as $column => $value) {
        if ($value === null || $value === '') {
            $where[] = " {$column} IS NULL ";
        } else {
            $where[] = " {$column} = :{$column} ";
            $params[$column] = $value;
        }
    }

    $sql = "SELECT id FROM {$table} WHERE " . implode(' AND ', $where);
    $result = $db->select($sql, $params);

    return isset($result[0]['id']) ? (int) $result[0]['id'] : 0;
}

/**
 * ============================================================
 *  JSON RESPONSE HELPERS
 * ============================================================
 */
function dd(mixed $msg): void
{
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function response(mixed $msg, bool $background = false): void
{
    $json = json_encode($msg, JSON_UNESCAPED_UNICODE);

    Log::sysLog("RESPONSE: {$json}");

    header('Content-Type: application/json');

    echo $json;

    if (!$background) {
        exit;
    }

    // background mode
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    if (session_id() !== '') {
        session_write_close();
    }
}

/**
 * ============================================================
 *  CLASS NAME (SHORT)
 * ============================================================
 */
function getClassName(string $class): string
{
    return basename(str_replace('\\', '/', $class));
}

/**
 * ============================================================
 *  LABEL LOADER (SAFE)
 * ============================================================
 */
function getLabels(string $table, string $key, string $value): array
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        Log::sysErr("INVALID TABLE IN getLabels: {$table}");
        return [];
    }

    $db = new Database();
    $rows = $db->select("SELECT * FROM {$table}");

    $result = [];
    foreach ($rows as $row) {
        if (isset($row[$key], $row[$value])) {
            $result[$row[$key]] = $row[$value];
        }
    }

    return $result;
}

/**
 * ============================================================
 *  LEGACY RAW LOG
 * ============================================================
 */
function logData(string $tag, mixed $data): void
{
    $base = defined('ZT_APP_ROOT') ? ZT_APP_ROOT : __DIR__;

    $file = "{$base}/" . date('Y-m') . "_syslog.txt";
    $line = date('d-m-Y H:i:s') . " - {$tag}: " . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;

    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * ============================================================
 *  TRANSLATION
 * ============================================================
 */
function trans(string $key): string
{
    $lang = \Authentication\Session::get('lang') ?? 'en';

    if (!defined('ZT_APP_ROOT')) return $key;

    $file = ZT_APP_ROOT . "/locale/lang.{$lang}.php";

    if (file_exists($file)) {
        $translations = require $file;

        if (isset($translations[$key])) {
            return $translations[$key];
        }
    }

    return $key;
}

/**
 * ============================================================
 *  CURL HTTP CLIENT
 * ============================================================
 */
function sendRequest(
    string $url,
    string $method = 'GET',
    array $data = [],
    array $headers = []
): string|false {

    $curl = curl_init();

    $opts = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => $headers,
    ];

    if ($method !== 'GET') {
        $opts[CURLOPT_POSTFIELDS] = http_build_query($data);
    }

    if (str_starts_with(strtolower($url), 'https://')) {
        $opts[CURLOPT_SSL_VERIFYPEER] = true;
        $opts[CURLOPT_SSL_VERIFYHOST] = 2;
    }

    curl_setopt_array($curl, $opts);

    $res = curl_exec($curl);

    if ($res === false) {
        Log::sysErr("CURL ERROR: " . curl_error($curl));
    }

    curl_close($curl);

    return $res;
}

/**
 * ============================================================
 *  FORCE LOGOUT
 * ============================================================
 */
function kill(): void
{
    \Authentication\Session::destroy();

    $url = defined('URL') ? URL : '';
    $loginRoute = defined('ZT_ROUTE_LOGIN') ? ZT_ROUTE_LOGIN : 'login';
    
    header('Location: ' . $url . '/' . $loginRoute, true, 302);
    exit;
}
