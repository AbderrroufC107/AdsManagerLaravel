<?php
namespace App\Services\Meta;

class Currency {
    private static array $multipliers = [
        'DZD' => 1, 'USD' => 100, 'EUR' => 100, 'GBP' => 100, 'CAD' => 100,
        'AUD' => 100, 'JPY' => 1, 'KRW' => 1, 'CLP' => 1, 'VND' => 1,
        'BIF' => 1, 'RWF' => 1, 'UGX' => 1, 'TZS' => 1, 'MWK' => 1,
    ];

    public static function getMultiplier(string $currency): int {
        return self::$multipliers[strtoupper($currency)] ?? 100;
    }

    public static function fromMeta(float $amount, string $currency): float {
        return $amount / self::getMultiplier($currency);
    }

    public static function toMeta(float $amount, string $currency): int {
        return (int) round($amount * self::getMultiplier($currency));
    }
}
