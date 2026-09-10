<?php

declare(strict_types=1);

namespace App\Domain\Rides\MessageHandler;

use App\Domain\Rides\Message\CheckRideDistance;
use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\Service\CachedStationRouteService;
use App\Domain\Stations\Repository\StationRepository;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckRideDistanceHandler
{
    public function __construct(
        private RideRepository $rides,
        private StationRepository $stations,
        private CachedStationRouteService $routeService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckRideDistance $message): void
    {
        $ride = $this->rides->findRide($message->rideId);

        if (null === $ride) {
            throw new RuntimeException("Cannot check distance: ride {$message->rideId} does not exist.");
        }

        if (null !== $ride->distanceCheckedAt()) {
            return;
        }

        $origin = $this->stations->findStation($ride->originStationCode());
        $destination = $this->stations->findStation($ride->destinationStationCode());

        // The export contains rides from stations that have since been retired.
        // Leaving the ride unmarked means a later reload of the station feed
        // gives it another chance.
        if (null === $origin || null === $destination) {
            $this->logger->error(sprintf(
                'Cannot check distance for ride %d: unknown station code(s) %s / %s',
                $message->rideId,
                $ride->originStationCode(),
                $ride->destinationStationCode(),
            ));

            return;
        }

        $this->routeService->getRoute($origin, $destination, TravelMode::Bike);

        $ride->markDistanceChecked();
        $this->rides->save($ride);
    }
}
