<?php

declare(strict_types=1);

namespace App\Domain\Rides\Service;

use App\Domain\Rides\Repository\RideRepository;
use App\Support\Round;

/**
 * The aggregate view of every ride.
 */
final readonly class RideSummaryCalculator
{
    public function __construct(
        private RideRepository $rides,
    ) {
    }

    /**
     * @return array<string, int|float|null>
     */
    public function calculate(): array
    {
        $stats = $this->rides->summaryStats();

        return [
            'total_rides' => (int) ($stats['total_rides'] ?? 0),
            'total_duration' => self::nullableInt($stats['total_duration'] ?? null),
            'average_duration' => Round::money(self::nullableFloat($stats['average_duration'] ?? null)),
            'longest_ride_duration' => self::nullableInt($stats['longest_ride_duration'] ?? null),
            'shortest_ride_duration' => self::nullableInt($stats['shortest_ride_duration'] ?? null),
            'total_distance_meters' => self::nullableFloat($stats['total_distance_meters'] ?? null),
            'average_distance_meters' => Round::money(self::nullableFloat($stats['average_distance_meters'] ?? null)),
        ];
    }

    private static function nullableInt(mixed $value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        return $value === null ? null : (float) $value;
    }
}
