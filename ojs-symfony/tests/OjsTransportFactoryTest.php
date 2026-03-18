<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\Messenger\OjsTransportFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;

class OjsTransportFactoryTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsTransportFactory::class));
    }

    public function testImplementsTransportFactoryInterface(): void
    {
        $ref = new \ReflectionClass(OjsTransportFactory::class);
        $this->assertTrue($ref->implementsInterface(TransportFactoryInterface::class));
    }

    public function testSupportsOjsDsn(): void
    {
        $factory = new OjsTransportFactory();

        $this->assertTrue($factory->supports('ojs://localhost:8080', []));
        $this->assertTrue($factory->supports('ojs://ojs.example.com:9090?queue=emails', []));
        $this->assertTrue($factory->supports('ojss://secure.example.com:443', []));
    }

    public function testDoesNotSupportOtherDsn(): void
    {
        $factory = new OjsTransportFactory();

        $this->assertFalse($factory->supports('amqp://localhost', []));
        $this->assertFalse($factory->supports('redis://localhost', []));
        $this->assertFalse($factory->supports('doctrine://default', []));
    }

    public function testCreateTransportReturnOjsTransport(): void
    {
        $factory = new OjsTransportFactory();
        $transport = $factory->createTransport('ojs://localhost:8080?queue=test', []);

        $this->assertInstanceOf(
            \OpenJobSpec\Symfony\Messenger\OjsTransport::class,
            $transport,
        );
    }

    public function testCreateTransportWithOptions(): void
    {
        $factory = new OjsTransportFactory();
        $transport = $factory->createTransport('ojs://custom:9090', [
            'queue' => 'high-priority',
            'auth_token' => 'secret',
            'timeout' => 60,
        ]);

        $this->assertInstanceOf(
            \OpenJobSpec\Symfony\Messenger\OjsTransport::class,
            $transport,
        );
    }

    public function testCreateTransportWithInjectedClient(): void
    {
        $fakeTransport = new \OpenJobSpec\Testing\FakeTransport();
        $client = new \OpenJobSpec\Client('http://fake', ['transport' => $fakeTransport]);

        $factory = new OjsTransportFactory($client);
        $transport = $factory->createTransport('ojs://ignored:1234', []);

        $this->assertInstanceOf(
            \OpenJobSpec\Symfony\Messenger\OjsTransport::class,
            $transport,
        );
    }
}
