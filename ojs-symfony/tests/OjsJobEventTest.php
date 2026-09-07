<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Event as OjsEvent;
use OpenJobSpec\Symfony\EventDispatcher\OjsJobEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\Event;

class OjsJobEventTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsJobEvent::class));
    }

    public function testExtendsSymfonyEvent(): void
    {
        $ref = new \ReflectionClass(OjsJobEvent::class);
        $this->assertTrue($ref->isSubclassOf(Event::class));
    }

    public function testGetOjsEvent(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: ['job_id' => 'j123']);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame($ojsEvent, $event->getOjsEvent());
    }

    public function testGetEventType(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_ENQUEUED, data: []);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame(OjsEvent::JOB_ENQUEUED, $event->getEventType());
    }

    public function testGetData(): void
    {
        $data = ['job_id' => 'abc', 'type' => 'email.send'];
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_STARTED, data: $data);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame($data, $event->getData());
    }

    public function testGetJobIdFromData(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: ['job_id' => 'j-456']);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame('j-456', $event->getJobId());
    }

    public function testGetJobIdFromSubject(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: [], subject: 'j-789');
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame('j-789', $event->getJobId());
    }

    public function testGetJobIdReturnsNullWhenMissing(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::QUEUE_PAUSED, data: []);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertNull($event->getJobId());
    }

    public function testGetSymfonyEventName(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: []);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame('ojs.job.completed', $event->getSymfonyEventName());
    }

    public function testNamePrefix(): void
    {
        $this->assertSame('ojs.', OjsJobEvent::NAME_PREFIX);
    }

    public function testAllEventTypesMapCorrectly(): void
    {
        $types = [
            OjsEvent::JOB_ENQUEUED => 'ojs.job.enqueued',
            OjsEvent::JOB_STARTED => 'ojs.job.started',
            OjsEvent::JOB_FAILED => 'ojs.job.failed',
            OjsEvent::WORKFLOW_COMPLETED => 'ojs.workflow.completed',
            OjsEvent::CRON_TRIGGERED => 'ojs.cron.triggered',
        ];

        foreach ($types as $ojsType => $expectedName) {
            $ojsEvent = new OjsEvent(type: $ojsType, data: []);
            $event = new OjsJobEvent($ojsEvent);
            $this->assertSame($expectedName, $event->getSymfonyEventName(), "Mapping for {$ojsType}");
        }
    }
}
