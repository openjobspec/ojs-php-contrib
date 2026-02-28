<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Http\OjsMiddleware;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OJS HTTP middleware.
 * These tests validate the middleware behavior without requiring
 * the full Laravel framework.
 */
class OjsMiddlewareTest extends TestCase
{
    public function testMiddlewareClassExists(): void
    {
        $this->assertTrue(class_exists(OjsMiddleware::class));
    }

    public function testConstructorAcceptsClient(): void
    {
        $ref = new \ReflectionClass(OjsMiddleware::class);
        $constructor = $ref->getConstructor();

        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('client', $params[0]->getName());
    }

    public function testHandleMethodExists(): void
    {
        $ref = new \ReflectionClass(OjsMiddleware::class);
        $this->assertTrue($ref->hasMethod('handle'));

        $method = $ref->getMethod('handle');
        $this->assertTrue($method->isPublic());
        $params = $method->getParameters();
        $this->assertCount(2, $params);
        $this->assertSame('request', $params[0]->getName());
        $this->assertSame('next', $params[1]->getName());
    }
}
