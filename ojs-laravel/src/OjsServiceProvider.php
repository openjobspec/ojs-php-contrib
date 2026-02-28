<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel;

use Illuminate\Support\ServiceProvider;
use OpenJobSpec\Client;
use OpenJobSpec\Worker;
use OpenJobSpec\HttpTransport;

class OjsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ojs.php', 'ojs');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['ojs'];
            return new Client($config['url'], [
                'auth_token' => $config['auth_token'] ?? null,
                'timeout' => $config['timeout'] ?? 30,
            ]);
        });

        $this->app->alias(Client::class, 'ojs');

        $this->app->singleton(Worker::class, function ($app) {
            $config = $app['config']['ojs'];
            $workerConfig = $config['worker'] ?? [];
            return new Worker($config['url'], [
                'auth_token' => $config['auth_token'] ?? null,
                'queues' => $workerConfig['queues'] ?? ['default'],
                'concurrency' => $workerConfig['concurrency'] ?? 10,
                'poll_interval' => $workerConfig['poll_interval'] ?? 2.0,
                'heartbeat_interval' => $workerConfig['heartbeat_interval'] ?? 15.0,
                'shutdown_timeout' => $workerConfig['shutdown_timeout'] ?? 25.0,
            ]);
        });

        $this->app->singleton(HandlerRegistry::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/ojs.php' => config_path('ojs.php'),
            ], 'ojs-config');

            $this->commands([
                Console\WorkCommand::class,
                Console\StatusCommand::class,
                Console\PurgeCommand::class,
            ]);
        }
    }
}
