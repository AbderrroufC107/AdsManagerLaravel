<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\RateLimiter;
use Illuminate\Support\Facades\Cache;

class RateLimiterTest extends TestCase {
    public function test_allows_within_limit(): void {
        Cache::flush();
        $result = RateLimiter::check('test1', 5, 60);
        $this->assertTrue($result['allowed']);
        $this->assertEquals(4, $result['remaining']);
    }

    public function test_blocks_over_limit(): void {
        Cache::flush();
        for ($i = 0; $i < 3; $i++) RateLimiter::check('test2', 3, 60);
        $result = RateLimiter::check('test2', 3, 60);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    public function test_chat_and_approve_are_independent(): void {
        Cache::flush();
        RateLimiter::checkChat('1.2.3.4');
        $approve = RateLimiter::checkApprove('1.2.3.4');
        $this->assertTrue($approve['allowed']);
    }
}
