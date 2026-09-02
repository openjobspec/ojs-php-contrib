# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0] - 2026-09-02

### Added

- Baseline CI, package validation, archive checks, and clean consumer smoke tests.
- Dependabot, contributing guidance, and the Contributor Covenant.

### Fixed

- Updated the SDK constraint and README requirement to the current 0.4 release line.
- Updated the Symfony Messenger transport factory to implement the supported interface signature.
- Raised the Laravel compatibility floor to Laravel 12 / Testbench 10 because
  the Laravel 11 development dependency set is blocked by active advisories.

## [0.4.0] - 2026-04-20

### Added

- Laravel and Symfony framework integrations.

[Unreleased]: https://github.com/openjobspec/ojs-php-contrib/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/openjobspec/ojs-php-contrib/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/openjobspec/ojs-php-contrib/releases/tag/v0.4.0
