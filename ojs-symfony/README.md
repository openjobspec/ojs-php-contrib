# OJS Symfony

Symfony 7+ bundle for [Open Job Spec](https://openjobspec.org).

## Installation

```bash
composer require openjobspec/symfony
```

The bundle is auto-discovered if you use Symfony Flex.

## Configuration

```yaml
# config/packages/ojs.yaml
ojs:
    url: '%env(OJS_URL)%'          # default: http://localhost:8080
    auth_token: '%env(OJS_AUTH_TOKEN)%'
    default_queue: default
    timeout: 30
    worker:
        queues: [default]
        concurrency: 10
        poll_interval: 2.0
        heartbeat_interval: 15.0
        shutdown_timeout: 25.0
```

## Usage

### Inject the Client

```php
use OpenJobSpec\Client;

class OrderService
{
    public function __construct(private readonly Client $ojs) {}

    public function placeOrder(int $orderId): void
    {
        // Business logic...
        $this->ojs->enqueue('order.confirm', [$orderId], [
            'queue' => 'orders',
            'priority' => 10,
        ]);
    }
}
```

### Transactional Enqueue (Doctrine)

```php
use OpenJobSpec\Client;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private readonly Client $ojs,
        private readonly EntityManagerInterface $em,
    ) {}

    public function register(string $email): void
    {
        $user = new User($email);
        $this->em->persist($user);

        // Enqueue after Doctrine flush succeeds
        $this->em->getConnection()->executeStatement(
            'SELECT 1'  // Ensure we're in a transaction
        );

        // Use Doctrine postFlush event or manual approach
        $this->ojs->enqueue('welcome.email', [$email]);
        $this->em->flush();
    }
}
```

### Console Commands

```bash
# Start the worker
php bin/console ojs:work --queues=default,orders --concurrency=20

# Check backend status
php bin/console ojs:status --queue=default

# Purge dead letter queue
php bin/console ojs:purge --queue=default --force
```

## Requirements

- PHP 8.2+
- Symfony 7+

## License

Apache-2.0

