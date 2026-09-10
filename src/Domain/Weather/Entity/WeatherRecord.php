<?php

declare(strict_types=1);

namespace App\Domain\Weather\Entity;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Weather\Repository\WeatherRecordRepository;
use App\Domain\Weather\ValueObject\WeatherObservation;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * The weather at a ride's origin station when it was returned. One row per
 * ride, enforced by the unique constraint, so the archive is never queried
 * twice for the same ride.
 */
#[ORM\Entity(repositoryClass: WeatherRecordRepository::class)]
#[ORM\Table(name: 'weather_records')]
#[ORM\UniqueConstraint(name: 'weather_records_ride_id_unique', columns: ['ride_id'])]
#[ORM\HasLifecycleCallbacks]
class WeatherRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Ride::class, inversedBy: 'weather')]
    #[ORM\JoinColumn(
        name: 'ride_id',
        referencedColumnName: 'ride_id',
        nullable: false,
        unique: true,
        onDelete: 'CASCADE',
    )]
    private Ride $ride;

    #[ORM\Column(name: 'temperature_c', type: 'float')]
    private float $temperatureC;

    #[ORM\Column(name: 'apparent_temperature_c', type: 'float')]
    private float $apparentTemperatureC;

    #[ORM\Column(name: 'precipitation_mm', type: 'float')]
    private float $precipitationMm;

    #[ORM\Column(name: 'rain_mm', type: 'float')]
    private float $rainMm;

    #[ORM\Column(name: 'snowfall_cm', type: 'float')]
    private float $snowfallCm;

    #[ORM\Column(name: 'cloud_cover_percent', type: 'float')]
    private float $cloudCoverPercent;

    #[ORM\Column(name: 'wind_speed_kmh', type: 'float')]
    private float $windSpeedKmh;

    #[ORM\Column(name: 'wind_gusts_kmh', type: 'float')]
    private float $windGustsKmh;

    #[ORM\Column(name: 'wind_direction_degrees', type: 'float')]
    private float $windDirectionDegrees;

    #[ORM\Column(name: 'relative_humidity_percent', type: 'float')]
    private float $relativeHumidityPercent;

    /**
     * The WMO weather code describing the conditions.
     */
    #[ORM\Column(name: 'weather_code', type: 'integer')]
    private int $weatherCode;

    #[ORM\Column(name: 'observed_at', type: 'datetime_immutable')]
    private DateTimeImmutable $observedAt;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    public function __construct(Ride $ride, WeatherObservation $observation)
    {
        $this->ride = $ride;
        $this->fill($observation);
    }

    public function fill(WeatherObservation $observation): void
    {
        $this->temperatureC = $observation->temperatureC;
        $this->apparentTemperatureC = $observation->apparentTemperatureC;
        $this->precipitationMm = $observation->precipitationMm;
        $this->rainMm = $observation->rainMm;
        $this->snowfallCm = $observation->snowfallCm;
        $this->cloudCoverPercent = $observation->cloudCoverPercent;
        $this->windSpeedKmh = $observation->windSpeedKmh;
        $this->windGustsKmh = $observation->windGustsKmh;
        $this->windDirectionDegrees = $observation->windDirectionDegrees;
        $this->relativeHumidityPercent = $observation->relativeHumidityPercent;
        $this->weatherCode = $observation->weatherCode;
        $this->observedAt = $observation->observedAt;
    }

    public function temperatureC(): float
    {
        return $this->temperatureC;
    }

    public function apparentTemperatureC(): float
    {
        return $this->apparentTemperatureC;
    }

    public function precipitationMm(): float
    {
        return $this->precipitationMm;
    }

    public function rainMm(): float
    {
        return $this->rainMm;
    }

    public function snowfallCm(): float
    {
        return $this->snowfallCm;
    }

    public function cloudCoverPercent(): float
    {
        return $this->cloudCoverPercent;
    }

    public function windSpeedKmh(): float
    {
        return $this->windSpeedKmh;
    }

    public function windGustsKmh(): float
    {
        return $this->windGustsKmh;
    }

    public function windDirectionDegrees(): float
    {
        return $this->windDirectionDegrees;
    }

    public function relativeHumidityPercent(): float
    {
        return $this->relativeHumidityPercent;
    }

    public function weatherCode(): int
    {
        return $this->weatherCode;
    }

    public function observedAt(): DateTimeImmutable
    {
        return $this->observedAt;
    }

    public function toObservation(): WeatherObservation
    {
        return new WeatherObservation(
            $this->temperatureC,
            $this->apparentTemperatureC,
            $this->precipitationMm,
            $this->rainMm,
            $this->snowfallCm,
            $this->cloudCoverPercent,
            $this->windSpeedKmh,
            $this->windGustsKmh,
            $this->windDirectionDegrees,
            $this->relativeHumidityPercent,
            $this->weatherCode,
            $this->observedAt,
        );
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
