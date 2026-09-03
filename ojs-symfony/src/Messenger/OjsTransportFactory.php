<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Messenger;

use OpenJobSpec\Client;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * Factory for creating OJS Messenger transports from DSN strings.
 *
 * DSN format: ojs://host:port?queue=myqueue
 * Example:    ojs://localhost:8080?queue=emails
 */
class OjsTransportFactory implements TransportFactoryInterface
{
    public function __construct(
        private readonly ?Client $client = null,
    ) {
    }

    public function createTransport(
        #[\SensitiveParameter] string $dsn,
        array $options,
        SerializerInterface $serializer,
    ): TransportInterface {
        $parsedUrl = parse_url($dsn);
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? 8080;
        $scheme = ($parsedUrl['scheme'] === 'ojss') ? 'https' : 'http';

        parse_str($parsedUrl['query'] ?? '', $queryParams);
        $queue = $queryParams['queue'] ?? $options['queue'] ?? 'default';

        if ($this->client !== null) {
            return new OjsTransport($this->client, $queue);
        }

        $clientOptions = [];
        if (isset($queryParams['auth_token']) || isset($options['auth_token'])) {
            $clientOptions['auth_token'] = $queryParams['auth_token'] ?? $options['auth_token'];
        }
        if (isset($queryParams['timeout']) || isset($options['timeout'])) {
            $clientOptions['timeout'] = (int) ($queryParams['timeout'] ?? $options['timeout']);
        }

        $url = "{$scheme}://{$host}:{$port}";
        $client = new Client($url, $clientOptions);

        return new OjsTransport($client, $queue);
    }

    public function supports(#[\SensitiveParameter] string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'ojs://') || str_starts_with($dsn, 'ojss://');
    }
}
