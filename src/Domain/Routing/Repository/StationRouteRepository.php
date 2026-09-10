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

    /**
     * Every cached route for one travel mode, keyed by the station pair it
     * connects, so a caller can look up many rides' routes without a query
     * each.
     *
     * @return array<string, StationRoute>
     */
    public function findAllIndexedByStationPair(TravelMode $mode): array
    {
        $routes = $this->createQueryBuilder('sr')
            ->where('sr.mode = :mode')
            ->setParameter('mode', $mode)
            ->getQuery()
            ->getResult();

        $indexed = [];

        foreach ($routes as $route) {
            $indexed[self::key($route->originStationId(), $route->destinationStationId())] = $route;
        }

        return $indexed;
    }

    /**
     * The key both sides of the lookup agree on. Routes are directional, so
     * the order of the two codes matters.
     */
    public static function key(string $originStationId, string $destinationStationId): string
    {
        return $originStationId.'|'.$destinationStationId;
    }

    public function save(StationRoute $route): void
    {
        $this->getEntityManager()->persist($route);
        $this->getEntityManager()->flush();
    }
}
