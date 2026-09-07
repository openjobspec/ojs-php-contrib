<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Queue as BaseQueue;
use OpenJobSpec\Client;

/**
 * Laravel Queue implementation backed by an OJS backend.
 *
 * Translates Laravel Queue::push() / Queue::later() calls into OJS
 * enqueue operations, letting applications use the familiar Laravel Queue
 * API while routing jobs through an OJS-compliant server.
 */
class OjsQueue extends BaseQueue implements QueueContract
{
    public function __construct(
        private readonly Client $client,
        private readonly string $defaultQueue = 'default',
    ) {}

    /**
     * Get the size of the queue.
     */
    public function size($queue = null): int
    {
        try {
            $stats = $this->client->getQueueStats($queue ?? $this->defaultQueue);
            return $stats->available + $stats->active + $stats->scheduled;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job   Job class name or serializable payload
     * @param mixed         $data  Job data
     * @param string|null   $queue Target queue
     * @return string|null  The OJS job ID
     */
    public function push($job, $data = '', $queue = null): ?string
    {
        $payload = $this->buildPayload($job, $data);

        $ojsJob = $this->client->enqueue(
            $payload['type'],
            $payload['args'],
            array_filter([
                'queue' => $queue ?? $this->defaultQueue,
                'meta' => $payload['meta'] ?? null,
            ]),
        );

        return $ojsJob->id;
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param string      $payload Raw JSON payload
     * @param string|null $queue   Target queue
     * @param array       $options Additional options
     */
    public function pushRaw($payload, $queue = null, array $options = []): ?string
    {
        $decoded = json_decode($payload, true) ?? [];

        $ojsJob = $this->client->enqueue(
            $decoded['type'] ?? 'laravel.raw',
            $decoded['args'] ?? [$payload],
            ['queue' => $queue ?? $this->defaultQueue],
        );

        return $ojsJob->id;
    }

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay  Delay in seconds or DateTime
     * @param string|object                        $job    Job class name or payload
     * @param mixed                                $data   Job data
     * @param string|null                          $queue  Target queue
     */
    public function later($delay, $job, $data = '', $queue = null): ?string
    {
        $payload = $this->buildPayload($job, $data);
        $scheduledAt = $this->resolveScheduledAt($delay);

        $ojsJob = $this->client->enqueue(
            $payload['type'],
            $payload['args'],
            array_filter([
                'queue' => $queue ?? $this->defaultQueue,
                'scheduled_at' => $scheduledAt,
                'meta' => $payload['meta'] ?? null,
            ]),
        );

        return $ojsJob->id;
    }

    /**
     * Pop the next job off of the queue.
     *
     * Not supported — OJS uses a push-based worker model via ojs:work.
     * Use the OJS Worker directly instead of Queue::pop().
     *
     * @throws \RuntimeException Always
     */
    public function pop($queue = null): never
    {
        throw new \RuntimeException(
            'OJS does not support pop(). Use the ojs:work artisan command to process jobs.'
        );
    }

    /**
     * Get the default queue name.
     */
    public function getQueue(?string $queue = null): string
    {
        return $queue ?? $this->defaultQueue;
    }

    /**
     * Get the underlying OJS client.
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Build a normalized payload from a Laravel job.
     *
     * @return array{type: string, args: array, meta?: array}
     */
    private function buildPayload(string|object $job, mixed $data): array
    {
        if (is_object($job)) {
            $className = get_class($job);
            return [
                'type' => $this->normalizeJobType($className),
                'args' => [$data !== '' ? $data : serialize($job)],
                'meta' => [
                    'laravel.job_class' => $className,
                    'laravel.queue_driver' => 'ojs',
                ],
            ];
        }

        return [
            'type' => $this->normalizeJobType($job),
            'args' => is_array($data) ? $data : [$data],
            'meta' => [
                'laravel.job_class' => $job,
                'laravel.queue_driver' => 'ojs',
            ],
        ];
    }

    /**
     * Convert a class name to an OJS-compatible job type.
     *
     * App\Jobs\SendEmail → app.jobs.send_email
     */
    private function normalizeJobType(string $className): string
    {
        $type = str_replace('\\', '.', $className);
        $type = strtolower((string) preg_replace('/(?<!^)(?=[A-Z])/', '_', $type));
        $type = (string) preg_replace('/[^a-z0-9._]/', '', $type);
        $type = trim($type, '.');

        return $type !== '' ? $type : 'laravel.job';
    }

    /**
     * Resolve a delay value to an RFC 3339 scheduled_at timestamp.
     */
    private function resolveScheduledAt(\DateTimeInterface|\DateInterval|int $delay): string
    {
        if ($delay instanceof \DateTimeInterface) {
            return $delay->format('c');
        }

        if ($delay instanceof \DateInterval) {
            return (new \DateTimeImmutable())->add($delay)->format('c');
        }

        return (new \DateTimeImmutable())->modify("+{$delay} seconds")->format('c');
    }
}
