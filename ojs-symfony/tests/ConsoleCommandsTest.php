<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\Command\WorkCommand;
use OpenJobSpec\Symfony\Command\StatusCommand;
use OpenJobSpec\Symfony\Command\PurgeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

class ConsoleCommandsTest extends TestCase
{
    public function testWorkCommandExists(): void
    {
        $this->assertTrue(class_exists(WorkCommand::class));
    }

    public function testWorkCommandExtendsCommand(): void
    {
        $ref = new \ReflectionClass(WorkCommand::class);
        $this->assertTrue($ref->isSubclassOf(Command::class));
    }

    public function testWorkCommandHasAsCommandAttribute(): void
    {
        $ref = new \ReflectionClass(WorkCommand::class);
        $attrs = $ref->getAttributes(\Symfony\Component\Console\Attribute\AsCommand::class);

        $this->assertCount(1, $attrs);
        $instance = $attrs[0]->newInstance();
        $this->assertSame('ojs:work', $instance->name);
        $this->assertNotEmpty($instance->description);
    }

    public function testStatusCommandExists(): void
    {
        $this->assertTrue(class_exists(StatusCommand::class));
    }

    public function testStatusCommandExtendsCommand(): void
    {
        $ref = new \ReflectionClass(StatusCommand::class);
        $this->assertTrue($ref->isSubclassOf(Command::class));
    }

    public function testStatusCommandHasAsCommandAttribute(): void
    {
        $ref = new \ReflectionClass(StatusCommand::class);
        $attrs = $ref->getAttributes(\Symfony\Component\Console\Attribute\AsCommand::class);

        $this->assertCount(1, $attrs);
        $instance = $attrs[0]->newInstance();
        $this->assertSame('ojs:status', $instance->name);
    }

    public function testPurgeCommandExists(): void
    {
        $this->assertTrue(class_exists(PurgeCommand::class));
    }

    public function testPurgeCommandExtendsCommand(): void
    {
        $ref = new \ReflectionClass(PurgeCommand::class);
        $this->assertTrue($ref->isSubclassOf(Command::class));
    }

    public function testPurgeCommandHasAsCommandAttribute(): void
    {
        $ref = new \ReflectionClass(PurgeCommand::class);
        $attrs = $ref->getAttributes(\Symfony\Component\Console\Attribute\AsCommand::class);

        $this->assertCount(1, $attrs);
        $instance = $attrs[0]->newInstance();
        $this->assertSame('ojs:purge', $instance->name);
    }

    public function testAllCommandsHaveExecuteMethod(): void
    {
        foreach ([WorkCommand::class, StatusCommand::class, PurgeCommand::class] as $class) {
            $ref = new \ReflectionClass($class);
            $this->assertTrue(
                $ref->hasMethod('execute'),
                "{$class} should have an execute() method"
            );
        }
    }

    public function testAllCommandsRequireConstructorDependencies(): void
    {
        foreach ([WorkCommand::class, StatusCommand::class, PurgeCommand::class] as $class) {
            $ref = new \ReflectionClass($class);
            $constructor = $ref->getConstructor();
            $this->assertNotNull($constructor, "{$class} should have a constructor");
            $this->assertGreaterThan(0, $constructor->getNumberOfParameters(), "{$class} constructor should require dependencies");
        }
    }
}
