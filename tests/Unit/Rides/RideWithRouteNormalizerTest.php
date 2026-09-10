<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rides;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Rides\Normalizer\RideWithRouteNormalizer;
use App\Domain\Rides\ReadModel\RideWithRoute;
use App\Domain\Routing\Entity\StationRoute;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Stations\Entity\Station;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class RideWithRouteNormalizerTest extends TestCase
{
    public function testItShapesARideWithItsRoute(): void
    {
        $result = self::normalize(self::pair());

        self::assertSame(73147208, $result['ride_id']);
        self::assertSame('2026-09-06T08:57:02Z', $result['checkout_time']);
        self::assertSame('2026-09-06T09:05:30Z', $result['checkin_time']);
        self::assertSame(1500.0, $result['distance_meters']);
        self::assertSame(400.0, $result['expected_duration_seconds']);
        self::assertSame(508.0, $result['actual_duration_seconds']);
        self::assertSame(108.0, $result['duration_vs_expected_seconds']);
    }

    /**
     * The stored duration is whole minutes, which would overstate the speed.
     * This ride was out for 508 seconds, not 10 minutes.
     */
    public function testItComputesSpeedFromTheExactSecondsRatherThanTheRoundedMinutes(): void
    {
        self::assertSame(10.63, self::normalize(self::pair())['speed_kmh']);
    }

    public function testItReportsNoSpeedWhenTheRouteIsUnknown(): void
    {
        $result = self::normalize(self::pair(withRoute: false));

        self::assertNull($result['distance_meters']);
        self::assertNull($result['speed_kmh']);
        self::assertNull($result['expected_duration_seconds']);
        self::assertNull($result['duration_vs_expected_seconds']);
        self::assertSame(508.0, $result['actual_duration_seconds']);
    }

    /**
     * A ride returned in the same second it was taken has no meaningful speed,
     * and dividing by it would raise an error rather than produce one.
     */
    public function testItReportsNoSpeedWhenTheRideTookNoTime(): void
    {
        $result = self::normalize(self::pair(checkinTime: '2026-09-06 08:57:02'));

        self::assertSame(0.0, $result['actual_duration_seconds']);
        self::assertNull($result['speed_kmh']);
    }

    public function testItReportsANegativeComparisonForAFastRide(): void
    {
        $result = self::normalize(self::pair(durationSeconds: 600.0));

        self::assertSame(-92.0, $result['duration_vs_expected_seconds']);
    }

    public function testItReportsNoWeatherWhenNoneWasRecorded(): void
    {
        $result = self::normalize(self::pair());

        self::assertArrayHasKey('weather', $result);
        self::assertNull($result['weather']);
    }

    /**
     * @return array<string, mixed>
     */
    private static function normalize(RideWithRoute $pair): array
    {
        $normalizer = new RideWithRouteNormalizer();
        $normalizer->setNormalizer(self::createStub(NormalizerInterface::class));

        return $normalizer->normalize($pair);
    }

    private static function pair(
        bool $withRoute = true,
        string $checkinTime = '2026-09-06 09:05:30',
        float $durationSeconds = 400.0,
    ): RideWithRoute {
        $ride = new Ride(
            73147208,
            123,
            'Completed',
            8,
            '5097',
            '021',
            '021- Driekoningen',
            '15',
            self::utc('2026-09-06 08:57:02'),
            '041',
            '041- Van Eyck',
            '23',
            self::utc($checkinTime),
        );

        if (! $withRoute) {
            return new RideWithRoute($ride, null);
        }

        $route = new StationRoute(
            self::station('021'),
            self::station('041'),
            TravelMode::Bike,
            1500.0,
            $durationSeconds,
        );

        return new RideWithRoute($ride, $route);
    }

    private static function station(string $id): Station
    {
        return new Station($id, "{$id}- Somewhere", $id, 51.2189, 4.4131, 'Street', '2600', ['KEY'], 24);
    }

    private static function utc(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
