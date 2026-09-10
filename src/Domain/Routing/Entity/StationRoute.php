<?php

declare(strict_types=1);

namespace App\Domain\Routing\Entity;

use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\Repository\StationRouteRepository;
use App\Domain\Stations\Entity\Station;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * The upstream routing service is free and rate-limited, and the answer for a
 * pair of fixed docking stations never changes, so every lookup is stored here
 * and never asked for twice. The unique constraint is the cache key.
 */
#[ORM\Entity(repositoryClass: StationRouteRepository::class)]
#[ORM\Table(name: 'station_routes')]
#[ORM\UniqueConstraint(
    name: 'unique_station_route_per_mode',
    columns: ['origin_station_id', 'destination_station_id', 'mode'],
)]
#[ORM\HasLifecycleCallbacks]
class StationRoute
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Station::class)]
    #[ORM\JoinColumn(
        name: 'origin_station_id',
        referencedColumnName: 'station_id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private Station $originStation;

    #[ORM\ManyToOne(targetEntity: Station::class)]
    #[ORM\JoinColumn(
        name: 'destination_station_id',
        referencedColumnName: 'station_id',
        nullable: false,
        onDelete: 'CASCADE',
    )]
    private Station $destinationStation;

    #[ORM\Column(name: 'mode', type: 'string', length: 8, enumType: TravelMode::class)]
    private TravelMode $mode;

    #[ORM\Column(name: 'distance_meters', type: 'float')]
    private float $distanceMeters;

    #[ORM\Column(name: 'duration_seconds', type: 'float')]
    private float $durationSeconds;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(
        Station $originStation,
        Station $destinationStation,
        TravelMode $mode,
        float $distanceMeters,
        float $durationSeconds,
    ) {
        $this->originStation = $originStation;
        $this->destinationStation = $destinationStation;
        $this->mode = $mode;
        $this->distanceMeters = $distanceMeters;
        $this->durationSeconds = $durationSeconds;
    }

    public function originStationId(): string
    {
        return $this->originStation->stationId();
    }

    public function destinationStationId(): string
    {
        return $this->destinationStation->stationId();
    }

    public function distanceMeters(): float
    {
        return $this->distanceMeters;
    }

    public function durationSeconds(): float
    {
        return $this->durationSeconds;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
