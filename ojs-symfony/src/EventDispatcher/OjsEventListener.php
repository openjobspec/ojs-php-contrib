<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\EventDispatcher;

use OpenJobSpec\Event as OjsEvent;
use OpenJobSpec\SSESubscription;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Bridge between OJS SSE lifecycle events and Symfony's EventDispatcher.
 *
 * Subscribes to OJS backend SSE streams and re-dispatches each event
 * as a Symfony OjsJobEvent through the EventDispatcher component.
 */
class OjsEventListener
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly string $baseUrl,
        private readonly ?string $authToken = null,
    ) {
    }

    /**
     * Subscribe to SSE events for a specific job and bridge to Symfony dispatcher.
     */
    public function subscribeToJob(string $jobId): SSESubscription
    {
        return SSESubscription::forJob(
            $this->baseUrl,
            $jobId,
            fn(OjsEvent $event) => $this->dispatchEvent($event),
            $this->authToken,
        );
    }

    /**
     * Subscribe to SSE events for a queue and bridge to Symfony dispatcher.
     */
    public function subscribeToQueue(string $queue): SSESubscription
    {
        return SSESubscription::forQueue(
            $this->baseUrl,
            $queue,
            fn(OjsEvent $event) => $this->dispatchEvent($event),
            $this->authToken,
        );
    }

    /**
     * Subscribe to all SSE events and bridge to Symfony dispatcher.
     */
    public function subscribeToAll(): SSESubscription
    {
        return SSESubscription::forAll(
            $this->baseUrl,
            fn(OjsEvent $event) => $this->dispatchEvent($event),
            $this->authToken,
        );
    }

    /**
     * Dispatch a single OJS event through Symfony's event system.
     *
     * Can be called directly for manual event bridging.
     */
    public function dispatchEvent(OjsEvent $event): void
    {
        $symfonyEvent = new OjsJobEvent($event);
        $this->dispatcher->dispatch($symfonyEvent, $symfonyEvent->getSymfonyEventName());
    }
}
