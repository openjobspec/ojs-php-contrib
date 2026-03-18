<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Client;
use OpenJobSpec\Symfony\Health\OjsHealthCheck;
use OpenJobSpec\Testing\FakeTransport;
use PHPUnit\Framework\TestCase;

class OjsHealthCheckTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsHealthCheck::class));
    }

    public function testConstructorRequiresClient(): void
    {
        $ref = new \ReflectionClass(OjsHealthCheck::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $this->assertSame(Client::class, $constructor->getParameters()[0]->getType()->getName());
    }

    public function testHasCheckMethod(): void
    {
        $ref = new \ReflectionClass(OjsHealthCheck::class);
        $this->assertTrue($ref->hasMethod('check'));
    }

    public function testHasIsHealthyMethod(): void
    {
        $ref = new \ReflectionClass(OjsHealthCheck::class);
        $this->assertTrue($ref->hasMethod('isHealthy'));
    }

    public function testHasManifestMethod(): void
    {
        $ref = new \ReflectionClass(OjsHealthCheck::class);
        $this->assertTrue($ref->hasMethod('manifest'));
    }

    public function testCheckReturnsHealthyForOkBackend(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $healthCheck = new OjsHealthCheck($client);

        $result = $healthCheck->check();

        $this->assertTrue($result['healthy']);
        $this->assertSame('ok', $result['status']);
        $this->assertNull($result['error']);
        $this->assertIsArray($result['details']);
    }

    public function testIsHealthyReturnsTrue(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $healthCheck = new OjsHealthCheck($client);

        $this->assertTrue($healthCheck->isHealthy());
    }

    public function testManifestReturnsData(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $healthCheck = new OjsHealthCheck($client);

        $manifest = $healthCheck->manifest();

        $this->assertArrayHasKey('version', $manifest);
        $this->assertArrayHasKey('level', $manifest);
    }

    public function testCheckResultStructure(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $healthCheck = new OjsHealthCheck($client);

        $result = $healthCheck->check();

        $this->assertArrayHasKey('healthy', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('error', $result);
    }
}
