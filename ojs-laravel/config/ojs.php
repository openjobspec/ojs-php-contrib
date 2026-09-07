<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OJS Backend URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your OJS-compliant backend server. All SDKs communicate
    | with this URL for job operations.
    |
    */
    'url' => env('OJS_URL', 'http://localhost:8080'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Token
    |--------------------------------------------------------------------------
    |
    | Optional Bearer token for authenticated OJS backends.
    |
    */
    'auth_token' => env('OJS_AUTH_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Default Queue
    |--------------------------------------------------------------------------
    |
    | The default queue name for jobs that don't specify one.
    |
    */
    'default_queue' => env('OJS_DEFAULT_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum time in seconds for HTTP requests to the OJS backend.
    |
    */
    'timeout' => env('OJS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Worker Configuration
    |--------------------------------------------------------------------------
    */
    'worker' => [
        'queues' => explode(',', env('OJS_QUEUES', 'default')),
        'concurrency' => (int) env('OJS_CONCURRENCY', 10),
        'poll_interval' => (float) env('OJS_POLL_INTERVAL', 2.0),
        'heartbeat_interval' => 15.0,
        'shutdown_timeout' => 25.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption
    |--------------------------------------------------------------------------
    |
    | Enable transparent encryption of job args using AES-256-GCM. When
    | enabled, the LaravelKeyProvider uses APP_KEY by default. Add named
    | keys for key rotation support.
    |
    */
    'encryption' => [
        'enabled' => (bool) env('OJS_ENCRYPTION_ENABLED', false),
        'keys' => [
            // 'v2' => env('OJS_ENCRYPTION_KEY_V2'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Events (SSE)
    |--------------------------------------------------------------------------
    |
    | Enable the OJS event subscriber that bridges OJS Server-Sent Events
    | to Laravel's event dispatcher. When enabled, OjsEventSubscriber is
    | registered as a singleton in the container.
    |
    */
    'events' => [
        'enabled' => (bool) env('OJS_EVENTS_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Check
    |--------------------------------------------------------------------------
    |
    | Enable the OJS health check integration. When enabled, OjsHealthCheck
    | is registered in the container for use with Laravel's health monitoring.
    |
    */
    'health' => [
        'enabled' => (bool) env('OJS_HEALTH_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Driver
    |--------------------------------------------------------------------------
    |
    | Enable the 'ojs' Laravel Queue driver so you can use Queue::push()
    | with OJS as the backend. Add an 'ojs' connection to config/queue.php:
    |
    |   'ojs' => ['driver' => 'ojs', 'queue' => 'default'],
    |
    */
    'queue_driver' => [
        'enabled' => (bool) env('OJS_QUEUE_DRIVER_ENABLED', false),
    ],
];
