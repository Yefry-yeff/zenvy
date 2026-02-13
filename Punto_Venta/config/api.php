<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Versión actual del API
    |
    */
    'version' => 'v1',
    
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Configuración de límites de requests por minuto
    |
    */
    'rate_limit' => [
        'max_attempts' => env('API_RATE_LIMIT', 60),
        'decay_minutes' => 1,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Autenticación
    |--------------------------------------------------------------------------
    |
    | Configuración de autenticación del API
    |
    */
    'auth' => [
        'driver' => env('API_AUTH_DRIVER', 'jwt'), // 'jwt' or 'api_key'
        'jwt_secret' => env('API_JWT_SECRET', env('APP_KEY')),
        'token_ttl' => env('API_TOKEN_TTL', 3600), // segundos (1 hora)
        'refresh_ttl' => env('API_REFRESH_TTL', 86400), // 24 horas
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Paginación
    |--------------------------------------------------------------------------
    |
    | Configuración de paginación por defecto
    |
    */
    'pagination' => [
        'default_per_page' => 50,
        'max_per_page' => 200,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Inventario
    |--------------------------------------------------------------------------
    |
    | Configuración específica del módulo de inventario
    |
    */
    'inventory' => [
        'stock_lock_timeout' => 10, // segundos
        'low_stock_threshold' => 5,
        'cache_ttl' => 300, // 5 minutos
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Auditoría
    |--------------------------------------------------------------------------
    |
    | Configuración de logging y auditoría
    |
    */
    'audit' => [
        'enabled' => env('API_AUDIT_ENABLED', true),
        'log_requests' => true,
        'log_responses' => true,
        'retention_days' => 90,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | CORS
    |--------------------------------------------------------------------------
    |
    | Configuración de CORS para el API
    |
    */
    'cors' => [
        'allowed_origins' => explode(',', env('API_ALLOWED_ORIGINS', '*')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 3600,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Respuestas
    |--------------------------------------------------------------------------
    |
    | Configuración de respuestas del API
    |
    */
    'response' => [
        'include_trace' => env('API_INCLUDE_TRACE', false),
        'pretty_print' => env('API_PRETTY_PRINT', false),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Configuración de webhooks para sincronización con servicios externos
    |
    */
    'webhooks' => [
        'enabled' => env('WEBHOOK_ENABLED', false),
        'url' => env('WEBHOOK_URL', ''),
        'token' => env('WEBHOOK_TOKEN', ''),
        'timeout' => env('WEBHOOK_TIMEOUT', 5),
        'retry_attempts' => env('WEBHOOK_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WEBHOOK_RETRY_DELAY', 1000), // milisegundos
        'verify_ssl' => env('WEBHOOK_VERIFY_SSL', true),
        'log_requests' => env('WEBHOOK_LOG_REQUESTS', true),
        'log_responses' => env('WEBHOOK_LOG_RESPONSES', true),
        'async' => env('WEBHOOK_ASYNC', true), // Enviar en cola
        
        // Eventos a sincronizar
        'eventos' => [
            'inventario.stock_actualizado' => true,
            'inventario.compra_recibida' => true,
            'inventario.venta_realizada' => true,
            'inventario.factura_anulada' => true,
            'inventario.sincronizacion_completa' => true,
        ],
    ],
];

