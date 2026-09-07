<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Scheduling;

use OpenJobSpec\Client;
use OpenJobSpec\CronJob;

/**
 * Bridge between OJS cron jobs and Laravel for cron lifecycle management.
 *
 * Provides a convenient API to register, unregister, list, and sync
 * cron jobs on the OJS backend.
 *
 * Usage:
 *   $bridge = app(OjsCronBridge::class);
 *   $bridge->register(new CronJob(
 *       name: 'cleanup.stale',
 *       cron: '0 3 * * *',
 *       type: 'maintenance.cleanup',
 *       args: ['older_than_days' => 30],
 *   ));
 */
class OjsCronBridge
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Register a cron job on the OJS backend.
     */
    public function register(CronJob $cron): array
    {
        return $this->client->registerCronJob($cron);
    }

    /**
     * Unregister a cron job by name.
     */
    public function unregister(string $name): void
    {
        $this->client->unregisterCronJob($name);
    }

    /**
     * List all registered cron jobs.
     *
     * @return CronJob[]
     */
    public function list(): array
    {
        return $this->client->listCronJobs();
    }

    /**
     * Sync a set of cron jobs with the backend.
     *
     * Registers cron jobs that don't exist yet and unregisters those
     * that are no longer in the provided list.
     *
     * @param CronJob[] $cronJobs Desired cron job definitions
     * @return array{registered: string[], unregistered: string[]} Names of changed cron jobs
     */
    public function sync(array $cronJobs): array
    {
        $existing = $this->list();
        $existingNames = array_map(fn(CronJob $c) => $c->name, $existing);
        $desiredNames = array_map(fn(CronJob $c) => $c->name, $cronJobs);

        $registered = [];
        $unregistered = [];

        // Register new or updated cron jobs
        foreach ($cronJobs as $cron) {
            $this->register($cron);
            $registered[] = $cron->name;
        }

        // Unregister cron jobs no longer in the desired set
        foreach ($existingNames as $name) {
            if (!in_array($name, $desiredNames, true)) {
                $this->unregister($name);
                $unregistered[] = $name;
            }
        }

        return ['registered' => $registered, 'unregistered' => $unregistered];
    }
}
