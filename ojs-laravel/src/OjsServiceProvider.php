<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use OpenJobSpec\Client;
use OpenJobSpec\EncryptionCodec;
use OpenJobSpec\EncryptionMiddleware;
use OpenJobSpec\KeyProvider;
use OpenJobSpec\Worker;
use OpenJobSpec\Laravel\Encryption\LaravelKeyProvider;
use OpenJobSpec\Laravel\Events\OjsEventSubscriber;
use OpenJobSpec\Laravel\Health\OjsHealthCheck;
use OpenJobSpec\Laravel\Queue\OjsQueueConnector;
use OpenJobSpec\Laravel\Scheduling\OjsCronBridge;
use OpenJobSpec\Laravel\Workflows\WorkflowBuilder;

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

        $this->registerWorkflowBuilder();
        $this->registerEventSubscriber();
        $this->registerCronBridge();
        $this->registerEncryption();
        $this->registerHealthCheck();
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

        $this->registerQueueConnector();
    }

    private function registerWorkflowBuilder(): void
    {
        $this->app->singleton(WorkflowBuilder::class, function ($app) {
            return new WorkflowBuilder($app->make(Client::class));
        });
    }

    private function registerEventSubscriber(): void
    {
        if (!($this->app['config']['ojs.events.enabled'] ?? false)) {
            return;
        }

        $this->app->singleton(OjsEventSubscriber::class, function ($app) {
            return new OjsEventSubscriber(
                $app->make(Dispatcher::class),
                $app->make(Client::class),
            );
        });
    }

    private function registerCronBridge(): void
    {
        $this->app->singleton(OjsCronBridge::class, function ($app) {
            return new OjsCronBridge($app->make(Client::class));
        });
    }

    private function registerEncryption(): void
    {
        if (!($this->app['config']['ojs.encryption.enabled'] ?? false)) {
            return;
        }

        $this->app->singleton(LaravelKeyProvider::class, function ($app) {
            $keys = $app['config']['ojs.encryption.keys'] ?? [];
            return new LaravelKeyProvider($keys);
        });

        $this->app->alias(LaravelKeyProvider::class, KeyProvider::class);

        $this->app->singleton(EncryptionCodec::class);

        $this->app->singleton(EncryptionMiddleware::class, function ($app) {
            return new EncryptionMiddleware(
                $app->make(EncryptionCodec::class),
                $app->make(LaravelKeyProvider::class),
            );
        });
    }

    private function registerHealthCheck(): void
    {
        if (!($this->app['config']['ojs.health.enabled'] ?? false)) {
            return;
        }

        $this->app->singleton(OjsHealthCheck::class, function ($app) {
            return new OjsHealthCheck($app->make(Client::class));
        });
    }

    private function registerQueueConnector(): void
    {
        if (!($this->app['config']['ojs.queue_driver.enabled'] ?? false)) {
            return;
        }

        /** @var \Illuminate\Queue\QueueManager $manager */
        $manager = $this->app['queue'];
        $manager->addConnector('ojs', function () {
            return new OjsQueueConnector($this->app->make(Client::class));
        });
    }
}
