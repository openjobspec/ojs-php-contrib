<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\Messenger\OjsStamp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Stamp\StampInterface;

class OjsStampTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsStamp::class));
    }

    public function testImplementsStampInterface(): void
    {
        $ref = new \ReflectionClass(OjsStamp::class);
        $this->assertTrue($ref->implementsInterface(StampInterface::class));
    }

    public function testDefaultValues(): void
    {
        $stamp = new OjsStamp();

        $this->assertNull($stamp->getQueue());
        $this->assertNull($stamp->getPriority());
        $this->assertNull($stamp->getTimeout());
        $this->assertSame([], $stamp->getMeta());
        $this->assertNull($stamp->getScheduledAt());
        $this->assertNull($stamp->getJobId());
    }

    public function testCustomValues(): void
    {
        $stamp = new OjsStamp(
            queue: 'emails',
            priority: 5,
            timeout: 120,
            meta: ['tenant' => 'acme'],
            scheduledAt: '2025-01-01T00:00:00Z',
            jobId: 'job-123',
        );

        $this->assertSame('emails', $stamp->getQueue());
        $this->assertSame(5, $stamp->getPriority());
        $this->assertSame(120, $stamp->getTimeout());
        $this->assertSame(['tenant' => 'acme'], $stamp->getMeta());
        $this->assertSame('2025-01-01T00:00:00Z', $stamp->getScheduledAt());
        $this->assertSame('job-123', $stamp->getJobId());
    }

    public function testPartialValues(): void
    {
        $stamp = new OjsStamp(queue: 'high-priority');

        $this->assertSame('high-priority', $stamp->getQueue());
        $this->assertNull($stamp->getPriority());
        $this->assertNull($stamp->getTimeout());
    }
}
