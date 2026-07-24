<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Meta\Currency;

class CurrencyTest extends TestCase {
    public function test_get_multiplier_returns_1_for_dzd(): void {
        $this->assertEquals(1, Currency::getMultiplier('DZD'));
    }

    public function test_get_multiplier_returns_100_for_usd(): void {
        $this->assertEquals(100, Currency::getMultiplier('USD'));
    }

    public function test_get_multiplier_returns_100_for_unknown(): void {
        $this->assertEquals(100, Currency::getMultiplier('XYZ'));
    }

    public function test_from_meta_divides_by_multiplier(): void {
        $this->assertEquals(100.0, Currency::fromMeta(100.0, 'DZD'));
        $this->assertEquals(1.0, Currency::fromMeta(100.0, 'USD'));
    }

    public function test_to_meta_multiplies_by_multiplier(): void {
        $this->assertEquals(100, Currency::toMeta(100.0, 'DZD'));
        $this->assertEquals(10000, Currency::toMeta(100.0, 'USD'));
    }

    public function test_case_insensitive(): void {
        $this->assertEquals(1, Currency::getMultiplier('dzd'));
        $this->assertEquals(100, Currency::getMultiplier('usd'));
    }
}
