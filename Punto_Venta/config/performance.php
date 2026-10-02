<?php

return [
    'enabled' => env('PERFORMANCE_MONITORING_ENABLED', false),
    'slow_request_ms' => (float) env('PERFORMANCE_SLOW_REQUEST_MS', 1000),
    'slow_query_ms' => (float) env('PERFORMANCE_SLOW_QUERY_MS', 100),
];