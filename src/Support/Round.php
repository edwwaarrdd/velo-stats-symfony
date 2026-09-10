<?php

declare(strict_types=1);

namespace App\Support;

final class Round
{
    public const int PRECISION = 2;

    /**
     * This formats rather than calling round(), because round() first nudges
     * the value towards the decimal a human would have typed: it treats
     * 15.995, whose nearest double is really 15.99499999999999957, as an exact
     * midpoint and rounds it up to 16.0. Formatting rounds the actual double,
     * so that value reports as 15.99.
     */
    public static function money(int|float|null $value): ?float
    {
        if (null === $value) {
            return null;
        }

        return (float) sprintf('%.'.self::PRECISION.'F', $value);
    }
}
