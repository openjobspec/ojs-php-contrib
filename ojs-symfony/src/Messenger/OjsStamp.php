<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * Stamp that attaches OJS metadata to a Symfony Messenger envelope.
 *
 * When a message is sent through the OJS transport, this stamp controls
 * the queue, priority, and other OJS-specific options.
 */
class OjsStamp implements StampInterface
{
    public function __construct(
        private readonly ?string $queue = null,
        private readonly ?int $priority = null,
        private readonly ?int $timeout = null,
        private readonly array $meta = [],
        private readonly ?string $scheduledAt = null,
        private readonly ?string $jobId = null,
    ) {
    }

    public function getQueue(): ?string
    {
        return $this->queue;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getScheduledAt(): ?string
    {
        return $this->scheduledAt;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }
}
