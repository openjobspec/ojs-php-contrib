<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Health;

use OpenJobSpec\Client;

/**
 * Symfony health check for the OJS backend.
 *
 * Probes the OJS backend's /health endpoint and returns a structured
 * result suitable for Symfony health check integrations and monitoring.
 */
class OjsHealthCheck
{
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Check backend health and return a structured result.
     *
     * @return array{healthy: bool, status: string, details: array, error: ?string}
     */
    public function check(): array
    {
        try {
            $health = $this->client->health();
            $status = $health['status'] ?? 'unknown';

            return [
                'healthy' => $status === 'ok',
                'status' => $status,
                'details' => $health,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'healthy' => false,
                'status' => 'unreachable',
                'details' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Quick boolean health check — true if backend responds with "ok".
     */
    public function isHealthy(): bool
    {
        return $this->check()['healthy'];
    }

    /**
     * Get the backend conformance manifest.
     *
     * @return array{version: string, level: int, ...}
     */
    public function manifest(): array
    {
        return $this->client->manifest();
    }
}
