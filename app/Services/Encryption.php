<?php
namespace App\Services;

class Encryption {
    private static function getKey(): string {
        $key = env('MASTER_KEY');
        if (!$key || strlen($key) < 64) throw new \Exception('MASTER_KEY must be 64 hex chars');
        return hex2bin($key);
    }

    public static function encrypt(string $plaintext): array {
        $key = self::getKey();
        $iv = random_bytes(16);
        $tag = '';
        $encrypted = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        return ['data' => base64_encode($encrypted), 'iv' => base64_encode($iv), 'tag' => base64_encode($tag)];
    }

    public static function decrypt(array $payload): string {
        $key = self::getKey();
        $iv = base64_decode($payload['iv']);
        $tag = base64_decode($payload['tag']);
        $encrypted = base64_decode($payload['data']);
        return openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }
}
