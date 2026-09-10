<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Stations\Repository\StationRepository;
use App\Domain\Weather\Contract\WeatherService;
use App\Domain\Weather\Message\CheckRideWeather;
use App\Domain\Weather\MessageHandler\CheckRideWeatherHandler;
use App\Domain\Weather\Repository\WeatherRecordRepository;
use App\Domain\Weather\Service\CachedRideWeatherService;
use App\Domain\Weather\ValueObject\WeatherObservation;
use App\Support\Coordinate;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

final class CheckRideWeatherHandlerTest extends DatabaseTestCase
{
    public function testItStoresTheWeatherAndMarksTheRideChecked(): void
    {
        $this->givenStation();
        $ride = $this->givenRide();

        $this->handle(new CheckRideWeather($ride->rideId()));

        $record = static::getContainer()->get(WeatherRecordRepository::class)->findOneForRide($ride);

        self::assertNotNull($record);
        self::assertSame(18.0, $record->toObservation()->temperatureC);
        self::assertNotNull($this->refresh($ride)->weatherCheckedAt());
    }

    /**
     * The weather that matters is the weather at the origin station when the
     * bike came back, which is an easy pair of details to get backwards.
     */
    public function testItAsksForTheOriginStationAtCheckInTime(): void
    {
        $this->givenStation(['lat' => 51.2189, 'lon' => 4.4131]);
        $this->givenStation(['station_id' => '041', 'lat' => 1.0, 'lon' => 2.0]);
        $ride = $this->givenRide();

        $asked = [];
        $this->handle(new CheckRideWeather($ride->rideId()), asked: $asked);

        self::assertSame(51.2189, $asked['lat']);
        self::assertSame(4.4131, $asked['lon']);
        self::assertSame('2026-09-06T09:05:30+00:00', $asked['at']);
    }

    public function testItSkipsARideThatHasAlreadyBeenChecked(): void
    {
        $this->givenStation();
        $ride = $this->givenRide();
        $ride->markWeatherChecked();
        $this->entityManager->flush();

        $calls = 0;
        $this->handle(new CheckRideWeather($ride->rideId()), $calls);

        self::assertSame(0, $calls);
    }

    /**
     * Forcing is how a wrong observation gets replaced, so it has to bypass
     * both the ride's marker and the stored record.
     */
    public function testItRefetchesAnAlreadyCheckedRideWhenForced(): void
    {
        $this->givenStation();
        $ride = $this->givenRide();
        $ride->markWeatherChecked();
        $this->entityManager->flush();

        $calls = 0;
        $this->handle(new CheckRideWeather($ride->rideId(), force: true), $calls);

        self::assertSame(1, $calls);
    }

    public function testItLeavesARideWithAnUnknownOriginUnmarked(): void
    {
        $ride = $this->givenRide(['origin_station_code' => '999']);

        $calls = 0;
        $this->handle(new CheckRideWeather($ride->rideId()), $calls);

        self::assertSame(0, $calls);
        self::assertNull($this->refresh($ride)->weatherCheckedAt());
    }

    public function testItFailsLoudlyForARideThatDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);

        $this->handle(new CheckRideWeather(404));
    }

    /**
     * @param array<string, mixed> $asked
     */
    private function handle(CheckRideWeather $message, int &$calls = 0, array &$asked = []): void
    {
        $weatherService = new class($calls, $asked) implements WeatherService {
            /**
             * @param array<string, mixed> $asked
             */
            public function __construct(private int &$calls, private array &$asked)
            {
            }

            public function getWeather(Coordinate $location, DateTimeInterface $at): WeatherObservation
            {
                ++$this->calls;
                $this->asked = [
                    'lat' => $location->lat,
                    'lon' => $location->lon,
                    'at' => $at->format('c'),
                ];

                return new WeatherObservation(
                    18.0,
                    17.1,
                    0.0,
                    0.0,
                    0.0,
                    42.0,
                    11.2,
                    24.5,
                    210.0,
                    68.0,
                    3,
                    new DateTimeImmutable('2026-09-06 09:00:00'),
                );
            }
        };

        $container = static::getContainer();

        $handler = new CheckRideWeatherHandler(
            $container->get(RideRepository::class),
            $container->get(StationRepository::class),
            new CachedRideWeatherService($weatherService, $container->get(WeatherRecordRepository::class)),
            $container->get('logger'),
        );

        $handler($message);
    }

    private function refresh(Ride $ride): Ride
    {
        $this->entityManager->refresh($ride);

        return $ride;
    }
}
