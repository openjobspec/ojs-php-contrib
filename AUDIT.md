# AUDIT — `ojs-php-contrib` Clean-Code / SRP Pass

Branch: `refactor/clean-code-srp` · Scope: `ojs-laravel/`, `ojs-symfony/` · Actor-based Single-Responsibility review.
Changes are unstaged working-tree edits only (no stage/commit/push, no sibling repos touched).

## Status

**Partial / production refactoring skipped under Rule 2.**

The audit identified production refactoring opportunities, but dependency installation and PHPUnit are blocked. Rule 2 therefore limits this change set to test-harness repairs and characterization coverage. All production files remain exactly at `HEAD`/`main`; production findings and proposed splits are documented as **Deferred**, not fixed.

## Summary

- **Implemented — Laravel test-harness parse repair:** `ojs-laravel/tests/LaravelKeyProviderTest.php` mixed unbracketed and bracketed namespace declarations, which is a hard PHP parse error. The file now uses compatible bracketed namespaces.
- **Implemented — Laravel test correction:** the known-key assertion now requests key ID `'test'`, matching the key registered by the test, rather than requesting `'default'`.
- **Implemented — Symfony characterization coverage:** `ojs-symfony/tests/OjsExtensionTest.php` now characterizes simultaneous registration of all opt-in service groups while confirming always-on services remain registered.
- **Deferred — production cleanups:** the proposed SRP splits and mechanical cleanups in `WorkflowBuilder`, `OjsExtension`, `OjsTransport`, and `WorkflowFactory` were restored to `HEAD`/`main` because PHPUnit cannot run.
- **Validation boundary:** `php -l` passes for every PHP file and both Composer manifests validate. PHPUnit and dependency-backed analysis were not run and are not claimed green.

## Dependency triage (blocker of record)

| Module | `composer install` result | Root cause | Repo-owned? | Action |
|--------|---------------------------|------------|-------------|--------|
| `ojs-symfony` | **Blocked** | `openjobspec/sdk` is not published to the declared Composer registry, so the required package cannot be resolved. | No | None under Rule 2; do not repoint the adapter to a sibling checkout. |
| `ojs-laravel` | **Blocked** | The same unpublished `openjobspec/sdk` dependency blocks resolution; additionally, the Laravel 11 development dependency chain is blocked by Composer security advisories. | No | None under Rule 2; do not ignore advisories or change framework support solely to make the audit runnable. |

No dependency manifests or lock files were changed. The install blockers were not bypassed, so PHPUnit could not be executed.

## Findings

Severity: P0 = breaks a gate / correctness · P1 = high-leverage SRP · P2 = useful clean-up · D = deferred product decision.
Cost = implementation effort · Size = code churn · Risk = chance of behaviour change.

| ID | Location | Category | Severity | Status | Actor(s) / axis | Cost | Size | Risk |
|----|----------|----------|----------|--------|-----------------|------|------|------|
| F1 | `ojs-laravel/tests/LaravelKeyProviderTest.php` | Parse error (mixed namespace declaration styles) | **P0** | **Implemented** | Laravel test harness | Low | Med | Low |
| F2 | `ojs-laravel/tests/LaravelKeyProviderTest.php::testGetKeyReturnsKnownKey` | Wrong key ID in assertion | **P0** | **Implemented** | Laravel encryption tests | Low | Low | Low |
| F3 | `ojs-symfony/src/DependencyInjection/OjsExtension.php::load()` | Long method spanning independent service-registration concerns | **P1** | **Deferred (Rule 2)** | Symfony DI wiring | Med | Med | Low |
| F4 | `ojs-symfony/src/DependencyInjection/OjsExtension.php` | Unused `HttpTransport` import | P2 | **Deferred (Rule 2)** | Symfony DI wiring | Low | Low | Low |
| F5 | `ojs-symfony/src/Workflow/WorkflowFactory.php` | Repeated step normalization | P2 | **Deferred (Rule 2)** | Symfony workflow | Low | Low | Low |
| F6 | `ojs-laravel/src/Workflows/WorkflowBuilder.php` | Repeated `Step` construction | P2 | **Deferred (Rule 2)** | Laravel workflow | Low | Low | Low |
| F7 | `ojs-symfony/src/Messenger/OjsTransport.php` | Unused pending-envelope state and no-effect cleanup | P2 | **Deferred (Rule 2)** | Symfony messenger | Low | Low | Low |
| F8 | `ojs-symfony/src/Messenger/OjsTransport.php::resolveJobType()` | Redundant lowercase normalization | P2 | **Deferred (Rule 2)** | Symfony messenger | Low | Low | Low |
| F9 | `ojs-symfony/tests/OjsExtensionTest.php` | Combined opt-in registration characterization gap | P2 (test) | **Implemented** | Symfony DI test harness | Low | Low | Low |
| D1 | Laravel `OjsQueue::normalizeJobType()` and Symfony `OjsTransport::resolveJobType()` | Job-type wire output may contain a `._` artifact | **D** | **Deferred** | SDK wire format (both actors) | Med | Low | **High** |
| D2 | Laravel/Symfony health checks and status command | Inconsistent health-status semantics | **D** | **Deferred** | SDK/health wire contract | Med | Low | **High** |
| D3 | Laravel/Symfony worker commands | CLI worker options appear parsed but not applied to the DI-built worker | **D** | **Deferred** | Worker CLI (product) | Med | Med | Med |
| D4 | Laravel cron bridge and Symfony cron manager | Divergent cron synchronization semantics | **D** | **Deferred** | Scheduling (product) | Med | Low | Med |

## Implemented sequence

1. **F1** — repaired the Laravel test file's namespace declarations so PHP can parse the harness.
2. **F2** — corrected the known-key assertion to use the registered key ID.
3. **F9** — added characterization coverage for all Symfony opt-in registrations being enabled together.
4. Restored all attempted production refactors to exact `HEAD`/`main` content under Rule 2.

## Proposed production splits (Deferred)

1. **F3 + F4 — `OjsExtension`:** split `load()` into actor-local registration methods for parameters, client, worker, workflow, cron, encryption, events, health, and messenger; remove the unused import only when PHPUnit can validate the wiring.
2. **F5 — `WorkflowFactory`:** extract a private step-normalization helper shared by `chain()`, `group()`, and `batch()`.
3. **F6 — `WorkflowBuilder`:** extract a private `Step` factory shared by `add()` and batch callback setters.
4. **F7 + F8 — `OjsTransport`:** remove unused pending state and redundant lowercase normalization after dependency-backed tests are runnable.

These splits are proposals only. None is present in the working-tree production code.

## Validation performed

- `php -l` over all PHP files in the repository → **passes**.
- `composer validate` in `ojs-laravel/` → **passes**.
- `composer validate` in `ojs-symfony/` → **passes**.
- `git diff --` for all four reviewed production paths → **empty**.
- Dependency manifests, lock files, and sibling repositories → **unchanged**.
- **PHPUnit / dependency-backed static analysis:** **skipped**. Dependency installation is blocked by unpublished `openjobspec/sdk` and the advisory-blocked Laravel dependency chain. No PHPUnit result is claimed.

## Deferred (intentionally not changed)

- **F3–F8 — production SRP and cleanup proposals.** Rule 2 requires runnable dependency-backed tests before production refactoring. The four production files were restored exactly to `HEAD`/`main`.
- **D1 — job-type normalization artifact.** Job `type` is observable wire output; changing it requires a product/wire decision and per-framework tests.
- **D2 — health-status semantics.** The canonical backend health value must be established before changing adapter behavior.
- **D3 — worker CLI options.** Applying per-invocation options may require SDK or worker API changes outside this repository.
- **D4 — cron synchronization divergence.** Reconciliation is a product decision and must preserve framework-specific actor boundaries.

## Out-of-scope

- Publishing or resolving `openjobspec/sdk`, or repointing either adapter to a sibling/local SDK checkout.
- Disabling Composer security blocking, ignoring advisories, or changing supported Laravel/Testbench major versions.
- Changing Composer manifests or lock files to bypass the blocked dependency environment.
- Adding CI, PHPStan/Psalm, a style fixer, or any other new tooling.
- Changes to sibling repositories, SDK APIs, worker/client behavior, public APIs, protocol semantics, routes, commands, or configuration keys.

## Release-readiness follow-up (2026-09-02)

This follow-up supersedes only the dependency-installation and release-tooling
status above; the clean-code findings and product-level deferrals remain
unchanged.

- Added repository-local CI, Dependabot, `CONTRIBUTING.md`,
  `CODE_OF_CONDUCT.md`, and `CHANGELOG.md`.
- Both package manifests now require the coordinated SDK release line (`^0.5`) and
  include source/issue metadata and canonical test scripts.
- Symfony dependencies resolve against the tagged SDK source once its missing
  classmap coverage is restored. Its full suite passes: **131 tests, 287
  assertions**.
- The Symfony Messenger transport factory now implements the supported
  three-argument `TransportFactoryInterface::createTransport` signature.
- Laravel remains blocked because every Testbench 9 / Laravel 11 resolution is
  rejected by Composer's active security advisories. Advisory suppression and
  a Laravel-major support change are product/security decisions and remain
  deferred.
- CI checks out the coordinated SDK source and assigns it the local 0.5.0 path
  repository version for pre-publication tests; the post-release workflow then
  verifies direct Packagist resolution.

### Coordinated 0.5.0 resolution

- Both integrations now require `openjobspec/sdk ^0.5` and have committed
  Composer lockfiles resolved against the local 0.5.0 SDK artifact.
- Laravel's supported floor is now Laravel 12 with Testbench 10. This removes
  the advisory-blocked Laravel 11 dependency set; the locked dependency audit
  is clean.
- Laravel 12 compatibility repairs avoid redeclaring inherited queue-job
  properties and use Testbench's real configuration container for encryption
  tests.
- Laravel passes 126 tests (240 assertions, one upstream deprecation);
  Symfony passes 131 tests (287 assertions).
