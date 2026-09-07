<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Event as OjsEvent;
use OpenJobSpec\Symfony\EventDispatcher\OjsEventListener;
use OpenJobSpec\Symfony\EventDispatcher\OjsJobEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OjsEventListenerTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsEventListener::class));
    }

    public function testConstructorParameters(): void
    {
        $ref = new \ReflectionClass(OjsEventListener::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);

        $params = $constructor->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('dispatcher', $params[0]->getName());
        $this->assertSame('baseUrl', $params[1]->getName());
        $this->assertSame('authToken', $params[2]->getName());
        $this->assertTrue($params[2]->allowsNull());
    }

    public function testHasSubscriptionMethods(): void
    {
        $ref = new \ReflectionClass(OjsEventListener::class);
        $this->assertTrue($ref->hasMethod('subscribeToJob'));
        $this->assertTrue($ref->hasMethod('subscribeToQueue'));
        $this->assertTrue($ref->hasMethod('subscribeToAll'));
        $this->assertTrue($ref->hasMethod('dispatchEvent'));
    }

    public function testDispatchEventBridgesToSymfony(): void
    {
        $dispatchedEvents = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(function ($event, $name) use (&$dispatchedEvents) {
                $dispatchedEvents[] = ['event' => $event, 'name' => $name];
                return $event;
            });

        $listener = new OjsEventListener($dispatcher, 'http://localhost:8080');

        $ojsEvent = new OjsEvent(
            type: OjsEvent::JOB_COMPLETED,
            data: ['job_id' => 'test-123'],
        );

        $listener->dispatchEvent($ojsEvent);

        $this->assertCount(1, $dispatchedEvents);
        $this->assertSame('ojs.job.completed', $dispatchedEvents[0]['name']);
        $this->assertInstanceOf(OjsJobEvent::class, $dispatchedEvents[0]['event']);
        $this->assertSame('test-123', $dispatchedEvents[0]['event']->getJobId());
    }

    public function testDispatchMultipleEvents(): void
    {
        $dispatchedNames = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(function ($event, $name) use (&$dispatchedNames) {
                $dispatchedNames[] = $name;
                return $event;
            });

        $listener = new OjsEventListener($dispatcher, 'http://localhost:8080', 'test-token');

        $listener->dispatchEvent(new OjsEvent(type: OjsEvent::JOB_ENQUEUED, data: []));
        $listener->dispatchEvent(new OjsEvent(type: OjsEvent::JOB_STARTED, data: []));
        $listener->dispatchEvent(new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: []));

        $this->assertSame([
            'ojs.job.enqueued',
            'ojs.job.started',
            'ojs.job.completed',
        ], $dispatchedNames);
    }
}
