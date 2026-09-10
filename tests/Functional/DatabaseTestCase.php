<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Routing\Entity\StationRoute;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Stations\Entity\Station;
use App\Domain\Weather\Entity\WeatherRecord;
use App\Domain\Weather\ValueObject\WeatherObservation;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Boots the kernel against a throwaway SQLite file, rebuilt empty for every
 * test so nothing leaks between them.
 */
abstract class DatabaseTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function givenStation(array $overrides = []): Station
    {
        $values = array_replace([
            'station_id' => '021',
            'name' => '021- Driekoningen',
            'short_name' => '021',
            'lat' => 51.2189,
            'lon' => 4.4131,
            'address' => 'Driekoningenstraat',
            'post_code' => '2600',
            'rental_methods' => ['KEY'],
            'capacity' => 24,
        ], $overrides);

        $station = new Station(
            $values['station_id'],
            $values['name'],
            $values['short_name'],
            $values['lat'],
            $values['lon'],
            $values['address'],
            $values['post_code'],
            $values['rental_methods'],
            $values['capacity'],
        );

        $this->entityManager->persist($station);
        $this->entityManager->flush();

        return $station;
    }

    /**
     * @param array<string, mixed> $overrides
     */
    protected function givenRide(array $overrides = []): Ride
    {
        $values = array_replace([
            'ride_id' => 73147208,
            'account_id' => 123,
            'status' => 'Completed',
            'duration' => 8,
            'bike_number' => '5097',
            'origin_station_code' => '021',
            'origin_station' => '021- Driekoningen',
            'origin_slot_id' => '15',
            'checkout_time' => '2026-09-06 08:57:02',
            'destination_station_code' => '041',
            'destination_station' => '041- Van Eyck',
            'destination_slot_id' => '23',
            'checkin_time' => '2026-09-06 09:05:30',
        ], $overrides);

        $ride = new Ride(
            $values['ride_id'],
            $values['account_id'],
            $values['status'],
            $values['duration'],
            $values['bike_number'],
            $values['origin_station_code'],
            $values['origin_station'],
            $values['origin_slot_id'],
            self::utc($values['checkout_time']),
            $values['destination_station_code'],
            $values['destination_station'],
            $values['destination_slot_id'],
            self::utc($values['checkin_time']),
        );

        $this->entityManager->persist($ride);
        $this->entityManager->flush();

        return $ride;
    }

    protected function givenRoute(
        Station $origin,
        Station $destination,
        float $distanceMeters = 1500.0,
        float $durationSeconds = 400.0,
        TravelMode $mode = TravelMode::Bike,
    ): StationRoute {
        $route = new StationRoute($origin, $destination, $mode, $distanceMeters, $durationSeconds);

        $this->entityManager->persist($route);
        $this->entityManager->flush();

        return $route;
    }

    protected function givenWeather(Ride $ride, string $observedAt = '2026-09-06 09:00:00'): WeatherRecord
    {
        $record = new WeatherRecord($ride, new WeatherObservation(
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
            self::utc($observedAt),
        ));

        $this->entityManager->persist($record);
        $this->entityManager->flush();

        return $record;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getJson(string $path): array
    {
        $this->client->request('GET', $path);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');

        return json_decode(
            (string) $this->client->getResponse()->getContent(),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    protected static function utc(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
