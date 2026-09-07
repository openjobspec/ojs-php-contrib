<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Queue\OjsQueueConnector;
use OpenJobSpec\Laravel\Queue\OjsQueue;
use OpenJobSpec\Laravel\Queue\OjsQueueJob;
use OpenJobSpec\Client;
use OpenJobSpec\Job as OjsJob;
use OpenJobSpec\QueueStats;
use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use PHPUnit\Framework\TestCase;

class QueueDriverTest extends TestCase
{
    // ── Connector ───────────────────────────────────────────

    public function testConnectorClassExists(): void
    {
        $this->assertTrue(class_exists(OjsQueueConnector::class));
    }

    public function testConnectorImplementsInterface(): void
    {
        $ref = new \ReflectionClass(OjsQueueConnector::class);
        $this->assertTrue($ref->implementsInterface(ConnectorInterface::class));
    }

    public function testConnectorConnectReturnsOjsQueue(): void
    {
        $client = $this->createMock(Client::class);
        $connector = new OjsQueueConnector($client);

        $queue = $connector->connect(['queue' => 'custom']);
        $this->assertInstanceOf(OjsQueue::class, $queue);
    }

    // ── Queue ───────────────────────────────────────────────

    public function testQueueClassExists(): void
    {
        $this->assertTrue(class_exists(OjsQueue::class));
    }

    public function testQueueImplementsQueueContract(): void
    {
        $ref = new \ReflectionClass(OjsQueue::class);
        $this->assertTrue($ref->implementsInterface(QueueContract::class));
    }

    public function testQueuePushEnqueuesJob(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-123',
            type: 'test.job',
            args: ['hello'],
            queue: 'default',
            state: 'available',
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('enqueue')
            ->willReturn($ojsJob);

        $queue = new OjsQueue($client, 'default');
        $id = $queue->push('test.job', ['hello']);

        $this->assertSame('j-123', $id);
    }

    public function testQueuePushWithCustomQueue(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-456',
            type: 'test.job',
            args: [],
            queue: 'emails',
            state: 'available',
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(fn(array $opts) => ($opts['queue'] ?? null) === 'emails'),
            )
            ->willReturn($ojsJob);

        $queue = new OjsQueue($client, 'default');
        $queue->push('test.job', '', 'emails');
    }

    public function testQueueLaterEnqueuesWithScheduledAt(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-789',
            type: 'test.job',
            args: [],
            queue: 'default',
            state: 'scheduled',
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('enqueue')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(fn(array $opts) => isset($opts['scheduled_at'])),
            )
            ->willReturn($ojsJob);

        $queue = new OjsQueue($client, 'default');
        $id = $queue->later(60, 'test.job');

        $this->assertSame('j-789', $id);
    }

    public function testQueueLaterWithDateTimeInterface(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-dt',
            type: 'test.job',
            args: [],
            queue: 'default',
            state: 'scheduled',
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('enqueue')
            ->willReturn($ojsJob);

        $queue = new OjsQueue($client, 'default');
        $future = new \DateTimeImmutable('+1 hour');
        $id = $queue->later($future, 'test.job');

        $this->assertSame('j-dt', $id);
    }

    public function testQueueSizeUsesQueueStats(): void
    {
        $stats = new QueueStats(
            name: 'default',
            available: 5,
            active: 3,
            completed: 100,
            retryable: 2,
            discarded: 1,
            scheduled: 10,
            paused: false,
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('getQueueStats')
            ->with('default')
            ->willReturn($stats);

        $queue = new OjsQueue($client, 'default');
        $this->assertSame(18, $queue->size()); // 5 + 3 + 10
    }

    public function testQueueSizeReturnsZeroOnError(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('getQueueStats')
            ->willThrowException(new \RuntimeException('unreachable'));

        $queue = new OjsQueue($client, 'default');
        $this->assertSame(0, $queue->size());
    }

    public function testQueuePopThrows(): void
    {
        $client = $this->createMock(Client::class);
        $queue = new OjsQueue($client);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('does not support pop');
        $queue->pop();
    }

    public function testQueuePushRaw(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-raw',
            type: 'laravel.raw',
            args: ['{"type":"raw","data":"test"}'],
            queue: 'default',
            state: 'available',
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('enqueue')
            ->willReturn($ojsJob);

        $queue = new OjsQueue($client, 'default');
        $id = $queue->pushRaw('{"type":"raw","data":"test"}');

        $this->assertSame('j-raw', $id);
    }

    public function testGetQueue(): void
    {
        $client = $this->createMock(Client::class);
        $queue = new OjsQueue($client, 'default');

        $this->assertSame('default', $queue->getQueue());
        $this->assertSame('custom', $queue->getQueue('custom'));
    }

    public function testGetClient(): void
    {
        $client = $this->createMock(Client::class);
        $queue = new OjsQueue($client);

        $this->assertSame($client, $queue->getClient());
    }

    // ── Queue Job ───────────────────────────────────────────

    public function testQueueJobClassExists(): void
    {
        $this->assertTrue(class_exists(OjsQueueJob::class));
    }

    public function testQueueJobGetRawBody(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-100',
            type: 'email.send',
            args: ['to' => 'user@example.com'],
            queue: 'emails',
            state: 'active',
            attempt: 2,
            meta: ['laravel.job_class' => 'SendEmail'],
        );

        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs', 'emails');

        $raw = json_decode($job->getRawBody(), true);
        $this->assertSame('email.send', $raw['type']);
        $this->assertSame('j-100', $raw['id']);
        $this->assertSame(2, $raw['attempt']);
    }

    public function testQueueJobGetJobId(): void
    {
        $ojsJob = new OjsJob(id: 'j-200', type: 'test', args: [], queue: 'default', state: 'active');
        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs', 'default');

        $this->assertSame('j-200', $job->getJobId());
    }

    public function testQueueJobAttempts(): void
    {
        $ojsJob = new OjsJob(id: 'j-300', type: 'test', args: [], queue: 'default', state: 'active', attempt: 3);
        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs', 'default');

        $this->assertSame(3, $job->attempts());
    }

    public function testQueueJobGetName(): void
    {
        $ojsJob = new OjsJob(
            id: 'j-400',
            type: 'email.send',
            args: [],
            queue: 'default',
            state: 'active',
            meta: ['laravel.job_class' => 'App\\Jobs\\SendEmail'],
        );
        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs', 'default');

        $this->assertSame('App\\Jobs\\SendEmail', $job->getName());
    }

    public function testQueueJobGetNameFallsBackToType(): void
    {
        $ojsJob = new OjsJob(id: 'j-500', type: 'email.send', args: [], queue: 'default', state: 'active');
        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs', 'default');

        $this->assertSame('email.send', $job->getName());
    }

    public function testQueueJobGetConnectionName(): void
    {
        $ojsJob = new OjsJob(id: 'j-600', type: 'test', args: [], queue: 'default', state: 'active');
        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs-primary', 'default');

        $this->assertSame('ojs-primary', $job->getConnectionName());
    }

    public function testQueueJobGetOjsJob(): void
    {
        $ojsJob = new OjsJob(id: 'j-700', type: 'test', args: [], queue: 'default', state: 'active');
        $container = new \Illuminate\Container\Container();
        $client = $this->createMock(Client::class);
        $job = new OjsQueueJob($container, $client, $ojsJob, 'ojs', 'default');

        $this->assertSame($ojsJob, $job->getOjsJob());
    }
}
