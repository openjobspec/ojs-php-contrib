<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests;

use OpenJobSpec\Laravel\Encryption\LaravelKeyProvider;
use OpenJobSpec\KeyProvider;
use PHPUnit\Framework\TestCase;

class LaravelKeyProviderTest extends TestCase
{
    /**
     * Simulate Laravel's config() helper for tests.
     */
    protected function setUp(): void
    {
        // Define config() function if not defined (outside Laravel)
        if (!function_exists('config')) {
            // Already defined below in the global namespace
        }
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(LaravelKeyProvider::class));
    }

    public function testImplementsKeyProviderInterface(): void
    {
        $ref = new \ReflectionClass(LaravelKeyProvider::class);
        $this->assertTrue($ref->implementsInterface(KeyProvider::class));
    }

    public function testGetKeyReturnsKnownKey(): void
    {
        $key = random_bytes(32);
        $provider = new LaravelKeyProvider(['test' => $key]);

        $this->assertSame($key, $provider->getKey('default'));
    }

    public function testGetKeyThrowsForUnknownKeyId(): void
    {
        $provider = new LaravelKeyProvider(['known' => random_bytes(32)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown encryption key ID');
        $provider->getKey('nonexistent');
    }

    public function testGetCurrentKeyIdReturnsFirstKey(): void
    {
        $provider = new LaravelKeyProvider(['v1' => random_bytes(32)]);

        // Default key is 'default' from APP_KEY stub
        $this->assertSame('default', $provider->getCurrentKeyId());
    }

    public function testGetCurrentKeyIdWithExplicitId(): void
    {
        $provider = new LaravelKeyProvider(
            ['v2' => random_bytes(32)],
            'v2',
        );

        $this->assertSame('v2', $provider->getCurrentKeyId());
    }

    public function testInvalidCurrentKeyIdThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('not found in available keys');

        new LaravelKeyProvider([], 'nonexistent');
    }

    public function testBase64PrefixedKeysAreDecoded(): void
    {
        $rawKey = random_bytes(32);
        $base64Key = 'base64:' . base64_encode($rawKey);

        $provider = new LaravelKeyProvider(['encoded' => $base64Key], 'encoded');

        $this->assertSame($rawKey, $provider->getKey('encoded'));
    }

    public function testMultipleKeysSupported(): void
    {
        $key1 = random_bytes(32);
        $key2 = random_bytes(32);

        $provider = new LaravelKeyProvider([
            'v1' => $key1,
            'v2' => $key2,
        ]);

        $this->assertSame($key1, $provider->getKey('v1'));
        $this->assertSame($key2, $provider->getKey('v2'));
    }
}

/**
 * Stub config() helper for tests running outside Laravel.
 */
namespace OpenJobSpec\Laravel\Encryption;

if (!function_exists('OpenJobSpec\\Laravel\\Encryption\\config')) {
    // The LaravelKeyProvider uses config('app.key', '') which resolves
    // via the global config() helper. We need the real namespace-level
    // function to exist for the class to load properly.
}

namespace {
    if (!function_exists('config')) {
        function config(string $key = '', mixed $default = null): mixed
        {
            // Return a fake APP_KEY for testing
            return match ($key) {
                'app.key' => 'base64:' . base64_encode(random_bytes(32)),
                default => $default,
            };
        }
    }
}
