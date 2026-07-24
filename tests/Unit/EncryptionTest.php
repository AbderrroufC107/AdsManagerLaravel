<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Encryption;

class EncryptionTest extends TestCase {
    public function test_encrypt_decrypt_roundtrip(): void {
        $plaintext = 'my-secret-token-12345';
        $encrypted = Encryption::encrypt($plaintext);
        $this->assertArrayHasKey('data', $encrypted);
        $this->assertArrayHasKey('iv', $encrypted);
        $this->assertArrayHasKey('tag', $encrypted);
        $decrypted = Encryption::decrypt($encrypted);
        $this->assertEquals($plaintext, $decrypted);
    }

    public function test_encrypted_data_is_different_each_time(): void {
        $a = Encryption::encrypt('test');
        $b = Encryption::encrypt('test');
        $this->assertNotEquals($a['data'], $b['data']);
    }

    public function test_decrypt_with_wrong_tag_fails(): void {
        $encrypted = Encryption::encrypt('test');
        $encrypted['tag'] = base64_encode(random_bytes(16));
        $decrypted = Encryption::decrypt($encrypted);
        $this->assertNotEquals('test', $decrypted);
    }
}
