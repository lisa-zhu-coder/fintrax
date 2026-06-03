<?php

namespace App\Support;

class VatCalculator
{
    public const DEFAULT_RATE = 21.0;

    public static function divisor(float $vatRatePercent): float
    {
        return 1 + ($vatRatePercent / 100);
    }

    public static function amountWithoutVat(float $amountWithVat, float $vatRatePercent): float
    {
        $divisor = self::divisor($vatRatePercent);

        return $divisor > 0 ? $amountWithVat / $divisor : $amountWithVat;
    }
}
