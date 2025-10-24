<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Este archivo utiliza variables de entorno (.env) para definir las
    | políticas CORS de forma dinámica según el entorno (local, staging, prod).
    |
    */

    'paths' => explode(',', env('CORS_PATHS', 'api/*,sanctum/csrf-cookie')),

    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', '*')),

    //'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'https://zuma.com.pe/factoring,https://zuma.com.pe/hipotecas')),
    
    'allowed_origins' => ['*'],
    
    'allowed_origins_patterns' => [],

    'allowed_headers' => explode(',', env('CORS_ALLOWED_HEADERS', '*')),

    'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', '')),

    'max_age' => (int) env('CORS_MAX_AGE', 0),

    'supports_credentials' => filter_var(env('CORS_SUPPORTS_CREDENTIALS', true), FILTER_VALIDATE_BOOLEAN),

];
