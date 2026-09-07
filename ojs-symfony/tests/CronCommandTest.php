<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Symfony\Command\CronCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;

class CronCommandTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(CronCommand::class));
    }

    public function testExtendsCommand(): void
    {
        $ref = new \ReflectionClass(CronCommand::class);
        $this->assertTrue($ref->isSubclassOf(Command::class));
    }

    public function testHasAsCommandAttribute(): void
    {
        $ref = new \ReflectionClass(CronCommand::class);
        $attrs = $ref->getAttributes(AsCommand::class);

        $this->assertCount(1, $attrs);
        $instance = $attrs[0]->newInstance();
        $this->assertSame('ojs:cron', $instance->name);
        $this->assertNotEmpty($instance->description);
    }

    public function testConstructorRequiresCronManager(): void
    {
        $ref = new \ReflectionClass(CronCommand::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $params = $constructor->getParameters();
        $this->assertGreaterThan(0, count($params));
        $this->assertSame('cronManager', $params[0]->getName());
    }

    public function testHasExecuteMethod(): void
    {
        $ref = new \ReflectionClass(CronCommand::class);
        $this->assertTrue($ref->hasMethod('execute'));
    }
}
