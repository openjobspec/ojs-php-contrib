<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Events;

use Illuminate\Contracts\Events\Dispatcher;
use OpenJobSpec\Client;
use OpenJobSpec\Event as OjsEvent;
use OpenJobSpec\SSESubscription;

/**
 * Bridge between OJS Server-Sent Events and Laravel's event dispatcher.
 *
 * Subscribes to OJS lifecycle events via SSE and re-dispatches them as
 * Laravel events (OjsJobEvent) so listeners can react using standard
 * Laravel event handling.
 *
 * Usage:
 *   $subscriber = app(OjsEventSubscriber::class);
 *   $sub = $subscriber->subscribeToJob($jobId);
 *   // ... later
 *   $sub->cancel();
 */
class OjsEventSubscriber
{
    /** @var SSESubscription[] */
    private array $activeSubscriptions = [];

    public function __construct(
        private readonly Dispatcher $events,
        private readonly Client $client,
    ) {}

    /**
     * Subscribe to lifecycle events for a specific job.
     *
     * Events are automatically dispatched as OjsJobEvent instances
     * through Laravel's event system.
     */
    public function subscribeToJob(string $jobId): SSESubscription
    {
        $sub = $this->client->subscribeJob($jobId, $this->handleEvent(...));
        $this->activeSubscriptions[] = $sub;
        return $sub;
    }

    /**
     * Subscribe to events for all jobs in a specific queue.
     */
    public function subscribeToQueue(string $queue): SSESubscription
    {
        $sub = $this->client->subscribeQueue($queue, $this->handleEvent(...));
        $this->activeSubscriptions[] = $sub;
        return $sub;
    }

    /**
     * Subscribe to all events from the OJS backend.
     */
    public function subscribeToAll(): SSESubscription
    {
        $sub = $this->client->subscribeAll($this->handleEvent(...));
        $this->activeSubscriptions[] = $sub;
        return $sub;
    }

    /**
     * Cancel all active subscriptions.
     */
    public function cancelAll(): void
    {
        foreach ($this->activeSubscriptions as $sub) {
            if ($sub->isActive()) {
                $sub->cancel();
            }
        }
        $this->activeSubscriptions = [];
    }

    /**
     * Get the number of active subscriptions.
     */
    public function activeCount(): int
    {
        $this->activeSubscriptions = array_values(
            array_filter($this->activeSubscriptions, fn(SSESubscription $s) => $s->isActive()),
        );
        return count($this->activeSubscriptions);
    }

    /**
     * Handle an incoming OJS event by dispatching a Laravel event.
     */
    private function handleEvent(OjsEvent $event): void
    {
        $this->events->dispatch(new OjsJobEvent($event));
    }
}
