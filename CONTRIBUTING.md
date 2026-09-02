# Contributing to OJS PHP Contrib

Thank you for your interest in contributing to the Open Job Spec PHP integrations.

## Development Setup

Clone the SDK into the same repository-relative dependency layout used by CI:

```bash
git clone https://github.com/openjobspec/ojs-php-contrib.git
cd ojs-php-contrib
git clone https://github.com/openjobspec/ojs-php-sdk.git .deps/ojs-php-sdk
```

Install and test each package:

```bash
cd ojs-laravel
composer install
composer test

cd ../ojs-symfony
composer install
composer test
```

## Adding an Integration

1. Create an `ojs-{framework}/` directory.
2. Include `composer.json`, `src/`, `tests/`, `phpunit.xml`, and `README.md`.
3. Use the framework's idiomatic dependency-injection and lifecycle patterns.
4. Add the package to the CI matrix and root README.

## Pull Request Process

1. Create a focused feature branch.
2. Add tests for behavior changes.
3. Run PHPUnit and `composer validate --strict` in each affected package.
4. Update `CHANGELOG.md` for user-visible changes.
5. Submit a pull request with a clear description.

## License

By contributing, you agree that your contributions will be licensed under the Apache 2.0 License.
