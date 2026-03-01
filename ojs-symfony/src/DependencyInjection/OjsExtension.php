<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\DependencyInjection;

use OpenJobSpec\Client;
use OpenJobSpec\HttpTransport;
use OpenJobSpec\Worker;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

class OjsExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('ojs.url', $config['url']);
        $container->setParameter('ojs.auth_token', $config['auth_token']);
        $container->setParameter('ojs.default_queue', $config['default_queue']);
        $container->setParameter('ojs.timeout', $config['timeout']);

        // Register Client as a service
        $clientDef = new Definition(Client::class);
        $clientDef->setArgument(0, $config['url']);
        $clientDef->setArgument(1, [
            'auth_token' => $config['auth_token'],
            'timeout' => $config['timeout'],
        ]);
        $clientDef->setPublic(true);
        $container->setDefinition(Client::class, $clientDef);
        $container->setAlias('ojs.client', Client::class);

        // Register Worker as a service
        $workerConfig = $config['worker'];
        $workerDef = new Definition(Worker::class);
        $workerDef->setArgument(0, $config['url']);
        $workerDef->setArgument(1, [
            'auth_token' => $config['auth_token'],
            'queues' => $workerConfig['queues'],
            'concurrency' => $workerConfig['concurrency'],
            'poll_interval' => $workerConfig['poll_interval'],
            'heartbeat_interval' => $workerConfig['heartbeat_interval'],
            'shutdown_timeout' => $workerConfig['shutdown_timeout'],
        ]);
        $workerDef->setPublic(true);
        $container->setDefinition(Worker::class, $workerDef);
        $container->setAlias('ojs.worker', Worker::class);
    }

    public function getAlias(): string
    {
        return 'ojs';
    }
}
