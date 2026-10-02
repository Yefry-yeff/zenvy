<?php

namespace App\Providers;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class PerformanceMonitoringServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (!config('performance.enabled') || $this->app->runningInConsole()) {
            return;
        }

        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryTimeMs = 0.0;
        $slowQueries = [];

        DB::listen(function (QueryExecuted $query) use (&$queryCount, &$queryTimeMs, &$slowQueries): void {
            $queryCount++;
            $queryTimeMs += $query->time;

            if ($query->time >= config('performance.slow_query_ms') && count($slowQueries) < 10) {
                $slowQueries[] = [
                    'time_ms' => round($query->time, 2),
                    'sql' => mb_substr($query->sql, 0, 500),
                ];
            }
        });

        $this->app->terminating(function () use ($startedAt, &$queryCount, &$queryTimeMs, &$slowQueries): void {
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $request = request();

            Log::channel('performance')->info('request_metrics', [
                'method' => $request->method(),
                'path' => $request->path(),
                'route' => $request->route()?->getName(),
                'duration_ms' => round($durationMs, 2),
                'slow' => $durationMs >= config('performance.slow_request_ms'),
                'query_count' => $queryCount,
                'query_time_ms' => round($queryTimeMs, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'slow_queries' => $slowQueries,
            ]);
        });
    }
}