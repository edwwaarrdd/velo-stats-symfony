<?php

declare(strict_types=1);

namespace App\Domain\Routing\Service;

use App\Domain\Routing\Contract\RouteService;
use App\Domain\Routing\Entity\StationRoute;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\Repository\StationRouteRepository;
use App\Domain\Routing\ValueObject\Route;
use App\Domain\Stations\Entity\Station;

/**
 * A read-through cache in front of the routing service.
 *
 * Docking stations do not move, so the route between any two of them is
 * answered once and stored forever. This is what keeps the free upstream
 * service usable at all.
 */
final readonly class CachedStationRouteService
{
    public function __construct(
        private RouteService $routeService,
        private StationRouteRepository $routes,
    ) {
    }

    public function getRoute(Station $origin, Station $destination, TravelMode $mode): Route
    {
        $cached = $this->routes->findOneFor($origin, $destination, $mode);

        if ($cached !== null) {
            return new Route($cached->distanceMeters(), $cached->durationSeconds());
        }

        $route = $this->routeService->getRoute($origin->coordinate(), $destination->coordinate(), $mode);

        $this->routes->save(new StationRoute(
            $origin,
            $destination,
            $mode,
            $route->distanceMeters,
            $route->durationSeconds,
        ));

        return $route;
    }
}
