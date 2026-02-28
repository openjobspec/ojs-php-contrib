<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Attributes\OjsJob;
use PHPUnit\Framework\TestCase;

class OjsJobAttributeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $attr = new OjsJob(type: 'email.send');

        $this->assertSame('email.send', $attr->type);
        $this->assertSame('default', $attr->queue);
        $this->assertNull($attr->priority);
        $this->assertNull($attr->timeout);
    }

    public function testCustomValues(): void
    {
        $attr = new OjsJob(
            type: 'video.transcode',
            queue: 'media',
            priority: 5,
            timeout: 300,
        );

        $this->assertSame('video.transcode', $attr->type);
        $this->assertSame('media', $attr->queue);
        $this->assertSame(5, $attr->priority);
        $this->assertSame(300, $attr->timeout);
    }

    public function testAttributeIsReadable(): void
    {
        $ref = new \ReflectionClass(OjsJob::class);
        $attributes = $ref->getAttributes(\Attribute::class);

        $this->assertNotEmpty($attributes);
    }

    public function testAttributeTargetsClass(): void
    {
        $ref = new \ReflectionClass(OjsJob::class);
        $attrs = $ref->getAttributes(\Attribute::class);

        $this->assertCount(1, $attrs);
        $instance = $attrs[0]->newInstance();
        $this->assertSame(\Attribute::TARGET_CLASS, $instance->flags);
    }
}
