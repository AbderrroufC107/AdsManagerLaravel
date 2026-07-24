<?php
namespace App\Services;

use App\Models\User;
use App\Models\Credential;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\Encryption;

class AuthService {
    public static function createUser(string $username, string $password): User {
        return User::create(['username' => $username, 'password' => Hash::make($password)]);
    }

    public static function login(string $username, string $password): ?User {
        $user = User::where('username', $username)->first();
        if ($user && Hash::check($password, $user->password)) return $user;
        return null;
    }

    public static function saveCredentials(string $metaToken, string $metaAccountId, string $anthropicKey): string {
        $data = json_encode(['meta_access_token' => $metaToken, 'meta_account_id' => $metaAccountId, 'anthropic_api_key' => $anthropicKey]);
        $encrypted = Encryption::encrypt($data);
        $cred = Credential::where('label', 'default')->first();
        if ($cred) {
            $cred->update(['encrypted_data' => $encrypted['data'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag']]);
            return $cred->id;
        }
        $cred = Credential::create(['label' => 'default', 'encrypted_data' => $encrypted['data'], 'iv' => $encrypted['iv'], 'tag' => $encrypted['tag']]);
        return $cred->id;
    }

    public static function getCredentials(): ?array {
        $cred = Credential::where('label', 'default')->first();
        if (!$cred) return null;
        try {
            $decrypted = Encryption::decrypt(['data' => $cred->encrypted_data, 'iv' => $cred->iv, 'tag' => $cred->tag]);
            return json_decode($decrypted, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function hasCredentials(): bool {
        return Credential::where('label', 'default')->exists();
    }
}
