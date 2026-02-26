<?php

return [
    'app' => [
        'env' => env('APP_ENV', 'production'),
        'debug' => (bool) env('APP_DEBUG', false),
        'url' => env('APP_URL', 'http://localhost'),
        'timezone' => 'UTC',
        'locale' => 'en',
        'fallback_locale' => 'en',
        'key' => env('APP_KEY'),
        'cipher' => 'AES-256-CBC',
    ],
    'database' => [
        'default' => env('DB_CONNECTION', 'mysql'),
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'game'),
                'username' => env('DB_USERNAME', 'game'),
                'password' => env('DB_PASSWORD', ''),
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
            ],
        ],
        'migrations' => 'migrations',
    ],
    'redis' => [
        'client' => env('REDIS_CLIENT', 'predis'),
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
        ],
        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
        ],
    ],
    'jwt' => [
        'secret' => env('JWT_SECRET', ''),
        'ttl' => env('JWT_TTL', 3600),
    ],
];
