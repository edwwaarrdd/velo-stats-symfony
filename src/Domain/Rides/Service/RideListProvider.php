<?php

declare(strict_types=1);

namespace App\Domain\Rides\Service;

use App\Domain\Rides\ReadModel\RideWithRoute;
use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\Repository\StationRouteRepository;

/**
 * A route belongs to a pair of station codes rather than to a ride, so it
 * cannot be a relation on the entity and cannot be joined as one. Loading the
 * cached routes once and matching them in memory keeps the endpoint to two
 * queries however many rides there are.
 */
final readonly class RideListProvider
{
    public function __construct(
        private RideRepository $rides,
        private StationRouteRepository $routes,
    ) {
    }

    /**
     * @return list<RideWithRoute>
     */
    public function list(): array
    {
        $routes = $this->routes->findAllIndexedByStationPair(TravelMode::Bike);

        return array_map(
            static fn ($ride): RideWithRoute => new RideWithRoute(
                $ride,
                $routes[StationRouteRepository::key(
                    $ride->originStationCode(),
                    $ride->destinationStationCode(),
                )] ?? null,
            ),
            $this->rides->findAllWithWeather(),
        );
    }
}
