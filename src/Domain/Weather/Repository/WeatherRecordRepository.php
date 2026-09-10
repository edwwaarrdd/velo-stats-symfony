<?php

declare(strict_types=1);

namespace App\Domain\Weather\Repository;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Weather\Entity\WeatherRecord;
use App\Domain\Weather\ValueObject\WeatherObservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeatherRecord>
 */
class WeatherRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeatherRecord::class);
    }

    public function findOneForRide(Ride $ride): ?WeatherRecord
    {
        return $this->findOneBy(['ride' => $ride]);
    }

    /**
     * One row per ride, so a re-check overwrites rather than accumulating.
     */
    public function upsert(Ride $ride, WeatherObservation $observation): void
    {
        $record = $this->findOneForRide($ride);

        if ($record === null) {
            $record = new WeatherRecord($ride, $observation);
            $this->getEntityManager()->persist($record);
        } else {
            $record->fill($observation);
        }

        $this->getEntityManager()->flush();
    }
}
