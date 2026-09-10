<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use DateTimeZone;

/**
 * Date and time formatting for API responses. Everything is rendered in UTC,
 * whatever timezone the value carries.
 */
final class ApiDateTime
{
    public const string DATETIME_FORMAT = 'Y-m-d\TH:i:s\Z';

    public const string DATE_FORMAT = 'Y-m-d';

    public static function datetime(?DateTimeInterface $value): ?string
    {
        return self::format($value, self::DATETIME_FORMAT);
    }

    public static function date(?DateTimeInterface $value): ?string
    {
        return self::format($value, self::DATE_FORMAT);
    }

    private static function format(?DateTimeInterface $value, string $format): ?string
    {
        if ($value === null) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($value)
            ->setTimezone(new DateTimeZone('UTC'))
            ->format($format);
    }
}
