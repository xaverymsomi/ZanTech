<?php

namespace Logging;

/**
 * Oryn Unified Logging System
 *
 * - JSON-based structured logs
 * - Auto-creates directories
 * - Compatible with framework database helpers and legacy modules
 * - Provides clean log grouping (sys/db/query/email/sms/custom/audit)
 * - Includes request_number tracing
 */
class Log
{
    /** @var string|null Unique request ID */
    public static $request_number = null;

    /* ============================================================
     *  CORE
     * ============================================================ */

    private static function baseDir(): string
    {
        if (defined('ZT_LOG_ROOT')) {
            return ZT_LOG_ROOT;
        }

        return ZT_APP_ROOT;
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    private static function write(string $file, string $message): void
    {
        self::ensureDir(dirname($file));
        file_put_contents($file, $message . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function format(string $tag, $msg): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $req       = self::$request_number ?? 'NO-REQ-ID';

        if (is_array($msg) || is_object($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }

        return "[{$timestamp}] | {$req} | {$tag} | {$msg}";
    }

    /* ============================================================
     *  SYSTEM LOGS
     * ============================================================ */

    public static function sysLog($msg): int
    {
        if (!defined('APP_DEBUG') || APP_DEBUG !== true) {
            return 1;
        }
        $dir  = self::baseDir() . '/logs/sys/';
        $file = $dir . date('Y-m') . '-sys.log';

        self::write($file, self::format('SYS', $msg));
        return 1;
    }

    public static function sysErr($msg): int
    {
        $dir  = self::baseDir() . '/logs/sys/';
        $file = $dir . date('Y-m') . '-sys.log';

        self::write($file, self::format('SYS-ERROR', $msg));
        return 1;
    }

    public static function entry(string $tag, $msg): int
    {
        $dir  = self::baseDir() . '/logs/sys/';
        $file = $dir . date('Y-m') . '-sys.log';

        self::write($file, self::format($tag, $msg));
        return 1;
    }

    public static function customSysLog($tag, $msg): int
    {
        return self::entry($tag, $msg);
    }

    public static function info($msg): int
    {
        if (!defined('APP_DEBUG') || APP_DEBUG !== true) {
            return 1;
        }
        return self::entry('INFO', $msg);
    }

    public static function warning($msg): int
    {
        return self::entry('WARNING', $msg);
    }

    public static function debug($msg): int
    {
        if (!defined('APP_DEBUG') || APP_DEBUG !== true) {
            return 1;
        }
        return self::entry('DEBUG', $msg);
    }

    public static function error($msg): int
    {
        return self::sysErr($msg);
    }

    public static function exception(\Throwable $exception, string $tag = 'EXCEPTION', array $context = []): int
    {
        $payload = array_merge(
            [
                'type'        => get_class($exception),
                'message'     => $exception->getMessage(),
                'file'        => $exception->getFile(),
                'line'        => $exception->getLine(),
                'trace'       => $exception->getTraceAsString(),
                'request_id'  => self::$request_number,
            ],
            $context
        );

        if ($previous = $exception->getPrevious()) {
            $payload['previous'] = [
                'type'    => get_class($previous),
                'message' => $previous->getMessage(),
                'file'    => $previous->getFile(),
                'line'    => $previous->getLine(),
                'trace'   => $previous->getTraceAsString(),
            ];
        }

        return self::entry($tag, $payload);
    }

    /* ============================================================
 *  LEGACY COMPAT: savePlainLog
 *  Used by Oryn/bootstrap to dump a raw line (e.g. ******)
 * ============================================================ */
    public static function savePlainLog($msg): int
    {
        if (!defined('APP_DEBUG') || APP_DEBUG !== true) {
            return 1;
        }
        $dir  = self::baseDir() . '/logs/sys/';
        $file = $dir . date('Y-m') . '-sys.log';
        self::write($file, (string)$msg);

        return 1;
    }


    /* ============================================================
     *  DB LOGS
     * ============================================================ */

    public static function dbErr($msg): int
    {
        $dir  = self::baseDir() . '/logs/db/';
        $file = $dir . date('Y-m') . '-db-error.log';

        self::write($file, self::format('DB-ERROR', $msg));
        return 1;
    }

    public static function queryLog($type, $query, $params = []): int
    {
        if (!defined('APP_DEBUG') || APP_DEBUG !== true) {
            return 1;
        }
        $dir  = self::baseDir() . '/logs/query/';
        $file = $dir . date('Y-m') . '-query.log';

        self::write($file, self::format("QUERY-{$type}", [
            'query'  => $query,
            'params' => $params
        ]));

        return 1;
    }

    /* ============================================================
     *  DATA LOGS
     * ============================================================ */

    public static function dataLog($msg): int
    {
        $dir  = self::baseDir() . '/logs/data/';
        $file = $dir . date('Y-m') . '-data.log';
        self::write($file, self::format('DATA', $msg));
        return 1;
    }

    /* ============================================================
     *  EMAIL LOGS
     * ============================================================ */

    public static function emailErr($msg): int
    {
        $dir  = self::baseDir() . '/logs/email/';
        $file = $dir . date('Y-m') . '-email-error.log';

        self::write($file, self::format('EMAIL-ERROR', $msg));
        return 1;
    }

    public static function logEmail($msg): int
    {
        $dir  = self::baseDir() . '/logs/email/';
        $file = $dir . date('Y-m') . '-email.log';

        self::write($file, self::format('EMAIL', $msg));
        return 1;
    }

    /* ============================================================
     *  SMS LOGS
     * ============================================================ */

    public static function smsErr($msg): int
    {
        $dir  = self::baseDir() . '/logs/sms/';
        $file = $dir . date('Y-m') . '-sms-error.log';

        self::write($file, self::format('SMS-ERROR', $msg));
        return 1;
    }

    public static function logMsg($msg): int
    {
        $dir  = self::baseDir() . '/logs/sms/';
        $file = $dir . date('Y-m') . '-sms.log';

        self::write($file, self::format('SMS', $msg));
        return 1;
    }

    /* ============================================================
     *  AUDIT TRAIL
     * ============================================================ */

    public static function auditor(string $tag, $data): int
    {
        $dir  = self::baseDir() . '/logs/audit/';
        $file = $dir . date('Y-m') . '-audit.log';

        self::write($file, self::format("AUDIT-{$tag}", $data));
        return 1;
    }

    /* ============================================================
     *  CUSTOM LOGS
     * ============================================================ */

    public static function custom_log($dirName, $fileName, $msg): int
    {
        $dir  = self::baseDir() . "/logs/custom/{$dirName}/";
        $file = $dir . date('Y-m') . "-{$fileName}.log";

        self::write($file, self::format("CUSTOM-{$dirName}", $msg));
        return 1;
    }
}
