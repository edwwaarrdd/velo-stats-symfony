<?php

declare(strict_types=1);

namespace App\Domain\Rides\Repository;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Rides\ValueObject\RideRecord;
use App\Domain\Routing\Enum\TravelMode;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Every query against rides lives here.
 *
 * The list endpoint hydrates entities and hands them to the serializer, which
 * is what the Laravel implementation does and what makes the two comparable.
 * The summary is an aggregate returning one row of numbers, so there is
 * nothing to hydrate and it stays a plain query.
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
        $created = null === $ride;

        if (null === $ride) {
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
     * Every ride, newest first, with its weather already loaded.
     *
     * The weather comes back in the same query rather than one lookup per
     * ride, which is the only thing that could turn this into an N+1.
     *
     * @return list<Ride>
     */
    public function findAllWithWeather(): array
    {
        return $this->createQueryBuilder('r')
            ->addSelect('w')
            ->leftJoin('r.weather', 'w')
            ->orderBy('r.checkoutTime', 'DESC')
            ->addOrderBy('r.rideId', 'DESC')
            ->getQuery()
            ->getResult();
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

        return false === $row ? [] : $row;
    }

    /**
     * Every ride's check-out time, which is all the cost calculation needs.
     *
     * Doctrine converts each value to a date object on the way out, the same
     * work the other implementations do for this endpoint.
     *
     * @return list<DateTimeImmutable>
     */
    public function allCheckoutTimes(): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('r.checkoutTime')
            ->getQuery()
            ->getResult();

        return array_column($rows, 'checkoutTime');
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
