<?php

declare(strict_types=1);

namespace App\Domain\Rides\Repository;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Rides\ValueObject\RideRecord;
use App\Domain\Routing\Enum\TravelMode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Every query against rides lives here.
 *
 * The two read models the API serves are written as SQL rather than DQL. They
 * join a ride to its cached route and its weather and then aggregate, which
 * DQL expresses poorly and the query builder expresses worse, and they are on
 * the hot path of the two busiest endpoints.
 *
 * @extends ServiceEntityRepository<Ride>
 */
class RideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ride::class);
    }

    public function findRide(int $rideId): ?Ride
    {
        return $this->find($rideId);
    }

    /**
     * Insert the ride, or update the one already stored under this id.
     *
     * The two check timestamps are deliberately not part of the record, so
     * re-importing the export never queues work that has already been done.
     */
    public function upsert(RideRecord $record): bool
    {
        $ride = $this->findRide($record->rideId);
        $created = $ride === null;

        if ($ride === null) {
            $ride = new Ride(
                $record->rideId,
                $record->accountId,
                $record->status,
                $record->duration,
                $record->bikeNumber,
                $record->originStationCode,
                $record->originStation,
                $record->originSlotId,
                $record->checkoutTime,
                $record->destinationStationCode,
                $record->destinationStation,
                $record->destinationSlotId,
                $record->checkinTime,
            );
            $this->getEntityManager()->persist($ride);
        } else {
            $ride->fill(
                $record->accountId,
                $record->status,
                $record->duration,
                $record->bikeNumber,
                $record->originStationCode,
                $record->originStation,
                $record->originSlotId,
                $record->checkoutTime,
                $record->destinationStationCode,
                $record->destinationStation,
                $record->destinationSlotId,
                $record->checkinTime,
            );
        }

        return $created;
    }

    public function save(Ride $ride): void
    {
        $this->getEntityManager()->persist($ride);
        $this->getEntityManager()->flush();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Ride ids still awaiting a distance check.
     *
     * @return list<int>
     */
    public function idsAwaitingDistanceCheck(): array
    {
        return $this->idColumn(
            'SELECT ride_id FROM rides WHERE distance_checked_at IS NULL ORDER BY ride_id',
        );
    }

    /**
     * Ride ids still awaiting a weather check, or every ride when forced.
     *
     * @return list<int>
     */
    public function idsAwaitingWeatherCheck(bool $includeChecked = false): array
    {
        $sql = $includeChecked
            ? 'SELECT ride_id FROM rides ORDER BY ride_id'
            : 'SELECT ride_id FROM rides WHERE weather_checked_at IS NULL ORDER BY ride_id';

        return $this->idColumn($sql);
    }

    /**
     * Every ride with the fields the list endpoint serves: the cached bike
     * route for its station pair, and its weather.
     *
     * The unique constraint on station_routes means the join matches at most
     * one route row per ride, so no grouping is needed.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllForApi(): array
    {
        $sql = <<<'SQL'
            SELECT
                r.ride_id,
                r.account_id,
                r.status,
                r.duration,
                r.bike_number,
                r.origin_station_code,
                r.origin_station,
                r.origin_slot_id,
                r.checkout_time,
                r.destination_station_code,
                r.destination_station,
                r.destination_slot_id,
                r.checkin_time,
                sr.distance_meters,
                sr.duration_seconds AS expected_duration_seconds,
                w.temperature_c,
                w.apparent_temperature_c,
                w.precipitation_mm,
                w.rain_mm,
                w.snowfall_cm,
                w.cloud_cover_percent,
                w.wind_speed_kmh,
                w.wind_gusts_kmh,
                w.wind_direction_degrees,
                w.relative_humidity_percent,
                w.weather_code,
                w.observed_at
            FROM rides r
            LEFT JOIN station_routes sr
                ON sr.origin_station_id = r.origin_station_code
                AND sr.destination_station_id = r.destination_station_code
                AND sr.mode = :mode
            LEFT JOIN weather_records w
                ON w.ride_id = r.ride_id
            ORDER BY r.checkout_time DESC, r.ride_id DESC
            SQL;

        return $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['mode' => TravelMode::Bike->value])
            ->fetchAllAssociative();
    }

    /**
     * The aggregates the summary endpoint serves.
     *
     * Distance sums and averages skip rides with no cached route, which the
     * left join gives for free: those rows contribute NULL, and SQL aggregates
     * ignore NULL.
     *
     * @return array<string, mixed>
     */
    public function summaryStats(): array
    {
        $sql = <<<'SQL'
            SELECT
                COUNT(*) AS total_rides,
                SUM(r.duration) AS total_duration,
                AVG(r.duration) AS average_duration,
                MAX(r.duration) AS longest_ride_duration,
                MIN(r.duration) AS shortest_ride_duration,
                SUM(sr.distance_meters) AS total_distance_meters,
                AVG(sr.distance_meters) AS average_distance_meters
            FROM rides r
            LEFT JOIN station_routes sr
                ON sr.origin_station_id = r.origin_station_code
                AND sr.destination_station_id = r.destination_station_code
                AND sr.mode = :mode
            SQL;

        $row = $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['mode' => TravelMode::Bike->value])
            ->fetchAssociative();

        return $row === false ? [] : $row;
    }

    /**
     * Every ride's check-out time, which is all the cost calculation needs.
     *
     * @return list<\DateTimeImmutable>
     */
    public function allCheckoutTimes(): array
    {
        $rows = $this->getEntityManager()->getConnection()
            ->executeQuery('SELECT checkout_time FROM rides')
            ->fetchFirstColumn();

        return array_map(
            static fn (string $value): \DateTimeImmutable => new \DateTimeImmutable(
                $value,
                new \DateTimeZone('UTC'),
            ),
            $rows,
        );
    }

    /**
     * @return list<int>
     */
    private function idColumn(string $sql): array
    {
        $rows = $this->getEntityManager()->getConnection()
            ->executeQuery($sql)
            ->fetchFirstColumn();

        return array_map(static fn (mixed $id): int => (int) $id, $rows);
    }
}
