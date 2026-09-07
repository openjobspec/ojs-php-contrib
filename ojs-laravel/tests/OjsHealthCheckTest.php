<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Health\OjsHealthCheck;
use OpenJobSpec\Client;
use PHPUnit\Framework\TestCase;

class OjsHealthCheckTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsHealthCheck::class));
    }

    public function testRunReturnsOkForHealthyBackend(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('health')
            ->willReturn(['status' => 'healthy', 'version' => '0.3.0']);

        $check = new OjsHealthCheck($client);
        $result = $check->run();

        $this->assertSame(OjsHealthCheck::STATUS_OK, $result['status']);
        $this->assertSame('OJS backend is healthy', $result['message']);
        $this->assertArrayHasKey('meta', $result);
        $this->assertSame('healthy', $result['meta']['status']);
    }

    public function testRunReturnsWarningForDegradedBackend(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('health')
            ->willReturn(['status' => 'degraded']);

        $check = new OjsHealthCheck($client);
        $result = $check->run();

        $this->assertSame(OjsHealthCheck::STATUS_WARNING, $result['status']);
        $this->assertStringContainsString('degraded', $result['message']);
    }

    public function testRunReturnsFailedOnException(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('health')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $check = new OjsHealthCheck($client);
        $result = $check->run();

        $this->assertSame(OjsHealthCheck::STATUS_FAILED, $result['status']);
        $this->assertStringContainsString('Connection refused', $result['message']);
        $this->assertEmpty($result['meta']);
    }

    public function testStatusConstants(): void
    {
        $this->assertSame('ok', OjsHealthCheck::STATUS_OK);
        $this->assertSame('warning', OjsHealthCheck::STATUS_WARNING);
        $this->assertSame('failed', OjsHealthCheck::STATUS_FAILED);
    }

    public function testRunReturnsWarningForUnknownStatus(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('health')->willReturn(['status' => 'unknown']);

        $check = new OjsHealthCheck($client);
        $result = $check->run();

        $this->assertSame(OjsHealthCheck::STATUS_WARNING, $result['status']);
    }

    public function testRunReturnsWarningForMissingStatus(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('health')->willReturn([]);

        $check = new OjsHealthCheck($client);
        $result = $check->run();

        $this->assertSame(OjsHealthCheck::STATUS_WARNING, $result['status']);
    }

    public function testConstructorRequiresClient(): void
    {
        $ref = new \ReflectionClass(OjsHealthCheck::class);
        $params = $ref->getConstructor()->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('client', $params[0]->getName());
    }
}
