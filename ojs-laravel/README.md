# OJS Laravel

Laravel 11+ integration for [Open Job Spec](https://openjobspec.org).

## Installation

```bash
composer require openjobspec/laravel
```

The service provider is auto-discovered. Publish the configuration:

```bash
php artisan vendor:publish --tag=ojs-config
```

## Configuration

```php
// config/ojs.php
return [
    'url' => env('OJS_URL', 'http://localhost:8080'),
    'auth_token' => env('OJS_AUTH_TOKEN'),
    'default_queue' => env('OJS_DEFAULT_QUEUE', 'default'),
    'timeout' => env('OJS_TIMEOUT', 30),

    'worker' => [
        'queues' => ['default'],
        'concurrency' => env('OJS_CONCURRENCY', 10),
        'poll_interval' => env('OJS_POLL_INTERVAL', 2.0),
        'heartbeat_interval' => 15.0,
        'shutdown_timeout' => 25.0,
    ],
];
```

## Usage

### Enqueue Jobs

```php
use OpenJobSpec\Laravel\Facades\Ojs;

// Simple enqueue
$job = Ojs::enqueue('email.send', ['user@example.com', 'Welcome!']);

// With options
$job = Ojs::enqueue('report.generate', [42], [
    'queue' => 'reports',
    'priority' => 10,
    'retry' => new \OpenJobSpec\RetryPolicy(maxAttempts: 5),
]);

// Batch enqueue
$jobs = Ojs::enqueueBatch([
    ['type' => 'email.send', 'args' => ['a@b.com']],
    ['type' => 'email.send', 'args' => ['c@d.com']],
]);
```

### Transactional Enqueue

Jobs are only enqueued after the database transaction commits:

```php
use Illuminate\Support\Facades\DB;
use OpenJobSpec\Laravel\Facades\Ojs;

DB::transaction(function () {
    $user = User::create(['email' => 'user@example.com']);

    // Only enqueued if the transaction commits successfully
    Ojs::enqueueAfterCommit('welcome.email', [$user->id]);
});
```

### Job Handlers

Define handlers using the `#[OjsJob]` attribute:

```php
use OpenJobSpec\Laravel\Attributes\OjsJob;
use OpenJobSpec\JobContext;

#[OjsJob('email.send', queue: 'emails')]
class SendEmailHandler
{
    public function __invoke(JobContext $ctx): mixed
    {
        $email = $ctx->job->args[0];
        Mail::to($email)->send(new WelcomeMail());
        return ['status' => 'sent'];
    }
}
```

### Worker Command

```bash
# Start the worker
php artisan ojs:work

# With options
php artisan ojs:work --queues=emails,reports --concurrency=20

# Check status
php artisan ojs:status

# Purge dead letter queue
php artisan ojs:purge --queue=default
```

### Middleware

Add OJS middleware to inject the client into requests:

```php
// app/Http/Kernel.php or bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\OpenJobSpec\Laravel\Http\OjsMiddleware::class);
})
```

Access the client from any request:

```php
public function store(Request $request)
{
    $client = $request->attributes->get('ojs');
    $client->enqueue('user.created', [$request->user()->id]);
}
```

## Testing

Use the fake transport in tests:

```php
use OpenJobSpec\Laravel\Facades\Ojs;

public function test_user_creation_enqueues_welcome_email(): void
{
    Ojs::fake();

    $this->post('/users', ['email' => 'test@example.com']);

    Ojs::assertEnqueued('welcome.email');
    Ojs::assertEnqueuedCount(1);
}
```

## Requirements

- PHP 8.2+
- Laravel 11+

## License

Apache-2.0
