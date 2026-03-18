<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\EventDispatcher;

use OpenJobSpec\Event as OjsEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Symfony event wrapping an OJS lifecycle event.
 *
 * Dispatched through Symfony's EventDispatcher when the OjsEventListener
 * receives SSE events from the OJS backend.
 */
class OjsJobEvent extends Event
{
    public const NAME_PREFIX = 'ojs.';

    public function __construct(
        private readonly OjsEvent $ojsEvent,
    ) {
    }

    /**
     * Get the underlying OJS event.
     */
    public function getOjsEvent(): OjsEvent
    {
        return $this->ojsEvent;
    }

    /**
     * Get the OJS event type (e.g., "job.completed").
     */
    public function getEventType(): string
    {
        return $this->ojsEvent->type;
    }

    /**
     * Get the OJS event data payload.
     */
    public function getData(): array
    {
        return $this->ojsEvent->data;
    }

    /**
     * Get the job ID from the event data, if present.
     */
    public function getJobId(): ?string
    {
        return $this->ojsEvent->data['job_id'] ?? $this->ojsEvent->subject;
    }

    /**
     * Get the Symfony event name for dispatching.
     *
     * Maps OJS event types like "job.completed" to "ojs.job.completed".
     */
    public function getSymfonyEventName(): string
    {
        return self::NAME_PREFIX . $this->ojsEvent->type;
    }
}
