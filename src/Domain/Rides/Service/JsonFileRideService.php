<?php

declare(strict_types=1);

namespace App\Domain\Rides\Service;

use App\Domain\Rides\Contract\RideDataSource;
use App\Domain\Rides\ValueObject\RideRecord;
use RuntimeException;

/**
 * Reads the ride history from the operator's JSON export.
 */
final readonly class JsonFileRideService implements RideDataSource
{
    public function __construct(
        private string $path,
    ) {
    }

    public function fetchRides(): array
    {
        if (! is_file($this->path)) {
            throw new RuntimeException("Rides export not found at {$this->path}.");
        }

        $payload = json_decode(
            (string) file_get_contents($this->path),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );

        $rides = [];

        foreach ($payload['data']['CustomerRides'] as $ride) {
            $record = RideRecord::fromExport($ride);
            $rides[$record->rideId] = $record;
        }

        return $rides;
    }
}
