<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Tests {

use OpenJobSpec\Laravel\Encryption\LaravelKeyProvider;
use OpenJobSpec\KeyProvider;
use Orchestra\Testbench\TestCase;

class LaravelKeyProviderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
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

        $this->assertSame($key, $provider->getKey('test'));
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

}
