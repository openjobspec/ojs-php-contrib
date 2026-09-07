<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Scheduling;

use OpenJobSpec\Client;
use OpenJobSpec\CronJob;

/**
 * Service for managing OJS cron jobs through Symfony.
 *
 * Provides CRUD operations and a sync method to reconcile the backend's
 * cron configuration with a desired set of cron job definitions.
 */
class CronManager
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Register a cron job with the OJS backend.
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
     * Synchronize the backend's cron jobs to match the desired set.
     *
     * Registers missing cron jobs and unregisters stale ones.
     *
     * @param CronJob[] $desired The desired set of cron jobs
     * @return array{registered: string[], unregistered: string[]} Summary of changes
     */
    public function sync(array $desired): array
    {
        $existing = $this->list();
        $existingByName = [];
        foreach ($existing as $cron) {
            $existingByName[$cron->name] = $cron;
        }

        $desiredByName = [];
        foreach ($desired as $cron) {
            $desiredByName[$cron->name] = $cron;
        }

        $registered = [];
        $unregistered = [];

        // Register new or updated cron jobs
        foreach ($desiredByName as $name => $cron) {
            if (!isset($existingByName[$name]) || $this->cronChanged($existingByName[$name], $cron)) {
                $this->register($cron);
                $registered[] = $name;
            }
        }

        // Unregister stale cron jobs
        foreach ($existingByName as $name => $cron) {
            if (!isset($desiredByName[$name])) {
                $this->unregister($name);
                $unregistered[] = $name;
            }
        }

        return ['registered' => $registered, 'unregistered' => $unregistered];
    }

    private function cronChanged(CronJob $existing, CronJob $desired): bool
    {
        return $existing->toArray() !== $desired->toArray();
    }
}
