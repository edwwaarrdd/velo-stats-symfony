<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Rides\Message\CheckRideDistance;
use App\Domain\Rides\MessageHandler\CheckRideDistanceHandler;
use App\Domain\Routing\Contract\RouteService;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\Repository\StationRouteRepository;
use App\Domain\Routing\ValueObject\Route;
use App\Support\Coordinate;
use RuntimeException;

final class CheckRideDistanceHandlerTest extends DatabaseTestCase
{
    public function testItCachesTheRouteAndMarksTheRideChecked(): void
    {
        $origin = $this->givenStation();
        $destination = $this->givenStation(['station_id' => '041', 'name' => '041- Van Eyck']);
        $ride = $this->givenRide();

        $this->handle(new CheckRideDistance($ride->rideId()));

        $route = static::getContainer()->get(StationRouteRepository::class)
            ->findOneFor($origin, $destination, TravelMode::Bike);

        self::assertNotNull($route);
        self::assertSame(1500.0, $route->distanceMeters());
        self::assertNotNull($this->refresh($ride)->distanceCheckedAt());
    }

    /**
     * The guard that makes the command safe to re-run: a ride already checked
     * costs no upstream call at all.
     */
    public function testItSkipsARideThatHasAlreadyBeenChecked(): void
    {
        $this->givenStation();
        $this->givenStation(['station_id' => '041', 'name' => '041- Van Eyck']);
        $ride = $this->givenRide();
        $ride->markDistanceChecked();
        $this->entityManager->flush();

        $calls = 0;
        $this->handle(new CheckRideDistance($ride->rideId()), $calls);

        self::assertSame(0, $calls);
    }

    /**
     * An unknown station is logged and left unmarked, so a later reload of the
     * station feed gives the ride another chance.
     */
    public function testItLeavesARideWithUnknownStationsUnmarked(): void
    {
        $ride = $this->givenRide(['origin_station_code' => '999']);

        $calls = 0;
        $this->handle(new CheckRideDistance($ride->rideId()), $calls);

        self::assertSame(0, $calls);
        self::assertNull($this->refresh($ride)->distanceCheckedAt());
    }

    public function testItFailsLoudlyForARideThatDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);

        $this->handle(new CheckRideDistance(404));
    }

    private function handle(CheckRideDistance $message, int &$calls = 0): void
    {
        $routeService = new class($calls) implements RouteService {
            public function __construct(private int &$calls)
            {
            }

            public function getRoute(Coordinate $origin, Coordinate $destination, TravelMode $mode): Route
            {
                $this->calls++;

                return new Route(1500.0, 400.0);
            }
        };

        $container = static::getContainer();

        $handler = new CheckRideDistanceHandler(
            $container->get(\App\Domain\Rides\Repository\RideRepository::class),
            $container->get(\App\Domain\Stations\Repository\StationRepository::class),
            new \App\Domain\Routing\Service\CachedStationRouteService(
                $routeService,
                $container->get(StationRouteRepository::class),
            ),
            $container->get('logger'),
        );

        $handler($message);
    }

    private function refresh(\App\Domain\Rides\Entity\Ride $ride): \App\Domain\Rides\Entity\Ride
    {
        $this->entityManager->refresh($ride);

        return $ride;
    }
}
