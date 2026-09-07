<?php

declare(strict_types=1);

namespace OpenJobSpec\Laravel\Encryption;

use OpenJobSpec\KeyProvider;

/**
 * KeyProvider implementation using Laravel's encryption configuration.
 *
 * By default, derives the encryption key from Laravel's APP_KEY (base64-decoded).
 * Additional named keys can be provided for key rotation support.
 *
 * Configuration (config/ojs.php):
 *   'encryption' => [
 *       'enabled' => true,
 *       'keys' => [
 *           'v2' => 'base64:...',
 *       ],
 *   ],
 */
class LaravelKeyProvider implements KeyProvider
{
    /** @var array<string, string> */
    private readonly array $keys;

    private readonly string $currentKeyId;

    /**
     * @param array<string, string> $keys      Named keys (keyId => raw or base64-prefixed key)
     * @param string|null           $currentId Which key to use for new encryptions
     */
    public function __construct(array $keys = [], ?string $currentId = null)
    {
        $resolved = [];

        // Always include Laravel's APP_KEY as the 'default' key
        $appKey = config('app.key', '');
        if (is_string($appKey) && $appKey !== '') {
            $resolved['default'] = self::decodeKey($appKey);
        }

        foreach ($keys as $id => $key) {
            $resolved[$id] = self::decodeKey($key);
        }

        if (empty($resolved)) {
            throw new \RuntimeException(
                'No encryption keys available. Set APP_KEY or configure ojs.encryption.keys.'
            );
        }

        $this->keys = $resolved;
        $this->currentKeyId = $currentId ?? array_key_first($this->keys);

        if (!isset($this->keys[$this->currentKeyId])) {
            throw new \InvalidArgumentException(
                "Current key ID '{$this->currentKeyId}' not found in available keys."
            );
        }
    }

    public function getKey(string $keyId): string
    {
        if (!isset($this->keys[$keyId])) {
            throw new \RuntimeException("Unknown encryption key ID: {$keyId}");
        }
        return $this->keys[$keyId];
    }

    public function getCurrentKeyId(): string
    {
        return $this->currentKeyId;
    }

    /**
     * Decode a key value, stripping the base64: prefix if present.
     */
    private static function decodeKey(string $key): string
    {
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false) {
                throw new \RuntimeException('Failed to base64-decode encryption key.');
            }
            return $decoded;
        }
        return $key;
    }
}
