<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RateLimiter {
    public static function check(string $key, int $maxRequests = 20, int $windowSeconds = 60): array {
        $cacheKey = "rate_limit:$key";
        $current = Cache::get($cacheKey, 0);
        if ($current >= $maxRequests) return ['allowed' => false, 'remaining' => 0];
        Cache::put($cacheKey, $current + 1, $windowSeconds);
        return ['allowed' => true, 'remaining' => $maxRequests - $current - 1];
    }

    public static function checkChat(string $ip): array { return self::check("chat:$ip", 10, 60); }
    public static function checkApprove(string $ip): array { return self::check("approve:$ip", 30, 60); }
}
