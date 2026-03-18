<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Queue;

use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Queue\Jobs\Job as BaseJob;
use OpenJobSpec\Client;
use OpenJobSpec\Job as OjsJob;

/**
 * Wraps an OJS job as a Laravel Queue Job.
 *
 * This adapter allows OJS jobs to be processed through Laravel's
 * queue worker infrastructure when needed.
 */
class OjsQueueJob extends BaseJob implements JobContract
{
    public function __construct(
        Container $container,
        private readonly Client $client,
        private readonly OjsJob $ojsJob,
        private readonly string $connectionName,
        private readonly string $queueName,
    ) {
        $this->container = $container;
        $this->queue = $queueName;
    }

    /**
     * Get the job identifier.
     */
    public function getJobId(): string
    {
        return $this->ojsJob->id;
    }

    /**
     * Get the raw body of the job.
     */
    public function getRawBody(): string
    {
        return json_encode([
            'type' => $this->ojsJob->type,
            'args' => $this->ojsJob->args,
            'id' => $this->ojsJob->id,
            'queue' => $this->ojsJob->queue,
            'attempt' => $this->ojsJob->attempt,
            'meta' => $this->ojsJob->meta,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Get the number of times the job has been attempted.
     */
    public function attempts(): int
    {
        return $this->ojsJob->attempt;
    }

    /**
     * Get the name of the queued job class.
     */
    public function getName(): string
    {
        return $this->ojsJob->meta['laravel.job_class'] ?? $this->ojsJob->type;
    }

    /**
     * Get the name of the connection the job belongs to.
     */
    public function getConnectionName(): string
    {
        return $this->connectionName;
    }

    /**
     * Get the name of the queue the job belongs to.
     */
    public function getQueue(): string
    {
        return $this->queueName;
    }

    /**
     * Get the underlying OJS job.
     */
    public function getOjsJob(): OjsJob
    {
        return $this->ojsJob;
    }
}
