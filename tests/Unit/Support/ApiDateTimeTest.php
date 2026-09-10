<?php

declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Support\ApiDateTime;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

final class ApiDateTimeTest extends TestCase
{
    public function testItLeavesNullAlone(): void
    {
        self::assertNull(ApiDateTime::datetime(null));
        self::assertNull(ApiDateTime::date(null));
    }

    public function testItFormatsAsUtcWithASecondsPrecisionZuluSuffix(): void
    {
        $value = new DateTimeImmutable('2026-09-06 08:57:02', new DateTimeZone('UTC'));

        self::assertSame('2026-09-06T08:57:02Z', ApiDateTime::datetime($value));
        self::assertSame('2026-09-06', ApiDateTime::date($value));
    }

    /**
     * A value carrying another zone is converted rather than relabelled, so
     * the instant stays the same.
     */
    public function testItConvertsOtherZonesToUtc(): void
    {
        $brussels = new DateTimeImmutable('2026-09-06 10:57:02', new DateTimeZone('Europe/Brussels'));

        self::assertSame('2026-09-06T08:57:02Z', ApiDateTime::datetime($brussels));
    }
}
