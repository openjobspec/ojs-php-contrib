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
}
