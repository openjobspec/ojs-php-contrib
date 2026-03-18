<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Scheduling\OjsCronBridge;
use OpenJobSpec\Client;
use OpenJobSpec\CronJob;
use PHPUnit\Framework\TestCase;

class OjsCronBridgeTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(OjsCronBridge::class));
    }

    public function testRegisterDelegatesToClient(): void
    {
        $cron = new CronJob(
            name: 'cleanup',
            cron: '0 3 * * *',
            type: 'maintenance.cleanup',
            args: ['days' => 30],
        );

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('registerCronJob')
            ->with($cron)
            ->willReturn(['name' => 'cleanup', 'status' => 'registered']);

        $bridge = new OjsCronBridge($client);
        $result = $bridge->register($cron);

        $this->assertSame('cleanup', $result['name']);
    }

    public function testUnregisterDelegatesToClient(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('unregisterCronJob')
            ->with('cleanup');

        $bridge = new OjsCronBridge($client);
        $bridge->unregister('cleanup');
    }

    public function testListDelegatesToClient(): void
    {
        $cronJobs = [
            new CronJob(name: 'cleanup', cron: '0 3 * * *', type: 'maintenance.cleanup'),
            new CronJob(name: 'report', cron: '0 8 * * 1', type: 'report.weekly'),
        ];

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('listCronJobs')
            ->willReturn($cronJobs);

        $bridge = new OjsCronBridge($client);
        $result = $bridge->list();

        $this->assertCount(2, $result);
        $this->assertSame('cleanup', $result[0]->name);
        $this->assertSame('report', $result[1]->name);
    }

    public function testSyncRegistersAndUnregisters(): void
    {
        $existingCrons = [
            new CronJob(name: 'keep', cron: '0 * * * *', type: 'keep.type'),
            new CronJob(name: 'remove', cron: '0 * * * *', type: 'remove.type'),
        ];

        $desiredCrons = [
            new CronJob(name: 'keep', cron: '0 * * * *', type: 'keep.type'),
            new CronJob(name: 'add', cron: '30 * * * *', type: 'add.type'),
        ];

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('listCronJobs')
            ->willReturn($existingCrons);

        // Each desired cron gets registered
        $client->expects($this->exactly(2))
            ->method('registerCronJob')
            ->willReturn(['status' => 'ok']);

        // Only 'remove' should be unregistered
        $client->expects($this->once())
            ->method('unregisterCronJob')
            ->with('remove');

        $bridge = new OjsCronBridge($client);
        $result = $bridge->sync($desiredCrons);

        $this->assertSame(['keep', 'add'], $result['registered']);
        $this->assertSame(['remove'], $result['unregistered']);
    }

    public function testSyncWithEmptyDesiredUnregistersAll(): void
    {
        $existingCrons = [
            new CronJob(name: 'old.one', cron: '0 * * * *', type: 'old.type'),
        ];

        $client = $this->createMock(Client::class);
        $client->method('listCronJobs')->willReturn($existingCrons);
        $client->expects($this->once())
            ->method('unregisterCronJob')
            ->with('old.one');

        $bridge = new OjsCronBridge($client);
        $result = $bridge->sync([]);

        $this->assertEmpty($result['registered']);
        $this->assertSame(['old.one'], $result['unregistered']);
    }

    public function testConstructorRequiresClient(): void
    {
        $ref = new \ReflectionClass(OjsCronBridge::class);
        $params = $ref->getConstructor()->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('client', $params[0]->getName());
    }
}
