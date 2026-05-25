<?php

namespace Foundation;

final class Profiler
{
    private static array $queries = [];
    private static float $startTime = 0;

    public static function start(): void
    {
        self::$startTime = microtime(true);
    }

    public static function recordQuery(string $sql, array $params, float $durationMs): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            self::$queries[] = [
                'sql' => $sql,
                'params' => $params,
                'duration' => $durationMs
            ];
        }
    }

    public static function getQueries(): array
    {
        return self::$queries;
    }

    public static function getTotalTimeMs(): float
    {
        if (self::$startTime === 0) return 0;
        return (microtime(true) - self::$startTime) * 1000;
    }

    public static function renderToolbar(): string
    {
        if (!defined('APP_DEBUG') || APP_DEBUG !== true) {
            return '';
        }

        $count = count(self::$queries);
        $totalDbTime = array_sum(array_column(self::$queries, 'duration'));
        $appTime = self::getTotalTimeMs();
        
        $html = "
        <style>
            .zt-profiler-toolbar {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #1e293b;
                color: #f8fafc;
                font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
                font-size: 13px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 8px 16px;
                border-top: 1px solid #334155;
                z-index: 999999;
                box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .zt-profiler-metrics {
                display: flex;
                gap: 20px;
            }
            .zt-profiler-metric {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            .zt-profiler-label { color: #94a3b8; text-transform: uppercase; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; }
            .zt-profiler-value { font-weight: 600; }
            .zt-profiler-value.warning { color: #f59e0b; }
            .zt-profiler-value.danger { color: #ef4444; }
            .zt-profiler-btn {
                background: #334155;
                color: #fff;
                border: none;
                padding: 4px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 12px;
                transition: 0.2s;
            }
            .zt-profiler-btn:hover { background: #475569; }
            .zt-profiler-panel {
                display: none;
                position: fixed;
                bottom: 40px;
                left: 0;
                right: 0;
                height: 400px;
                background: #0f172a;
                color: #e2e8f0;
                z-index: 999998;
                border-top: 1px solid #334155;
                overflow-y: auto;
                padding: 20px;
                font-family: ui-monospace, SFMono-Regular, Consolas, monospace;
            }
            .zt-profiler-query {
                background: #1e293b;
                padding: 12px;
                border-radius: 6px;
                margin-bottom: 10px;
                border: 1px solid #334155;
            }
            .zt-profiler-query-time {
                display: inline-block;
                background: #334155;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 11px;
                margin-bottom: 8px;
                color: #93c5fd;
            }
            .zt-profiler-query-sql { color: #f8fafc; font-size: 13px; line-height: 1.5; white-space: pre-wrap; }
            .zt-profiler-query-params { margin-top: 8px; color: #94a3b8; font-size: 11px; }
        </style>

        <div id='ztProfilerPanel' class='zt-profiler-panel'>";
        
        foreach (self::$queries as $q) {
            $timeClass = $q['duration'] > 100 ? 'danger' : ($q['duration'] > 20 ? 'warning' : '');
            $paramsJson = !empty($q['params']) ? htmlspecialchars(json_encode($q['params'])) : '[]';
            $sqlHtml = htmlspecialchars($q['sql']);
            $dur = round($q['duration'], 2);
            $html .= "
            <div class='zt-profiler-query'>
                <span class='zt-profiler-query-time $timeClass'>{$dur}ms</span>
                <div class='zt-profiler-query-sql'>{$sqlHtml}</div>
                <div class='zt-profiler-query-params'>Bindings: {$paramsJson}</div>
            </div>";
        }

        $appClass = $appTime > 500 ? 'danger' : ($appTime > 200 ? 'warning' : '');
        $dbClass = $totalDbTime > 200 ? 'danger' : ($totalDbTime > 50 ? 'warning' : '');

        $html .= "</div>
        <div class='zt-profiler-toolbar'>
            <div class='zt-profiler-metrics'>
                <div class='zt-profiler-metric'>
                    <span class='zt-profiler-label'>Oryn v" . (defined('ZT_APP_VERSION') ? ZT_APP_VERSION : '2.x') . "</span>
                </div>
                <div class='zt-profiler-metric' style='margin-left: 20px;'>
                    <span class='zt-profiler-label'>Total App Time</span>
                    <span class='zt-profiler-value $appClass'>" . round($appTime, 1) . "ms</span>
                </div>
                <div class='zt-profiler-metric'>
                    <span class='zt-profiler-label'>Total DB Time</span>
                    <span class='zt-profiler-value $dbClass'>" . round($totalDbTime, 1) . "ms</span>
                </div>
                <div class='zt-profiler-metric'>
                    <span class='zt-profiler-label'>SQL Queries Executed</span>
                    <span class='zt-profiler-value'>{$count}</span>
                </div>
            </div>
            <button class='zt-profiler-btn' onclick=\"document.getElementById('ztProfilerPanel').style.display = document.getElementById('ztProfilerPanel').style.display === 'block' ? 'none' : 'block'\">
                Toggle Developer SQL Trace
            </button>
        </div>";

        return $html;
    }
}
