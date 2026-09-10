<?php

declare(strict_types=1);

namespace App\Domain\Rides\Response;

use App\Domain\Weather\Response\WeatherResponse;
use App\Support\ApiDateTime;
use App\Support\Round;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Shapes a ride for the API, including the three figures that are derived
 * rather than stored.
 */
final class RideResponse
{
    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    public static function fromRow(array $row): array
    {
        $checkoutTime = self::time($row['checkout_time']);
        $checkinTime = self::time($row['checkin_time']);

        $distanceMeters = $row['distance_meters'] === null ? null : (float) $row['distance_meters'];
        $expectedDurationSeconds = $row['expected_duration_seconds'] === null
            ? null
            : (float) $row['expected_duration_seconds'];

        $actualDurationSeconds = self::actualDurationSeconds($checkoutTime, $checkinTime);

        return [
            'ride_id' => (int) $row['ride_id'],
            'account_id' => (int) $row['account_id'],
            'status' => (string) $row['status'],
            'duration' => (int) $row['duration'],
            'bike_number' => (string) $row['bike_number'],
            'origin_station_code' => (string) $row['origin_station_code'],
            'origin_station' => (string) $row['origin_station'],
            'origin_slot_id' => (string) $row['origin_slot_id'],
            'checkout_time' => ApiDateTime::datetime($checkoutTime),
            'destination_station_code' => (string) $row['destination_station_code'],
            'destination_station' => (string) $row['destination_station'],
            'destination_slot_id' => (string) $row['destination_slot_id'],
            'checkin_time' => ApiDateTime::datetime($checkinTime),
            'distance_meters' => $distanceMeters,
            'speed_kmh' => self::speedKmh($distanceMeters, $actualDurationSeconds),
            'expected_duration_seconds' => Round::money($expectedDurationSeconds),
            'actual_duration_seconds' => $actualDurationSeconds,
            'duration_vs_expected_seconds' => self::durationVsExpectedSeconds(
                $actualDurationSeconds,
                $expectedDurationSeconds,
            ),
            'weather' => WeatherResponse::fromRideRow($row),
        ];
    }

    /**
     * How long the bike was actually out, to the second.
     *
     * The stored duration is whole minutes, which is too coarse for anything
     * derived from it.
     */
    private static function actualDurationSeconds(
        ?DateTimeImmutable $checkoutTime,
        ?DateTimeImmutable $checkinTime,
    ): ?float {
        if ($checkoutTime === null || $checkinTime === null) {
            return null;
        }

        return Round::money($checkinTime->getTimestamp() - $checkoutTime->getTimestamp());
    }

    /**
     * Average speed over the ride, using the exact seconds rather than the
     * rounded minutes, which would overstate it.
     */
    private static function speedKmh(?float $distanceMeters, ?float $seconds): ?float
    {
        if ($distanceMeters === null || $seconds === null || $seconds <= 0.0) {
            return null;
        }

        return Round::money(($distanceMeters / 1000) / ($seconds / 3600));
    }

    /**
     * How much longer the ride took than the router predicted. Negative means
     * the rider beat the prediction.
     */
    private static function durationVsExpectedSeconds(?float $actual, ?float $expected): ?float
    {
        if ($actual === null || $expected === null) {
            return null;
        }

        return Round::money($actual - $expected);
    }

    private static function time(mixed $value): ?DateTimeImmutable
    {
        return $value === null
            ? null
            : new DateTimeImmutable((string) $value, new DateTimeZone('UTC'));
    }
}
