<?php

namespace App\Domain\Core\Money;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final class Decimal
{
    public static function compare(string $left, string $right): int
    {
        return BigDecimal::of($left)->compareTo(BigDecimal::of($right));
    }

    public static function add(string $left, string $right): string
    {
        return (string) BigDecimal::of($left)->plus($right)->toScale(2, RoundingMode::Unnecessary);
    }

    public static function subtract(string $left, string $right): string
    {
        return (string) BigDecimal::of($left)->minus($right)->toScale(2, RoundingMode::Unnecessary);
    }

    public static function percentage(string $amount, string $rate): string
    {
        return (string) BigDecimal::of($amount)
            ->multipliedBy(BigDecimal::of($rate)->dividedBy('100', 4, RoundingMode::Down))
            ->toScale(2, RoundingMode::Down);
    }
}
