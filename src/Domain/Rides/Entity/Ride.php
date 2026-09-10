<?php

declare(strict_types=1);

namespace App\Domain\Rides\Entity;

use App\Domain\Rides\Repository\RideRepository;
use App\Domain\Weather\Entity\WeatherRecord;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * One completed hire, as exported by the operator.
 *
 * The station columns hold codes rather than a relation, and there is
 * deliberately no foreign key to stations: the export contains rides from
 * stations that have since been retired, and those rides still have to load.
 * The background checks treat an unknown code as a logged non-event.
 */
#[ORM\Entity(repositoryClass: RideRepository::class)]
#[ORM\Table(name: 'rides')]
#[ORM\Index(name: 'rides_checkout_time_index', columns: ['checkout_time'])]
#[ORM\HasLifecycleCallbacks]
class Ride
{
    /**
     * The identifier the operator assigned, not one of ours.
     */
    #[ORM\Id]
    #[ORM\Column(name: 'ride_id', type: 'bigint')]
    private int $rideId;

    #[ORM\Column(name: 'account_id', type: 'bigint')]
    private int $accountId;

    #[ORM\Column(name: 'status', type: 'string', length: 32)]
    private string $status;

    /**
     * Whole minutes, as exported. Anything needing precision recomputes from
     * the check-out and check-in timestamps instead.
     */
    #[ORM\Column(name: 'duration', type: 'integer')]
    private int $duration;

    #[ORM\Column(name: 'bike_number', type: 'string', length: 32)]
    private string $bikeNumber;

    #[ORM\Column(name: 'origin_station_code', type: 'string', length: 32)]
    private string $originStationCode;

    #[ORM\Column(name: 'origin_station', type: 'string')]
    private string $originStation;

    #[ORM\Column(name: 'origin_slot_id', type: 'string', length: 16)]
    private string $originSlotId;

    #[ORM\Column(name: 'checkout_time', type: 'datetime_immutable')]
    private DateTimeImmutable $checkoutTime;

    #[ORM\Column(name: 'destination_station_code', type: 'string', length: 32)]
    private string $destinationStationCode;

    #[ORM\Column(name: 'destination_station', type: 'string')]
    private string $destinationStation;

    #[ORM\Column(name: 'destination_slot_id', type: 'string', length: 16)]
    private string $destinationSlotId;

    #[ORM\Column(name: 'checkin_time', type: 'datetime_immutable')]
    private DateTimeImmutable $checkinTime;

    /**
     * Set once the distance check has run. Leaving it null is how a failed
     * check asks to be retried by the next command run.
     */
    #[ORM\Column(name: 'distance_checked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $distanceCheckedAt = null;

    #[ORM\Column(name: 'weather_checked_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $weatherCheckedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * The weather at the origin station when this ride ended, once it has been
     * checked. Mapped as the inverse side so the list query can fetch it
     * alongside the ride rather than one query per row.
     */
    #[ORM\OneToOne(mappedBy: 'ride', targetEntity: WeatherRecord::class)]
    private ?WeatherRecord $weather = null;

    public function __construct(
        int $rideId,
        int $accountId,
        string $status,
        int $duration,
        string $bikeNumber,
        string $originStationCode,
        string $originStation,
        string $originSlotId,
        DateTimeImmutable $checkoutTime,
        string $destinationStationCode,
        string $destinationStation,
        string $destinationSlotId,
        DateTimeImmutable $checkinTime,
    ) {
        $this->rideId = $rideId;
        $this->fill(
            $accountId,
            $status,
            $duration,
            $bikeNumber,
            $originStationCode,
            $originStation,
            $originSlotId,
            $checkoutTime,
            $destinationStationCode,
            $destinationStation,
            $destinationSlotId,
            $checkinTime,
        );
    }

    /**
     * Overwrite everything the export carries, leaving the two check timestamps
     * alone so re-importing does not queue work that has already been done.
     */
    public function fill(
        int $accountId,
        string $status,
        int $duration,
        string $bikeNumber,
        string $originStationCode,
        string $originStation,
        string $originSlotId,
        DateTimeImmutable $checkoutTime,
        string $destinationStationCode,
        string $destinationStation,
        string $destinationSlotId,
        DateTimeImmutable $checkinTime,
    ): void {
        $this->accountId = $accountId;
        $this->status = $status;
        $this->duration = $duration;
        $this->bikeNumber = $bikeNumber;
        $this->originStationCode = $originStationCode;
        $this->originStation = $originStation;
        $this->originSlotId = $originSlotId;
        $this->checkoutTime = $checkoutTime;
        $this->destinationStationCode = $destinationStationCode;
        $this->destinationStation = $destinationStation;
        $this->destinationSlotId = $destinationSlotId;
        $this->checkinTime = $checkinTime;
    }

    public function rideId(): int
    {
        return $this->rideId;
    }

    public function originStationCode(): string
    {
        return $this->originStationCode;
    }

    public function destinationStationCode(): string
    {
        return $this->destinationStationCode;
    }

    public function checkoutTime(): DateTimeImmutable
    {
        return $this->checkoutTime;
    }

    public function checkinTime(): DateTimeImmutable
    {
        return $this->checkinTime;
    }

    public function accountId(): int
    {
        return $this->accountId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function duration(): int
    {
        return $this->duration;
    }

    public function bikeNumber(): string
    {
        return $this->bikeNumber;
    }

    public function originStation(): string
    {
        return $this->originStation;
    }

    public function originSlotId(): string
    {
        return $this->originSlotId;
    }

    public function destinationStation(): string
    {
        return $this->destinationStation;
    }

    public function destinationSlotId(): string
    {
        return $this->destinationSlotId;
    }

    public function weather(): ?WeatherRecord
    {
        return $this->weather;
    }

    public function distanceCheckedAt(): ?DateTimeImmutable
    {
        return $this->distanceCheckedAt;
    }

    public function weatherCheckedAt(): ?DateTimeImmutable
    {
        return $this->weatherCheckedAt;
    }

    public function markDistanceChecked(): void
    {
        $this->distanceCheckedAt = new DateTimeImmutable();
    }

    public function markWeatherChecked(): void
    {
        $this->weatherCheckedAt = new DateTimeImmutable();
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
