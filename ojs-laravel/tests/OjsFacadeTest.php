<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Facades\Ojs;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OJS Facade structure.
 * These verify the facade contract without requiring the full Laravel framework.
 */
class OjsFacadeTest extends TestCase
{
    public function testFacadeClassExists(): void
    {
        $this->assertTrue(class_exists(Ojs::class));
    }

    public function testFacadeHasFakeMethod(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $this->assertTrue($ref->hasMethod('fake'));
    }

    public function testFacadeHasEnqueueAfterCommitMethod(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $this->assertTrue($ref->hasMethod('enqueueAfterCommit'));
    }

    public function testFacadeHasAssertEnqueuedMethod(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $this->assertTrue($ref->hasMethod('assertEnqueued'));
    }

    public function testFacadeHasRefuteEnqueuedMethod(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $this->assertTrue($ref->hasMethod('refuteEnqueued'));
    }

    public function testFacadeHasAssertEnqueuedCountMethod(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $this->assertTrue($ref->hasMethod('assertEnqueuedCount'));
    }

    public function testFacadeHasClearFakeMethod(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $this->assertTrue($ref->hasMethod('clearFake'));
    }

    public function testFacadeExtendsFacade(): void
    {
        $ref = new \ReflectionClass(Ojs::class);
        $parent = $ref->getParentClass();
        $this->assertNotFalse($parent);
        $this->assertSame('Illuminate\Support\Facades\Facade', $parent->getName());
    }
}
