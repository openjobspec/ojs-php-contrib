# OJS PHP Contrib

Framework integrations for the [OJS PHP SDK](https://github.com/openjobspec/ojs-php-sdk).

## Packages

| Package | Framework | Status | Install |
|---------|-----------|--------|---------|
| [ojs-laravel](./ojs-laravel/) | Laravel 11+ | Alpha | `composer require openjobspec/laravel` |
| [ojs-symfony](./ojs-symfony/) | Symfony 7+ | Alpha | `composer require openjobspec/symfony` |

## Overview

Each package provides idiomatic integration between the OJS PHP SDK and a popular PHP framework:

- **Laravel**: Service provider, Facade, `DB::afterCommit()` transactional enqueue, Artisan worker command, config publishing, queue connection driver
- **Symfony**: Bundle, DI container configuration, Doctrine `postFlush` transactional enqueue, Console worker command, Messenger transport bridge

## Requirements

- PHP 8.2+
- [openjobspec/sdk](https://packagist.org/packages/openjobspec/sdk) ^1.0

## License

Apache-2.0

