<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Events\OjsEventSubscriber;
use OpenJobSpec\Laravel\Events\OjsJobEvent;
use OpenJobSpec\Client;
use OpenJobSpec\Event as OjsEvent;
use OpenJobSpec\SSESubscription;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class OjsEventSubscriberTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsEventSubscriber::class));
    }

    public function testSubscribeToJobDelegatesToClient(): void
    {
        $mockSub = $this->createMock(SSESubscription::class);
        $mockSub->method('isActive')->willReturn(true);

        $dispatcher = $this->createMock(Dispatcher::class);
        $client = $this->createMock(Client::class);

        $client->expects($this->once())
            ->method('subscribeJob')
            ->with('j-123', $this->isType('callable'))
            ->willReturn($mockSub);

        $subscriber = new OjsEventSubscriber($dispatcher, $client);
        $result = $subscriber->subscribeToJob('j-123');

        $this->assertSame($mockSub, $result);
    }

    public function testSubscribeToQueueDelegatesToClient(): void
    {
        $mockSub = $this->createMock(SSESubscription::class);
        $mockSub->method('isActive')->willReturn(true);

        $dispatcher = $this->createMock(Dispatcher::class);
        $client = $this->createMock(Client::class);

        $client->expects($this->once())
            ->method('subscribeQueue')
            ->with('emails', $this->isType('callable'))
            ->willReturn($mockSub);

        $subscriber = new OjsEventSubscriber($dispatcher, $client);
        $result = $subscriber->subscribeToQueue('emails');

        $this->assertSame($mockSub, $result);
    }

    public function testSubscribeToAllDelegatesToClient(): void
    {
        $mockSub = $this->createMock(SSESubscription::class);
        $mockSub->method('isActive')->willReturn(true);

        $dispatcher = $this->createMock(Dispatcher::class);
        $client = $this->createMock(Client::class);

        $client->expects($this->once())
            ->method('subscribeAll')
            ->with($this->isType('callable'))
            ->willReturn($mockSub);

        $subscriber = new OjsEventSubscriber($dispatcher, $client);
        $result = $subscriber->subscribeToAll();

        $this->assertSame($mockSub, $result);
    }

    public function testActiveCountTracksSubscriptions(): void
    {
        $activeSub = $this->createMock(SSESubscription::class);
        $activeSub->method('isActive')->willReturn(true);

        $inactiveSub = $this->createMock(SSESubscription::class);
        $inactiveSub->method('isActive')->willReturn(false);

        $dispatcher = $this->createMock(Dispatcher::class);
        $client = $this->createMock(Client::class);
        $client->method('subscribeJob')->willReturn($activeSub);
        $client->method('subscribeQueue')->willReturn($inactiveSub);

        $subscriber = new OjsEventSubscriber($dispatcher, $client);
        $subscriber->subscribeToJob('j-1');
        $subscriber->subscribeToQueue('q-1');

        $this->assertSame(1, $subscriber->activeCount());
    }

    public function testCancelAllCancelsActiveSubscriptions(): void
    {
        $sub1 = $this->createMock(SSESubscription::class);
        $sub1->method('isActive')->willReturn(true);
        $sub1->expects($this->once())->method('cancel');

        $sub2 = $this->createMock(SSESubscription::class);
        $sub2->method('isActive')->willReturn(true);
        $sub2->expects($this->once())->method('cancel');

        $dispatcher = $this->createMock(Dispatcher::class);
        $client = $this->createMock(Client::class);
        $client->method('subscribeJob')->willReturn($sub1);
        $client->method('subscribeAll')->willReturn($sub2);

        $subscriber = new OjsEventSubscriber($dispatcher, $client);
        $subscriber->subscribeToJob('j-1');
        $subscriber->subscribeToAll();

        $subscriber->cancelAll();
        $this->assertSame(0, $subscriber->activeCount());
    }

    public function testHasRequiredConstructorParameters(): void
    {
        $ref = new \ReflectionClass(OjsEventSubscriber::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('events', $params[0]->getName());
        $this->assertSame('client', $params[1]->getName());
    }
}
