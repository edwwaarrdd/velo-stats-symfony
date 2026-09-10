<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class RideSummaryTest extends DatabaseTestCase
{
    public function testItReportsNullsRatherThanZerosWhenThereAreNoRides(): void
    {
        self::assertSame([
            'total_rides' => 0,
            'total_duration' => null,
            'average_duration' => null,
            'longest_ride_duration' => null,
            'shortest_ride_duration' => null,
            'total_distance_meters' => null,
            'average_distance_meters' => null,
        ], $this->getJson('/rides/summary'));
    }

    public function testItAggregatesDurationsAcrossEveryRide(): void
    {
        $this->givenRide(['ride_id' => 1, 'duration' => 10]);
        $this->givenRide(['ride_id' => 2, 'duration' => 20]);
        $this->givenRide(['ride_id' => 3, 'duration' => 15]);

        $summary = $this->getJson('/rides/summary');

        self::assertSame(3, $summary['total_rides']);
        self::assertSame(45, $summary['total_duration']);
        self::assertSame(15.0, $summary['average_duration']);
        self::assertSame(20, $summary['longest_ride_duration']);
        self::assertSame(10, $summary['shortest_ride_duration']);
    }

    /**
     * A ride with no cached route contributes nothing to the distance figures
     * rather than dragging the average towards zero.
     */
    public function testItAveragesDistanceOverOnlyTheRidesThatHaveARoute(): void
    {
        $origin = $this->givenStation();
        $destination = $this->givenStation(['station_id' => '041', 'name' => '041- Van Eyck']);
        $this->givenRoute($origin, $destination, distanceMeters: 2000.0);

        $this->givenRide(['ride_id' => 1]);
        $this->givenRide(['ride_id' => 2]);
        $this->givenRide(['ride_id' => 3, 'origin_station_code' => '999']);

        $summary = $this->getJson('/rides/summary');

        self::assertSame(3, $summary['total_rides']);
        self::assertSame(4000.0, $summary['total_distance_meters']);
        self::assertSame(2000.0, $summary['average_distance_meters']);
    }
}
