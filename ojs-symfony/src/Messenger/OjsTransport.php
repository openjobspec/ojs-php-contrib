<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Messenger;

use OpenJobSpec\Client;
use OpenJobSpec\Job;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Symfony Messenger transport that routes messages through an OJS backend.
 *
 * Maps Messenger send/get/ack/reject operations to OJS enqueue/fetch/ack/nack.
 */
class OjsTransport implements TransportInterface
{
    /** @var Envelope[] Fetched envelopes awaiting ack/reject */
    private array $pending = [];

    public function __construct(
        private readonly Client $client,
        private readonly string $defaultQueue = 'default',
    ) {
    }

    /**
     * @return iterable<Envelope>
     */
    public function get(): iterable
    {
        // Messenger calls get() expecting available messages; not used in
        // OJS's push model. Return empty to signal no messages available.
        return [];
    }

    public function ack(Envelope $envelope): void
    {
        $jobId = $this->extractJobId($envelope);
        if ($jobId !== null) {
            unset($this->pending[$jobId]);
        }
    }

    public function reject(Envelope $envelope): void
    {
        $jobId = $this->extractJobId($envelope);
        if ($jobId !== null) {
            try {
                $this->client->cancel($jobId);
            } catch (\Throwable) {
                // Best-effort rejection
            }
            unset($this->pending[$jobId]);
        }
    }

    public function send(Envelope $envelope): Envelope
    {
        $message = $envelope->getMessage();
        $stamp = $this->extractOjsStamp($envelope);

        $type = $this->resolveJobType($message);
        $args = $this->serializeMessage($message);
        $options = $this->buildOptions($stamp);

        $job = $this->client->enqueue($type, $args, $options);

        return $envelope
            ->with(new TransportMessageIdStamp($job->id))
            ->with(new OjsStamp(
                queue: $job->queue,
                jobId: $job->id,
            ));
    }

    /**
     * Resolve the OJS job type from a Messenger message.
     *
     * Uses the FQCN converted to a dot-notation OJS type.
     */
    private function resolveJobType(object $message): string
    {
        $class = get_class($message);

        // Convert "App\Message\SendEmail" to "app.message.send_email"
        $type = str_replace('\\', '.', $class);
        $type = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $type));
        $type = strtolower($type);
        // Normalize double dots and invalid chars
        $type = preg_replace('/[^a-z0-9_.]/', '_', $type);
        $type = preg_replace('/\.{2,}/', '.', $type);
        $type = trim($type, '.');

        return $type;
    }

    private function serializeMessage(object $message): array
    {
        if (method_exists($message, 'toArray')) {
            return $message->toArray();
        }

        return ['__class' => get_class($message), '__data' => serialize($message)];
    }

    private function buildOptions(?OjsStamp $stamp): array
    {
        $options = ['queue' => $stamp?->getQueue() ?? $this->defaultQueue];

        if ($stamp?->getPriority() !== null) {
            $options['priority'] = $stamp->getPriority();
        }
        if ($stamp?->getTimeout() !== null) {
            $options['timeout'] = $stamp->getTimeout();
        }
        if ($stamp?->getMeta() !== []) {
            $options['meta'] = $stamp?->getMeta() ?? [];
        }
        if ($stamp?->getScheduledAt() !== null) {
            $options['scheduled_at'] = $stamp->getScheduledAt();
        }

        return $options;
    }

    private function extractOjsStamp(Envelope $envelope): ?OjsStamp
    {
        /** @var OjsStamp|null $stamp */
        $stamp = $envelope->last(OjsStamp::class);
        return $stamp;
    }

    private function extractJobId(Envelope $envelope): ?string
    {
        $ojsStamp = $envelope->last(OjsStamp::class);
        if ($ojsStamp instanceof OjsStamp && $ojsStamp->getJobId() !== null) {
            return $ojsStamp->getJobId();
        }

        $idStamp = $envelope->last(TransportMessageIdStamp::class);
        if ($idStamp instanceof TransportMessageIdStamp) {
            return (string) $idStamp->getId();
        }

        return null;
    }
}
