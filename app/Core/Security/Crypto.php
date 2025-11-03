<?php
declare(strict_types=1);

namespace App\Core\Security;

use App\Core\Env;
use RuntimeException;

class Crypto
{
    private const CIPHER = 'AES-256-CBC';

    public static function encrypt(string $plainText): string
    {
        $key = self::getKey();
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $cipherText = openssl_encrypt($plainText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        if ($cipherText === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $cipherText);
    }

    public static function decrypt(string $payload): string
    {
        $key = self::getKey();
        $data = base64_decode($payload, true);
        if ($data === false) {
            throw new RuntimeException('Invalid encrypted payload.');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($data, 0, $ivLength);
        $cipherText = substr($data, $ivLength);

        $plain = openssl_decrypt($cipherText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new RuntimeException('Decryption failed.');
        }

        return $plain;
    }

    private static function getKey(): string
    {
        $key = (string) Env::get('AES_ENCRYPTION_KEY');
        if (str_starts_with($key, 'base64:')) {
            $key = (string) base64_decode(substr($key, 7));
        }

        if ($key === '') {
            throw new RuntimeException('Encryption key not configured.');
        }

        return substr(hash('sha256', $key, true), 0, 32);
    }
}
