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
];
