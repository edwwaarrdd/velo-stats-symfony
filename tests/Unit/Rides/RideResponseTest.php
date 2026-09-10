<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rides;

use App\Domain\Rides\Response\RideResponse;
use PHPUnit\Framework\TestCase;

final class RideResponseTest extends TestCase
{
    public function testItShapesARideWithItsRouteAndWeather(): void
    {
        $result = RideResponse::fromRow(self::row());

        self::assertSame(73147208, $result['ride_id']);
        self::assertSame('2026-09-06T08:57:02Z', $result['checkout_time']);
        self::assertSame('2026-09-06T09:05:30Z', $result['checkin_time']);
        self::assertSame(1500.0, $result['distance_meters']);
        self::assertSame(400.0, $result['expected_duration_seconds']);
        self::assertSame(508.0, $result['actual_duration_seconds']);
        self::assertSame(108.0, $result['duration_vs_expected_seconds']);
        self::assertSame(3, $result['weather']['weather_code']);
        self::assertSame('2026-09-06T09:00:00Z', $result['weather']['observed_at']);
    }

    /**
     * The stored duration is whole minutes, which would overstate the speed.
     * This ride was out for 508 seconds, not 10 minutes.
     */
    public function testItComputesSpeedFromTheExactSecondsRatherThanTheRoundedMinutes(): void
    {
        $result = RideResponse::fromRow(self::row());

        self::assertSame(10.63, $result['speed_kmh']);
        self::assertNotSame(9.0, $result['speed_kmh']);
    }

    public function testItReportsNoSpeedWhenTheDistanceIsUnknown(): void
    {
        $result = RideResponse::fromRow(self::row(['distance_meters' => null]));

        self::assertNull($result['distance_meters']);
        self::assertNull($result['speed_kmh']);
        self::assertSame(508.0, $result['actual_duration_seconds']);
    }

    /**
     * A ride returned in the same second it was taken has no meaningful speed,
     * and dividing by it would raise an error rather than produce one.
     */
    public function testItReportsNoSpeedWhenTheRideTookNoTime(): void
    {
        $result = RideResponse::fromRow(self::row(['checkin_time' => '2026-09-06 08:57:02']));

        self::assertSame(0.0, $result['actual_duration_seconds']);
        self::assertNull($result['speed_kmh']);
    }

    public function testItReportsNoComparisonWhenTheRouteIsUnknown(): void
    {
        $result = RideResponse::fromRow(self::row(['expected_duration_seconds' => null]));

        self::assertNull($result['expected_duration_seconds']);
        self::assertNull($result['duration_vs_expected_seconds']);
    }

    public function testItReportsNoWeatherWhenNoneWasRecorded(): void
    {
        $result = RideResponse::fromRow(self::row(['observed_at' => null]));

        self::assertArrayHasKey('weather', $result);
        self::assertNull($result['weather']);
    }

    /**
     * A rider who beat the router's prediction gets a negative comparison.
     */
    public function testItReportsANegativeComparisonForAFastRide(): void
    {
        $result = RideResponse::fromRow(self::row(['expected_duration_seconds' => 600.0]));

        self::assertSame(-92.0, $result['duration_vs_expected_seconds']);
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function row(array $overrides = []): array
    {
        return array_replace([
            'ride_id' => 73147208,
            'account_id' => 123,
            'status' => 'Completed',
            'duration' => 8,
            'bike_number' => '5097',
            'origin_station_code' => '021',
            'origin_station' => '021- Driekoningen',
            'origin_slot_id' => '15',
            'checkout_time' => '2026-09-06 08:57:02',
            'destination_station_code' => '041',
            'destination_station' => '041- Van Eyck',
            'destination_slot_id' => '23',
            'checkin_time' => '2026-09-06 09:05:30',
            'distance_meters' => 1500.0,
            'expected_duration_seconds' => 400.0,
            'temperature_c' => 18.0,
            'apparent_temperature_c' => 17.1,
            'precipitation_mm' => 0.0,
            'rain_mm' => 0.0,
            'snowfall_cm' => 0.0,
            'cloud_cover_percent' => 42.0,
            'wind_speed_kmh' => 11.2,
            'wind_gusts_kmh' => 24.5,
            'wind_direction_degrees' => 210.0,
            'relative_humidity_percent' => 68.0,
            'weather_code' => 3,
            'observed_at' => '2026-09-06 09:00:00',
        ], $overrides);
    }
}
