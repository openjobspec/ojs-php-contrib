<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Console\WorkCommand;
use OpenJobSpec\Laravel\Console\StatusCommand;
use OpenJobSpec\Laravel\Console\PurgeCommand;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OJS Artisan console commands structure.
 */
class ConsoleCommandsTest extends TestCase
{
    public function testWorkCommandExists(): void
    {
        $this->assertTrue(class_exists(WorkCommand::class));
    }

    public function testWorkCommandExtendsCommand(): void
    {
        $ref = new \ReflectionClass(WorkCommand::class);
        $this->assertTrue($ref->isSubclassOf(\Illuminate\Console\Command::class));
    }

    public function testWorkCommandHasSignature(): void
    {
        $ref = new \ReflectionClass(WorkCommand::class);
        $prop = $ref->getProperty('signature');
        $prop->setAccessible(true);
        // Read default value from reflection
        $defaults = $ref->getDefaultProperties();
        $signature = $defaults['signature'];

        $this->assertStringContainsString('ojs:work', $signature);
        $this->assertStringContainsString('--queues', $signature);
        $this->assertStringContainsString('--concurrency', $signature);
    }

    public function testStatusCommandExists(): void
    {
        $this->assertTrue(class_exists(StatusCommand::class));
    }

    public function testStatusCommandExtendsCommand(): void
    {
        $ref = new \ReflectionClass(StatusCommand::class);
        $this->assertTrue($ref->isSubclassOf(\Illuminate\Console\Command::class));
    }

    public function testStatusCommandHasSignature(): void
    {
        $ref = new \ReflectionClass(StatusCommand::class);
        $defaults = $ref->getDefaultProperties();
        $signature = $defaults['signature'];

        $this->assertStringContainsString('ojs:status', $signature);
        $this->assertStringContainsString('--queue', $signature);
    }

    public function testPurgeCommandExists(): void
    {
        $this->assertTrue(class_exists(PurgeCommand::class));
    }

    public function testPurgeCommandExtendsCommand(): void
    {
        $ref = new \ReflectionClass(PurgeCommand::class);
        $this->assertTrue($ref->isSubclassOf(\Illuminate\Console\Command::class));
    }

    public function testPurgeCommandHasSignature(): void
    {
        $ref = new \ReflectionClass(PurgeCommand::class);
        $defaults = $ref->getDefaultProperties();
        $signature = $defaults['signature'];

        $this->assertStringContainsString('ojs:purge', $signature);
        $this->assertStringContainsString('--force', $signature);
        $this->assertStringContainsString('--queue', $signature);
    }

    public function testAllCommandsHaveHandleMethod(): void
    {
        foreach ([WorkCommand::class, StatusCommand::class, PurgeCommand::class] as $class) {
            $ref = new \ReflectionClass($class);
            $this->assertTrue(
                $ref->hasMethod('handle'),
                "{$class} should have a handle() method"
            );

            $method = $ref->getMethod('handle');
            $this->assertTrue($method->isPublic(), "{$class}::handle() should be public");
        }
    }

    public function testAllCommandsHaveDescription(): void
    {
        foreach ([WorkCommand::class, StatusCommand::class, PurgeCommand::class] as $class) {
            $ref = new \ReflectionClass($class);
            $defaults = $ref->getDefaultProperties();
            $this->assertNotEmpty(
                $defaults['description'],
                "{$class} should have a non-empty description"
            );
        }
    }
}
