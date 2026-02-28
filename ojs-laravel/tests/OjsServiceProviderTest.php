<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\OjsServiceProvider;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OJS Laravel Service Provider.
 * These tests validate the provider's structure and registration logic
 * without requiring the full Laravel framework.
 */
class OjsServiceProviderTest extends TestCase
{
    public function testProviderClassExists(): void
    {
        $this->assertTrue(class_exists(OjsServiceProvider::class));
    }

    public function testProviderHasRegisterMethod(): void
    {
        $ref = new \ReflectionClass(OjsServiceProvider::class);
        $this->assertTrue($ref->hasMethod('register'));
    }

    public function testProviderHasBootMethod(): void
    {
        $ref = new \ReflectionClass(OjsServiceProvider::class);
        $this->assertTrue($ref->hasMethod('boot'));
    }

    public function testProviderExtendsServiceProvider(): void
    {
        $ref = new \ReflectionClass(OjsServiceProvider::class);
        $parent = $ref->getParentClass();
        $this->assertNotFalse($parent);
        $this->assertSame('Illuminate\Support\ServiceProvider', $parent->getName());
    }
}
