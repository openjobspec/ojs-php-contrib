<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Events\OjsJobEvent;
use OpenJobSpec\Event as OjsEvent;
use PHPUnit\Framework\TestCase;

class OjsJobEventTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsJobEvent::class));
    }

    public function testWrapsOjsEvent(): void
    {
        $ojsEvent = new OjsEvent(
            type: OjsEvent::JOB_COMPLETED,
            data: ['job_id' => 'j-123', 'result' => 'ok'],
        );

        $event = new OjsJobEvent($ojsEvent);
        $this->assertSame($ojsEvent, $event->event);
    }

    public function testIsTypeMatches(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: []);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertTrue($event->isType(OjsEvent::JOB_COMPLETED));
        $this->assertFalse($event->isType(OjsEvent::JOB_FAILED));
    }

    public function testGetData(): void
    {
        $data = ['job_id' => 'j-123', 'result' => 'ok'];
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: $data);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame($data, $event->getData());
    }

    public function testGetJobIdFromData(): void
    {
        $ojsEvent = new OjsEvent(
            type: OjsEvent::JOB_COMPLETED,
            data: ['job_id' => 'j-123'],
        );
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame('j-123', $event->getJobId());
    }

    public function testGetJobIdFromSubject(): void
    {
        $ojsEvent = new OjsEvent(
            type: OjsEvent::JOB_COMPLETED,
            data: [],
            subject: 'j-456',
        );
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame('j-456', $event->getJobId());
    }

    public function testGetJobIdReturnsNullWhenAbsent(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_COMPLETED, data: []);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertNull($event->getJobId());
    }

    public function testGetEventType(): void
    {
        $ojsEvent = new OjsEvent(type: OjsEvent::JOB_FAILED, data: []);
        $event = new OjsJobEvent($ojsEvent);

        $this->assertSame(OjsEvent::JOB_FAILED, $event->getEventType());
    }

    public function testIsTypeWithAllEventConstants(): void
    {
        $types = [
            OjsEvent::JOB_ENQUEUED,
            OjsEvent::JOB_STARTED,
            OjsEvent::JOB_COMPLETED,
            OjsEvent::JOB_FAILED,
            OjsEvent::JOB_RETRYING,
            OjsEvent::JOB_CANCELLED,
            OjsEvent::WORKFLOW_STARTED,
            OjsEvent::WORKFLOW_COMPLETED,
        ];

        foreach ($types as $type) {
            $ojsEvent = new OjsEvent(type: $type, data: []);
            $event = new OjsJobEvent($ojsEvent);
            $this->assertTrue($event->isType($type), "isType() should match for {$type}");
        }
    }
}
