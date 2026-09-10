<?php

declare(strict_types=1);

namespace App\Domain\Stations\Repository;

use App\Domain\Stations\Entity\Station;
use App\Domain\Stations\ValueObject\StationInformation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Station>
 */
class StationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Station::class);
    }

    public function findStation(string $stationId): ?Station
    {
        return $this->find($stationId);
    }

    /**
     * Insert the station, or update the one already stored under this id.
     */
    public function upsert(StationInformation $information): bool
    {
        $station = $this->findStation($information->stationId);
        $created = $station === null;

        if ($station === null) {
            $station = new Station(
                $information->stationId,
                $information->name,
                $information->shortName,
                $information->lat,
                $information->lon,
                $information->address,
                $information->postCode,
                $information->rentalMethods,
                $information->capacity,
            );
            $this->getEntityManager()->persist($station);
        } else {
            $station->fill(
                $information->name,
                $information->shortName,
                $information->lat,
                $information->lon,
                $information->address,
                $information->postCode,
                $information->rentalMethods,
                $information->capacity,
            );
        }

        return $created;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * The four fields the list endpoint serves, in primary key order.
     *
     * @return list<array<string, mixed>>
     */
    public function findAllForApi(): array
    {
        return $this->getEntityManager()->getConnection()
            ->executeQuery('SELECT station_id, name, lat, lon FROM stations')
            ->fetchAllAssociative();
    }
}
