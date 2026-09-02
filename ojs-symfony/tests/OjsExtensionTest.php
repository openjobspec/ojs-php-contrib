<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\DependencyInjection\OjsExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OjsExtensionTest extends TestCase
{
    public function testExtensionAlias(): void
    {
        $extension = new OjsExtension();
        $this->assertSame('ojs', $extension->getAlias());
    }

    public function testLoadRegistersClientService(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasDefinition('OpenJobSpec\Client'),
            'Client service should be registered'
        );
    }

    public function testLoadRegistersWorkerService(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasDefinition('OpenJobSpec\Worker'),
            'Worker service should be registered'
        );
    }

    public function testLoadRegistersClientAlias(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasAlias('ojs.client'),
            'ojs.client alias should be registered'
        );
    }

    public function testLoadRegistersWorkerAlias(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasAlias('ojs.worker'),
            'ojs.worker alias should be registered'
        );
    }

    public function testLoadSetsParameters(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue($container->hasParameter('ojs.url'));
        $this->assertTrue($container->hasParameter('ojs.auth_token'));
        $this->assertTrue($container->hasParameter('ojs.default_queue'));
        $this->assertTrue($container->hasParameter('ojs.timeout'));
    }

    public function testLoadDefaultParameterValues(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertSame('http://localhost:8080', $container->getParameter('ojs.url'));
        $this->assertNull($container->getParameter('ojs.auth_token'));
        $this->assertSame('default', $container->getParameter('ojs.default_queue'));
        $this->assertSame(30, $container->getParameter('ojs.timeout'));
    }

    public function testLoadCustomConfiguration(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([[
            'url' => 'http://custom:9090',
            'auth_token' => 'secret',
            'default_queue' => 'high',
            'timeout' => 60,
        ]], $container);

        $this->assertSame('http://custom:9090', $container->getParameter('ojs.url'));
        $this->assertSame('secret', $container->getParameter('ojs.auth_token'));
        $this->assertSame('high', $container->getParameter('ojs.default_queue'));
        $this->assertSame(60, $container->getParameter('ojs.timeout'));
    }

    public function testClientServiceIsPublic(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $def = $container->getDefinition('OpenJobSpec\Client');
        $this->assertTrue($def->isPublic());
    }

    public function testWorkerServiceIsPublic(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $def = $container->getDefinition('OpenJobSpec\Worker');
        $this->assertTrue($def->isPublic());
    }

    public function testLoadRegistersWorkflowFactory(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasDefinition('OpenJobSpec\Symfony\Workflow\WorkflowFactory'),
            'WorkflowFactory service should be registered'
        );
        $this->assertTrue($container->hasAlias('ojs.workflow'));
    }

    public function testLoadRegistersCronManager(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasDefinition('OpenJobSpec\Symfony\Scheduling\CronManager'),
            'CronManager service should be registered'
        );
        $this->assertTrue($container->hasAlias('ojs.cron'));
    }

    public function testLoadRegistersCronCommand(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertTrue(
            $container->hasDefinition('OpenJobSpec\Symfony\Command\CronCommand'),
            'CronCommand should be registered'
        );
    }

    public function testEncryptionServicesNotRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertFalse($container->hasDefinition('OpenJobSpec\Symfony\Encryption\SymfonyKeyProvider'));
        $this->assertFalse($container->hasDefinition('OpenJobSpec\EncryptionCodec'));
        $this->assertFalse($container->hasDefinition('OpenJobSpec\EncryptionMiddleware'));
    }

    public function testEncryptionServicesRegisteredWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([[
            'encryption' => [
                'enabled' => true,
                'current_key_id' => 'v1',
                'keys' => ['v1' => str_repeat('a', 64)],
            ],
        ]], $container);

        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Encryption\SymfonyKeyProvider'));
        $this->assertTrue($container->hasAlias('ojs.encryption.key_provider'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\EncryptionCodec'));
        $this->assertTrue($container->hasAlias('ojs.encryption.codec'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\EncryptionMiddleware'));
        $this->assertTrue($container->hasAlias('ojs.encryption.middleware'));
    }

    public function testEventListenerNotRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertFalse($container->hasDefinition('OpenJobSpec\Symfony\EventDispatcher\OjsEventListener'));
    }

    public function testEventListenerRegisteredWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([[
            'events' => ['enabled' => true],
        ]], $container);

        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\EventDispatcher\OjsEventListener'));
        $this->assertTrue($container->hasAlias('ojs.event_listener'));
    }

    public function testHealthCheckNotRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertFalse($container->hasDefinition('OpenJobSpec\Symfony\Health\OjsHealthCheck'));
    }

    public function testHealthCheckRegisteredWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([[
            'health' => ['enabled' => true],
        ]], $container);

        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Health\OjsHealthCheck'));
        $this->assertTrue($container->hasAlias('ojs.health'));
    }

    public function testMessengerNotRegisteredByDefault(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([], $container);

        $this->assertFalse($container->hasDefinition('OpenJobSpec\Symfony\Messenger\OjsTransport'));
        $this->assertFalse($container->hasDefinition('OpenJobSpec\Symfony\Messenger\OjsTransportFactory'));
    }

    public function testMessengerRegisteredWhenEnabled(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([[
            'messenger' => ['enabled' => true],
        ]], $container);

        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Messenger\OjsTransport'));
        $this->assertTrue($container->hasAlias('ojs.messenger.transport'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Messenger\OjsTransportFactory'));
        $this->assertTrue($container->hasAlias('ojs.messenger.transport_factory'));
    }

    public function testAllOptInServicesRegisterTogether(): void
    {
        $container = new ContainerBuilder();
        $extension = new OjsExtension();

        $extension->load([[
            'encryption' => [
                'enabled' => true,
                'current_key_id' => 'v1',
                'keys' => ['v1' => str_repeat('a', 64)],
            ],
            'events' => ['enabled' => true],
            'health' => ['enabled' => true],
            'messenger' => ['enabled' => true],
        ]], $container);

        // Always-on services remain registered alongside the opt-in ones.
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Client'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Worker'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Workflow\WorkflowFactory'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Scheduling\CronManager'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Command\CronCommand'));

        // Every opt-in group registers when all flags are enabled at once.
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Encryption\SymfonyKeyProvider'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\EncryptionMiddleware'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\EventDispatcher\OjsEventListener'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Health\OjsHealthCheck'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Messenger\OjsTransport'));
        $this->assertTrue($container->hasDefinition('OpenJobSpec\Symfony\Messenger\OjsTransportFactory'));
    }
}
