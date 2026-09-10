<?php

declare(strict_types=1);

namespace App\Domain\Routing\Repository;

use App\Domain\Routing\Entity\StationRoute;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Stations\Entity\Station;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StationRoute>
 */
class StationRouteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StationRoute::class);
    }

    public function findOneFor(Station $origin, Station $destination, TravelMode $mode): ?StationRoute
    {
        return $this->findOneBy([
            'originStation' => $origin,
            'destinationStation' => $destination,
            'mode' => $mode,
        ]);
    }

    public function save(StationRoute $route): void
    {
        $this->getEntityManager()->persist($route);
        $this->getEntityManager()->flush();
    }
}
