<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use OpenJobSpec\Event as OjsEvent;

/**
 * Laravel event wrapping an OJS lifecycle event.
 *
 * Listeners can type-hint this in their handle() method:
 *
 *   public function handle(OjsJobEvent $event): void
 *   {
 *       if ($event->isType(OjsEvent::JOB_COMPLETED)) {
 *           // ...
 *       }
 *   }
 */
class OjsJobEvent
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(
        public readonly OjsEvent $event,
    ) {}

    /**
     * Check whether this event matches the given OJS event type constant.
     */
    public function isType(string $type): bool
    {
        return $this->event->type === $type;
    }

    /**
     * Get the event data payload.
     */
    public function getData(): array
    {
        return $this->event->data;
    }

    /**
     * Get the job ID from the event data, if present.
     */
    public function getJobId(): ?string
    {
        return $this->event->data['job_id'] ?? $this->event->subject;
    }

    /**
     * Get the OJS event type (e.g., 'job.completed').
     */
    public function getEventType(): string
    {
        return $this->event->type;
    }
}
