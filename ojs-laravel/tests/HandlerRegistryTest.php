<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\HandlerRegistry;
use PHPUnit\Framework\TestCase;

class HandlerRegistryTest extends TestCase
{
    private HandlerRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new HandlerRegistry();
    }

    public function testRegisterAndGet(): void
    {
        $handler = fn() => null;
        $this->registry->register('email.send', $handler);

        $this->assertSame($handler, $this->registry->get('email.send'));
    }

    public function testGetReturnsNullForUnregistered(): void
    {
        $this->assertNull($this->registry->get('unknown.type'));
    }

    public function testHasReturnsTrueForRegistered(): void
    {
        $this->registry->register('email.send', fn() => null);
        $this->assertTrue($this->registry->has('email.send'));
    }

    public function testHasReturnsFalseForUnregistered(): void
    {
        $this->assertFalse($this->registry->has('unknown.type'));
    }

    public function testAllReturnsAllHandlers(): void
    {
        $handler1 = fn() => 'one';
        $handler2 = fn() => 'two';

        $this->registry->register('type.one', $handler1);
        $this->registry->register('type.two', $handler2);

        $all = $this->registry->all();
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('type.one', $all);
        $this->assertArrayHasKey('type.two', $all);
    }

    public function testAllReturnsEmptyForNoHandlers(): void
    {
        $this->assertEmpty($this->registry->all());
    }

    public function testRegisterOverwritesPreviousHandler(): void
    {
        $handler1 = fn() => 'first';
        $handler2 = fn() => 'second';

        $this->registry->register('email.send', $handler1);
        $this->registry->register('email.send', $handler2);

        $this->assertSame($handler2, $this->registry->get('email.send'));
        $this->assertCount(1, $this->registry->all());
    }
}
