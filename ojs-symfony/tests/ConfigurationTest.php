<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testConfigurationClassExists(): void
    {
        $this->assertTrue(class_exists(Configuration::class));
    }

    public function testDefaultConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertSame('http://localhost:8080', $config['url']);
        $this->assertNull($config['auth_token']);
        $this->assertSame('default', $config['default_queue']);
        $this->assertSame(30, $config['timeout']);
    }

    public function testDefaultWorkerConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('worker', $config);
        $worker = $config['worker'];
        $this->assertSame(['default'], $worker['queues']);
        $this->assertSame(10, $worker['concurrency']);
        $this->assertSame(2.0, $worker['poll_interval']);
        $this->assertSame(15.0, $worker['heartbeat_interval']);
        $this->assertSame(25.0, $worker['shutdown_timeout']);
    }

    public function testCustomConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'url' => 'http://ojs.example.com:8080',
            'auth_token' => 'my-secret-token',
            'default_queue' => 'high-priority',
            'timeout' => 60,
            'worker' => [
                'queues' => ['emails', 'notifications'],
                'concurrency' => 20,
                'poll_interval' => 1.0,
                'heartbeat_interval' => 10.0,
                'shutdown_timeout' => 30.0,
            ],
        ]]);

        $this->assertSame('http://ojs.example.com:8080', $config['url']);
        $this->assertSame('my-secret-token', $config['auth_token']);
        $this->assertSame('high-priority', $config['default_queue']);
        $this->assertSame(60, $config['timeout']);
        $this->assertSame(['emails', 'notifications'], $config['worker']['queues']);
        $this->assertSame(20, $config['worker']['concurrency']);
        $this->assertSame(1.0, $config['worker']['poll_interval']);
    }

    public function testPartialOverride(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'url' => 'http://custom:9090',
        ]]);

        $this->assertSame('http://custom:9090', $config['url']);
        // Defaults preserved for unset values
        $this->assertNull($config['auth_token']);
        $this->assertSame('default', $config['default_queue']);
        $this->assertSame(30, $config['timeout']);
        $this->assertSame(10, $config['worker']['concurrency']);
    }

    public function testTreeBuilderRootName(): void
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $this->assertSame('ojs', $treeBuilder->buildTree()->getName());
    }

    public function testDefaultEncryptionDisabled(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('encryption', $config);
        $this->assertFalse($config['encryption']['enabled']);
    }

    public function testDefaultEventsDisabled(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('events', $config);
        $this->assertFalse($config['events']['enabled']);
    }

    public function testDefaultHealthDisabled(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('health', $config);
        $this->assertFalse($config['health']['enabled']);
    }

    public function testDefaultMessengerDisabled(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, []);

        $this->assertArrayHasKey('messenger', $config);
        $this->assertFalse($config['messenger']['enabled']);
    }

    public function testEncryptionConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'encryption' => [
                'enabled' => true,
                'current_key_id' => 'v1',
                'keys' => ['v1' => 'abcdef1234567890abcdef1234567890'],
            ],
        ]]);

        $this->assertTrue($config['encryption']['enabled']);
        $this->assertSame('v1', $config['encryption']['current_key_id']);
        $this->assertArrayHasKey('v1', $config['encryption']['keys']);
    }

    public function testEventsEnabled(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'events' => ['enabled' => true],
        ]]);

        $this->assertTrue($config['events']['enabled']);
    }

    public function testHealthEnabled(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'health' => ['enabled' => true],
        ]]);

        $this->assertTrue($config['health']['enabled']);
    }

    public function testMessengerConfiguration(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'messenger' => [
                'enabled' => true,
                'queue' => 'emails',
            ],
        ]]);

        $this->assertTrue($config['messenger']['enabled']);
        $this->assertSame('emails', $config['messenger']['queue']);
    }

    public function testMessengerDefaultQueue(): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [[
            'messenger' => ['enabled' => true],
        ]]);

        $this->assertSame('default', $config['messenger']['queue']);
    }
}
