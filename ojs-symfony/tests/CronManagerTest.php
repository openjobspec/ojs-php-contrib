<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\Client;
use OpenJobSpec\CronJob;
use OpenJobSpec\Symfony\Scheduling\CronManager;
use OpenJobSpec\Testing\FakeTransport;
use PHPUnit\Framework\TestCase;

class CronManagerTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(CronManager::class));
    }

    public function testConstructorRequiresClient(): void
    {
        $ref = new \ReflectionClass(CronManager::class);
        $constructor = $ref->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(1, $constructor->getParameters());
        $this->assertSame(Client::class, $constructor->getParameters()[0]->getType()->getName());
    }

    public function testHasRequiredMethods(): void
    {
        $ref = new \ReflectionClass(CronManager::class);
        $this->assertTrue($ref->hasMethod('register'));
        $this->assertTrue($ref->hasMethod('unregister'));
        $this->assertTrue($ref->hasMethod('list'));
        $this->assertTrue($ref->hasMethod('sync'));
    }

    public function testRegisterCronJob(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $manager = new CronManager($client);

        $cron = new CronJob(
            name: 'cleanup',
            cron: '0 2 * * *',
            type: 'db.cleanup',
            queue: 'maintenance',
        );

        $result = $manager->register($cron);
        $this->assertSame('cleanup', $result['name']);
        $this->assertSame('0 2 * * *', $result['cron']);
    }

    public function testListCronJobs(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $manager = new CronManager($client);

        $manager->register(new CronJob(name: 'job-a', cron: '*/5 * * * *', type: 'type_a'));
        $manager->register(new CronJob(name: 'job-b', cron: '0 * * * *', type: 'type_b'));

        $list = $manager->list();
        $this->assertCount(2, $list);
        $names = array_map(fn(CronJob $c) => $c->name, $list);
        $this->assertContains('job-a', $names);
        $this->assertContains('job-b', $names);
    }

    public function testUnregisterCronJob(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $manager = new CronManager($client);

        $manager->register(new CronJob(name: 'temp-job', cron: '*/10 * * * *', type: 'temp'));
        $manager->unregister('temp-job');

        $list = $manager->list();
        $names = array_map(fn(CronJob $c) => $c->name, $list);
        $this->assertNotContains('temp-job', $names);
    }

    public function testSyncRegistersNewAndRemovesStale(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $manager = new CronManager($client);

        // Pre-register a cron job that should be removed
        $manager->register(new CronJob(name: 'stale-job', cron: '0 0 * * *', type: 'stale'));

        $desired = [
            new CronJob(name: 'new-job', cron: '*/5 * * * *', type: 'fresh'),
        ];

        $result = $manager->sync($desired);

        $this->assertContains('new-job', $result['registered']);
        $this->assertContains('stale-job', $result['unregistered']);
    }

    public function testSyncSkipsUnchangedCrons(): void
    {
        $transport = new FakeTransport();
        $client = new Client('http://fake', ['transport' => $transport]);
        $manager = new CronManager($client);

        $cron = new CronJob(name: 'stable', cron: '*/5 * * * *', type: 'stable_type');
        $manager->register($cron);

        // Sync with the same cron — should not re-register
        $result = $manager->sync([$cron]);

        $this->assertNotContains('stable', $result['registered']);
        $this->assertEmpty($result['unregistered']);
    }
}
