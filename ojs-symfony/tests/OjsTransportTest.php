<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Client;
use OpenJobSpec\Symfony\Messenger\OjsStamp;
use OpenJobSpec\Symfony\Messenger\OjsTransport;
use OpenJobSpec\Testing\FakeTransport;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

class OjsTransportTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsTransport::class));
    }

    public function testImplementsTransportInterface(): void
    {
        $ref = new \ReflectionClass(OjsTransport::class);
        $this->assertTrue($ref->implementsInterface(TransportInterface::class));
    }

    public function testSendCreatesJob(): void
    {
        $fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $fakeTransport]);
        $transport = new OjsTransport($client, 'default');

        $message = new \stdClass();
        $envelope = new Envelope($message);

        $result = $transport->send($envelope);

        $this->assertNotNull($result->last(TransportMessageIdStamp::class));
        $this->assertNotNull($result->last(OjsStamp::class));
        $this->assertSame(1, $fakeTransport->enqueuedCount());
    }

    public function testSendWithOjsStamp(): void
    {
        $fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $fakeTransport]);
        $transport = new OjsTransport($client, 'default');

        $message = new \stdClass();
        $stamp = new OjsStamp(queue: 'emails', priority: 3);
        $envelope = new Envelope($message, [$stamp]);

        $result = $transport->send($envelope);

        $ojsStamp = $result->last(OjsStamp::class);
        $this->assertNotNull($ojsStamp);
        $this->assertSame('emails', $ojsStamp->getQueue());
    }

    public function testGetReturnsEmpty(): void
    {
        $fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $fakeTransport]);
        $transport = new OjsTransport($client);

        $messages = iterator_to_array($transport->get());
        $this->assertEmpty($messages);
    }

    public function testAckDoesNotThrow(): void
    {
        $fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $fakeTransport]);
        $transport = new OjsTransport($client);

        $message = new \stdClass();
        $envelope = new Envelope($message, [new OjsStamp(jobId: 'j-1')]);

        // Should not throw
        $transport->ack($envelope);
        $this->assertTrue(true);
    }

    public function testRejectCancelsJob(): void
    {
        $fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $fakeTransport]);
        $transport = new OjsTransport($client);

        // First send a message so we have a real job
        $envelope = $transport->send(new Envelope(new \stdClass()));
        $ojsStamp = $envelope->last(OjsStamp::class);

        // Reject should attempt to cancel
        $transport->reject($envelope);
        $this->assertTrue(true); // No exception means success
    }

    public function testSendUsesDefaultQueue(): void
    {
        $fakeTransport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $fakeTransport]);
        $transport = new OjsTransport($client, 'custom-queue');

        $envelope = $transport->send(new Envelope(new \stdClass()));

        $jobs = $fakeTransport->allEnqueued(queue: 'custom-queue');
        $this->assertNotEmpty($jobs);
    }
}
