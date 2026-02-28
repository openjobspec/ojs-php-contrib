<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the OJS Laravel configuration defaults.
 */
class ConfigTest extends TestCase
{
    private array $config;

    protected function setUp(): void
    {
        $this->config = require __DIR__ . '/../config/ojs.php';
    }

    public function testConfigReturnsArray(): void
    {
        $this->assertIsArray($this->config);
    }

    public function testDefaultUrl(): void
    {
        $this->assertArrayHasKey('url', $this->config);
    }

    public function testDefaultQueueExists(): void
    {
        $this->assertArrayHasKey('default_queue', $this->config);
    }

    public function testTimeoutExists(): void
    {
        $this->assertArrayHasKey('timeout', $this->config);
    }

    public function testWorkerConfigExists(): void
    {
        $this->assertArrayHasKey('worker', $this->config);
        $worker = $this->config['worker'];
        $this->assertArrayHasKey('queues', $worker);
        $this->assertArrayHasKey('concurrency', $worker);
        $this->assertArrayHasKey('poll_interval', $worker);
        $this->assertArrayHasKey('heartbeat_interval', $worker);
        $this->assertArrayHasKey('shutdown_timeout', $worker);
    }

    public function testAuthTokenKeyExists(): void
    {
        $this->assertArrayHasKey('auth_token', $this->config);
    }

    public function testWorkerConcurrencyIsInt(): void
    {
        $this->assertIsInt($this->config['worker']['concurrency']);
    }

    public function testWorkerPollIntervalIsFloat(): void
    {
        $this->assertIsFloat($this->config['worker']['poll_interval']);
    }
}
