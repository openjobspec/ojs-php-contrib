# OJS PHP Contrib

Framework integrations for the [OJS PHP SDK](https://github.com/openjobspec/ojs-php-sdk).

## Packages

| Package | Framework | Status | Install |
|---------|-----------|--------|---------|
| [ojs-laravel](./ojs-laravel/) | Laravel 11+ | Beta | `composer require openjobspec/laravel` |
| [ojs-symfony](./ojs-symfony/) | Symfony 7+ | Beta | `composer require openjobspec/symfony` |

> **Status: Beta** — Core functionality (enqueue, worker, transactional enqueue) is stable and tested.
> Advanced features (middleware composition, encryption) may change in future releases.

## Overview

Each package provides idiomatic integration between the OJS PHP SDK and a popular PHP framework:

- **Laravel**: Service provider, Facade, `DB::afterCommit()` transactional enqueue, Artisan worker command, config publishing, queue connection driver
- **Symfony**: Bundle, DI container configuration, Doctrine `postFlush` transactional enqueue, Console worker command, Messenger transport bridge

## Quick Start

### Laravel

```bash
composer require openjobspec/laravel
php artisan vendor:publish --tag=ojs-config
```

```php
use OpenJobSpec\Laravel\Facades\Ojs;

// Enqueue a job
$job = Ojs::enqueue('email.send', ['user@example.com', 'Welcome!']);

// Transactional enqueue (enqueues only after DB commit)
DB::transaction(function () {
    $user = User::create(['email' => 'user@example.com']);
    Ojs::enqueueAfterCommit('welcome.email', [$user->id]);
});

// Start worker
// php artisan ojs:work --queues=default,emails
```

### Symfony

```bash
composer require openjobspec/symfony
```

```php
use OpenJobSpec\Client;

class OrderService
{
    public function __construct(private readonly Client $ojs) {}

    public function placeOrder(int $orderId): void
    {
        $this->ojs->enqueue('order.confirm', [$orderId], ['queue' => 'orders']);
    }
}

// Start worker: php bin/console ojs:work --queues=default,orders
```

## Requirements

- PHP 8.2+
- [openjobspec/sdk](https://packagist.org/packages/openjobspec/sdk) ^1.0

## License

Apache-2.0
