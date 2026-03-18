<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Tests;

use OpenJobSpec\KeyProvider;
use OpenJobSpec\Symfony\Encryption\SymfonyKeyProvider;
use PHPUnit\Framework\TestCase;

class SymfonyKeyProviderTest extends TestCase
{
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(SymfonyKeyProvider::class));
    }

    public function testImplementsKeyProvider(): void
    {
        $ref = new \ReflectionClass(SymfonyKeyProvider::class);
        $this->assertTrue($ref->implementsInterface(KeyProvider::class));
    }

    public function testGetCurrentKeyId(): void
    {
        $provider = new SymfonyKeyProvider(
            keys: ['key-1' => str_repeat('a', 32)],
            currentKeyId: 'key-1',
        );

        $this->assertSame('key-1', $provider->getCurrentKeyId());
    }

    public function testGetKeyReturnsRawKey(): void
    {
        $rawKey = str_repeat('x', 32);
        $provider = new SymfonyKeyProvider(
            keys: ['primary' => $rawKey],
            currentKeyId: 'primary',
        );

        $this->assertSame($rawKey, $provider->getKey('primary'));
    }

    public function testGetKeyDecodesHexKey(): void
    {
        $hexKey = str_repeat('ab', 32); // 64 hex chars = 32 bytes
        $provider = new SymfonyKeyProvider(
            keys: ['hex-key' => $hexKey],
            currentKeyId: 'hex-key',
        );

        $decoded = $provider->getKey('hex-key');
        $this->assertSame(32, strlen($decoded));
        $this->assertSame(hex2bin($hexKey), $decoded);
    }

    public function testMultipleKeys(): void
    {
        $provider = new SymfonyKeyProvider(
            keys: [
                'v1' => str_repeat('a', 32),
                'v2' => str_repeat('b', 32),
            ],
            currentKeyId: 'v2',
        );

        $this->assertSame('v2', $provider->getCurrentKeyId());
        $this->assertSame(str_repeat('a', 32), $provider->getKey('v1'));
        $this->assertSame(str_repeat('b', 32), $provider->getKey('v2'));
    }

    public function testThrowsOnUnknownKeyId(): void
    {
        $provider = new SymfonyKeyProvider(
            keys: ['existing' => str_repeat('a', 32)],
            currentKeyId: 'existing',
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unknown encryption key ID: missing');
        $provider->getKey('missing');
    }

    public function testThrowsOnInvalidCurrentKeyId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Current encryption key ID 'nonexistent' not found");

        new SymfonyKeyProvider(
            keys: ['valid' => str_repeat('a', 32)],
            currentKeyId: 'nonexistent',
        );
    }
}
