<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Support\Round;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RoundTest extends TestCase
{
    public function testItLeavesNullAlone(): void
    {
        self::assertNull(Round::money(null));
    }

    /**
     * The case the whole helper exists for. PHP's round() treats 15.995 as an
     * exact midpoint and lifts it to 16.0, even though the nearest double is
     * really 15.99499999999999957. Formatting rounds the actual double.
     */
    public function testItRoundsTheStoredDoubleRatherThanTheTypedDecimal(): void
    {
        self::assertSame(16.0, round(15.995, 2));
        self::assertSame(15.99, Round::money(15.995));
    }

    #[DataProvider('values')]
    public function testItRoundsToTwoDecimals(int|float $value, float $expected): void
    {
        self::assertSame($expected, Round::money($value));
    }

    /**
     * @return iterable<string, array{int|float, float}>
     */
    public static function values(): iterable
    {
        yield 'integer becomes a float' => [42, 42.0];
        yield 'already short' => [1.5, 1.5];
        yield 'rounds down' => [1.234, 1.23];
        yield 'rounds up' => [1.236, 1.24];
        yield 'negative' => [-1.236, -1.24];
        yield 'zero' => [0, 0.0];
    }
}
