<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Health;

use OpenJobSpec\Client;

/**
 * Health check for the OJS backend.
 *
 * Integrates with Laravel's health monitoring (Illuminate\Health) to report
 * the status of the OJS backend server. Compatible with Laravel 10+ health
 * check infrastructure.
 *
 * Registration in OjsServiceProvider is automatic when health checks are enabled.
 *
 * Manual usage:
 *   $check = app(OjsHealthCheck::class);
 *   $result = $check->run();
 */
class OjsHealthCheck
{
    public const STATUS_OK = 'ok';
    public const STATUS_WARNING = 'warning';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Run the health check against the OJS backend.
     *
     * @return array{status: string, message: string, meta: array}
     */
    public function run(): array
    {
        try {
            $health = $this->client->health();

            $status = ($health['status'] ?? '') === 'healthy'
                ? self::STATUS_OK
                : self::STATUS_WARNING;

            $message = $status === self::STATUS_OK
                ? 'OJS backend is healthy'
                : 'OJS backend is degraded: ' . ($health['status'] ?? 'unknown');

            return [
                'status' => $status,
                'message' => $message,
                'meta' => $health,
            ];
        } catch (\Throwable $e) {
            return [
                'status' => self::STATUS_FAILED,
                'message' => "OJS backend unreachable: {$e->getMessage()}",
                'meta' => [],
            ];
        }
    }
}
