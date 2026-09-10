<?php

declare(strict_types=1);

namespace App\Domain\Weather\Service;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Weather\Contract\WeatherService;
use App\Domain\Weather\Repository\WeatherRecordRepository;
use App\Domain\Weather\ValueObject\WeatherObservation;
use App\Support\Coordinate;

/**
 * Historical weather never changes, so a ride is looked up once. The force
 * flag exists for the case where the stored observation is wrong rather than
 * stale.
 */
final readonly class CachedRideWeatherService
{
    public function __construct(
        private WeatherService $weatherService,
        private WeatherRecordRepository $records,
    ) {
    }

    public function getWeather(Ride $ride, Coordinate $location, bool $force = false): WeatherObservation
    {
        if (!$force) {
            $cached = $this->records->findOneForRide($ride);

            if (null !== $cached) {
                return $cached->toObservation();
            }
        }

        // The weather that matters is the weather the rider arrived in, at the
        // station they set off from.
        $observation = $this->weatherService->getWeather($location, $ride->checkinTime());

        $this->records->upsert($ride, $observation);

        return $observation;
    }
}
