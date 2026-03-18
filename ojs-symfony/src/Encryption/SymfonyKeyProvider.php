<?php

declare(strict_types=1);

namespace OpenJobSpec\Symfony\Encryption;

use OpenJobSpec\KeyProvider;

/**
 * KeyProvider backed by Symfony configuration or secrets.
 *
 * Reads encryption keys from the bundle config (ojs.encryption.keys),
 * which can reference Symfony secrets via %env()% syntax.
 */
class SymfonyKeyProvider implements KeyProvider
{
    /**
     * @param array<string, string> $keys         Map of keyId => hex-encoded or raw key
     * @param string                $currentKeyId The active key ID for new encryptions
     */
    public function __construct(
        private readonly array $keys,
        private readonly string $currentKeyId,
    ) {
        if (!isset($this->keys[$this->currentKeyId])) {
            throw new \InvalidArgumentException(
                "Current encryption key ID '{$this->currentKeyId}' not found in configured keys.",
            );
        }
    }

    public function getKey(string $keyId): string
    {
        if (!isset($this->keys[$keyId])) {
            throw new \RuntimeException("Unknown encryption key ID: {$keyId}");
        }

        return $this->decodeKey($this->keys[$keyId]);
    }

    public function getCurrentKeyId(): string
    {
        return $this->currentKeyId;
    }

    /**
     * Decode a key value — supports hex-encoded and raw binary keys.
     */
    private function decodeKey(string $value): string
    {
        // If the key looks hex-encoded (64 hex chars = 32 bytes), decode it
        if (preg_match('/^[0-9a-fA-F]{64}$/', $value)) {
            return hex2bin($value);
        }

        return $value;
    }
}
