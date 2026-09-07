<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\DependencyInjection;

use OpenJobSpec\Client;
use OpenJobSpec\EncryptionCodec;
use OpenJobSpec\EncryptionMiddleware;
use OpenJobSpec\HttpTransport;
use OpenJobSpec\Worker;
use OpenJobSpec\Symfony\Command\CronCommand;
use OpenJobSpec\Symfony\Encryption\SymfonyKeyProvider;
use OpenJobSpec\Symfony\EventDispatcher\OjsEventListener;
use OpenJobSpec\Symfony\Health\OjsHealthCheck;
use OpenJobSpec\Symfony\Messenger\OjsTransport;
use OpenJobSpec\Symfony\Messenger\OjsTransportFactory;
use OpenJobSpec\Symfony\Scheduling\CronManager;
use OpenJobSpec\Symfony\Workflow\WorkflowFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

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

        // Register WorkflowFactory
        $workflowDef = new Definition(WorkflowFactory::class);
        $workflowDef->setArgument(0, new Reference(Client::class));
        $workflowDef->setPublic(true);
        $container->setDefinition(WorkflowFactory::class, $workflowDef);
        $container->setAlias('ojs.workflow', WorkflowFactory::class);

        // Register CronManager
        $cronDef = new Definition(CronManager::class);
        $cronDef->setArgument(0, new Reference(Client::class));
        $cronDef->setPublic(true);
        $container->setDefinition(CronManager::class, $cronDef);
        $container->setAlias('ojs.cron', CronManager::class);

        // Register CronCommand
        $cronCmdDef = new Definition(CronCommand::class);
        $cronCmdDef->setArgument(0, new Reference(CronManager::class));
        $cronCmdDef->addTag('console.command');
        $container->setDefinition(CronCommand::class, $cronCmdDef);

        // Encryption services (when enabled)
        if ($config['encryption']['enabled'] ?? false) {
            $encConfig = $config['encryption'];

            $keyProviderDef = new Definition(SymfonyKeyProvider::class);
            $keyProviderDef->setArgument(0, $encConfig['keys']);
            $keyProviderDef->setArgument(1, $encConfig['current_key_id']);
            $keyProviderDef->setPublic(true);
            $container->setDefinition(SymfonyKeyProvider::class, $keyProviderDef);
            $container->setAlias('ojs.encryption.key_provider', SymfonyKeyProvider::class);

            $codecDef = new Definition(EncryptionCodec::class);
            $codecDef->setPublic(true);
            $container->setDefinition(EncryptionCodec::class, $codecDef);
            $container->setAlias('ojs.encryption.codec', EncryptionCodec::class);

            $encMiddlewareDef = new Definition(EncryptionMiddleware::class);
            $encMiddlewareDef->setArgument(0, new Reference(EncryptionCodec::class));
            $encMiddlewareDef->setArgument(1, new Reference(SymfonyKeyProvider::class));
            $encMiddlewareDef->setPublic(true);
            $container->setDefinition(EncryptionMiddleware::class, $encMiddlewareDef);
            $container->setAlias('ojs.encryption.middleware', EncryptionMiddleware::class);
        }

        // Event listener bridge (when enabled)
        if ($config['events']['enabled'] ?? false) {
            $eventDef = new Definition(OjsEventListener::class);
            $eventDef->setArgument(0, new Reference('event_dispatcher'));
            $eventDef->setArgument(1, $config['url']);
            $eventDef->setArgument(2, $config['auth_token']);
            $eventDef->setPublic(true);
            $container->setDefinition(OjsEventListener::class, $eventDef);
            $container->setAlias('ojs.event_listener', OjsEventListener::class);
        }

        // Health check (when enabled)
        if ($config['health']['enabled'] ?? false) {
            $healthDef = new Definition(OjsHealthCheck::class);
            $healthDef->setArgument(0, new Reference(Client::class));
            $healthDef->setPublic(true);
            $container->setDefinition(OjsHealthCheck::class, $healthDef);
            $container->setAlias('ojs.health', OjsHealthCheck::class);
        }

        // Messenger transport (when enabled)
        if ($config['messenger']['enabled'] ?? false) {
            $messengerQueue = $config['messenger']['queue'];

            $transportDef = new Definition(OjsTransport::class);
            $transportDef->setArgument(0, new Reference(Client::class));
            $transportDef->setArgument(1, $messengerQueue);
            $transportDef->setPublic(true);
            $container->setDefinition(OjsTransport::class, $transportDef);
            $container->setAlias('ojs.messenger.transport', OjsTransport::class);

            $factoryDef = new Definition(OjsTransportFactory::class);
            $factoryDef->setArgument(0, new Reference(Client::class));
            $factoryDef->addTag('messenger.transport_factory');
            $container->setDefinition(OjsTransportFactory::class, $factoryDef);
            $container->setAlias('ojs.messenger.transport_factory', OjsTransportFactory::class);
        }
    }

    public function getAlias(): string
    {
        return 'ojs';
    }
}
