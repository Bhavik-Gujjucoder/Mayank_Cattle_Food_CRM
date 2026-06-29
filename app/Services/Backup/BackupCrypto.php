<?php

namespace App\Services\Backup;

use RuntimeException;

class BackupCrypto
{
    public function derivePassphraseKey(string $passphrase, string $salt): string
    {
        if (! extension_loaded('sodium')) {
            throw new RuntimeException('The sodium PHP extension is required for backup encryption.');
        }

        if (strlen($salt) !== SODIUM_CRYPTO_PWHASH_SALTBYTES) {
            throw new RuntimeException('Invalid Argon2id salt length.');
        }

        return sodium_crypto_pwhash(
            32,
            $passphrase,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE,
            SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );
    }

    public function buildEncryptionKey(string $passphraseDerivedKey): string
    {
        $serverSecret = (string) config('backup.server_secret');

        if ($serverSecret === '') {
            throw new RuntimeException('BACKUP_SERVER_SECRET is not configured. Run: php artisan backup:init');
        }

        return hash_hkdf(
            'sha256',
            $serverSecret.$passphraseDerivedKey,
            32,
            (string) config('backup.hkdf_info'),
        );
    }

    /**
     * @return array{salt: string, nonce: string, ciphertext: string}
     */
    public function encrypt(string $plaintext, string $passphrase): array
    {
        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $passphraseDerivedKey = $this->derivePassphraseKey($passphrase, $salt);
        $encryptionKey = $this->buildEncryptionKey($passphraseDerivedKey);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $encryptionKey);

        return [
            'salt' => $salt,
            'nonce' => $nonce,
            'ciphertext' => $ciphertext,
        ];
    }

    public function decrypt(string $ciphertext, string $passphrase, string $salt, string $nonce): string
    {
        $passphraseDerivedKey = $this->derivePassphraseKey($passphrase, $salt);
        $encryptionKey = $this->buildEncryptionKey($passphraseDerivedKey);
        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $encryptionKey);

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed. Wrong passphrase, corrupted backup file, or backup was created with different server encryption keys.');
        }

        return $plaintext;
    }

    /**
     * @param  array<string, mixed>  $header
     */
    public function signHeader(array $header): string
    {
        $serverSecret = (string) config('backup.server_secret');

        if ($serverSecret === '') {
            throw new RuntimeException('BACKUP_SERVER_SECRET is not configured. Run: php artisan backup:init');
        }

        return hash_hmac('sha256', $this->canonicalHeaderPayload($header), $serverSecret);
    }

    /**
     * @param  array<string, mixed>  $header
     */
    public function verifyHeader(array $header): void
    {
        if (! isset($header['hmac']) || ! is_string($header['hmac'])) {
            throw new RuntimeException('Backup header is missing HMAC signature.');
        }

        $provided = $header['hmac'];
        $expected = $this->signHeader($header);

        if (! hash_equals($expected, $provided)) {
            throw new RuntimeException('Backup header signature is invalid. File may be tampered with.');
        }
    }

    /**
     * @param  array<string, mixed>  $header
     */
    private function canonicalHeaderPayload(array $header): string
    {
        $payload = $header;
        unset($payload['hmac']);

        ksort($payload);

        return json_encode($payload, JSON_THROW_ON_ERROR);
    }
}
