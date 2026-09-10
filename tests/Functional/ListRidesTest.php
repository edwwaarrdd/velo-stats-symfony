<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ListRidesTest extends DatabaseTestCase
{
    public function testItReturnsAnEmptyListWhenThereAreNoRides(): void
    {
        self::assertSame(['results' => []], $this->getJson('/rides'));
    }

    public function testItReturnsARideWithItsRouteAndWeather(): void
    {
        $origin = $this->givenStation();
        $destination = $this->givenStation(['station_id' => '041', 'name' => '041- Van Eyck']);
        $this->givenRoute($origin, $destination);
        $ride = $this->givenRide();
        $this->givenWeather($ride);

        $result = $this->getJson('/rides')['results'][0];

        self::assertSame(73147208, $result['ride_id']);
        self::assertSame('2026-09-06T08:57:02Z', $result['checkout_time']);
        self::assertSame(1500.0, $result['distance_meters']);
        self::assertSame(400.0, $result['expected_duration_seconds']);
        self::assertSame(508.0, $result['actual_duration_seconds']);
        self::assertSame(10.63, $result['speed_kmh']);
        self::assertSame(3, $result['weather']['weather_code']);
    }

    /**
     * The route lookup is directional and bike-only, so neither the reverse
     * pair nor another travel mode may be picked up by mistake.
     */
    public function testItIgnoresARouteForTheReversePair(): void
    {
        $origin = $this->givenStation();
        $destination = $this->givenStation(['station_id' => '041', 'name' => '041- Van Eyck']);
        $this->givenRoute($destination, $origin);
        $this->givenRide();

        $result = $this->getJson('/rides')['results'][0];

        self::assertNull($result['distance_meters']);
        self::assertNull($result['speed_kmh']);
    }

    public function testItIgnoresARouteForAnotherTravelMode(): void
    {
        $origin = $this->givenStation();
        $destination = $this->givenStation(['station_id' => '041', 'name' => '041- Van Eyck']);
        $this->givenRoute($origin, $destination, mode: \App\Domain\Routing\Enum\TravelMode::Foot);
        $this->givenRide();

        self::assertNull($this->getJson('/rides')['results'][0]['distance_meters']);
    }

    public function testItReportsNullWeatherForARideThatHasNone(): void
    {
        $this->givenRide();

        $result = $this->getJson('/rides')['results'][0];

        self::assertArrayHasKey('weather', $result);
        self::assertNull($result['weather']);
    }

    public function testItOrdersNewestFirstAndBreaksTiesByRideId(): void
    {
        $this->givenRide(['ride_id' => 1, 'checkout_time' => '2025-03-12 15:30:17']);
        $this->givenRide(['ride_id' => 2, 'checkout_time' => '2026-09-06 08:57:02']);
        $this->givenRide(['ride_id' => 3, 'checkout_time' => '2026-09-06 08:57:02']);

        $ids = array_column($this->getJson('/rides')['results'], 'ride_id');

        self::assertSame([3, 2, 1], $ids);
    }

    /**
     * A ride from a station that has since been retired still has to appear,
     * which is why the station columns are codes rather than references.
     */
    public function testItStillReturnsARideWhoseStationsAreUnknown(): void
    {
        $this->givenRide(['origin_station_code' => '999', 'destination_station_code' => '998']);

        $result = $this->getJson('/rides')['results'][0];

        self::assertSame('999', $result['origin_station_code']);
        self::assertNull($result['distance_meters']);
    }
}
