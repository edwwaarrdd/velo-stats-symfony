<?php

declare(strict_types=1);

namespace App\Domain\Weather\MessageHandler;

use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Stations\Repository\StationRepository;
use App\Domain\Weather\Message\CheckRideWeather;
use App\Domain\Weather\Service\CachedRideWeatherService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CheckRideWeatherHandler
{
    public function __construct(
        private RideRepository $rides,
        private StationRepository $stations,
        private CachedRideWeatherService $weatherService,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CheckRideWeather $message): void
    {
        $ride = $this->rides->findRide($message->rideId);

        if (null === $ride) {
            throw new RuntimeException("Cannot check weather: ride {$message->rideId} does not exist.");
        }

        if (null !== $ride->weatherCheckedAt() && !$message->force) {
            return;
        }

        // Only the origin station matters: the weather recorded is the weather
        // the rider set off in.
        $origin = $this->stations->findStation($ride->originStationCode());

        if (null === $origin) {
            $this->logger->error(sprintf(
                'Cannot check weather for ride %d: unknown origin station code %s',
                $message->rideId,
                $ride->originStationCode(),
            ));

            return;
        }

        $this->weatherService->getWeather($ride, $origin->coordinate(), $message->force);

        $ride->markWeatherChecked();
        $this->rides->save($ride);
    }
}
