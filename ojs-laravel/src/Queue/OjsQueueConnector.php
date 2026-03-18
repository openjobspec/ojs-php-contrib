<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Queue;

use Illuminate\Contracts\Queue\Queue as QueueContract;
use Illuminate\Queue\Connectors\ConnectorInterface;
use OpenJobSpec\Client;

/**
 * Laravel Queue connector that registers the 'ojs' queue driver.
 *
 * Allows using OJS as a native Laravel Queue backend:
 *
 *   // config/queue.php
 *   'connections' => [
 *       'ojs' => [
 *           'driver' => 'ojs',
 *       ],
 *   ],
 */
class OjsQueueConnector implements ConnectorInterface
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Establish a queue connection.
     *
     * @param array $config Connection configuration from config/queue.php
     */
    public function connect(array $config): QueueContract
    {
        $defaultQueue = $config['queue'] ?? config('ojs.default_queue', 'default');

        return new OjsQueue($this->client, $defaultQueue);
    }
}
