<?php

declare(strict_types=1);

namespace App\Domain\Stations\Entity;

use App\Domain\Stations\Repository\StationRepository;
use App\Support\Coordinate;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StationRepository::class)]
#[ORM\Table(name: 'stations')]
#[ORM\HasLifecycleCallbacks]
class Station
{
    #[ORM\Id]
    #[ORM\Column(name: 'station_id', type: 'string', length: 32)]
    private string $stationId;

    #[ORM\Column(name: 'name', type: 'string')]
    private string $name;

    #[ORM\Column(name: 'short_name', type: 'string', length: 32)]
    private string $shortName;

    #[ORM\Column(name: 'lat', type: 'float')]
    private float $lat;

    #[ORM\Column(name: 'lon', type: 'float')]
    private float $lon;

    #[ORM\Column(name: 'address', type: 'string')]
    private string $address;

    #[ORM\Column(name: 'post_code', type: 'string', length: 16)]
    private string $postCode;

    /** @var list<string> */
    #[ORM\Column(name: 'rental_methods', type: 'json')]
    private array $rentalMethods;

    #[ORM\Column(name: 'capacity', type: 'integer', options: ['default' => 0])]
    private int $capacity = 0;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $updatedAt = null;

    /**
     * @param list<string> $rentalMethods
     */
    public function __construct(
        string $stationId,
        string $name,
        string $shortName,
        float $lat,
        float $lon,
        string $address,
        string $postCode,
        array $rentalMethods,
        int $capacity,
    ) {
        $this->stationId = $stationId;
        $this->fill($name, $shortName, $lat, $lon, $address, $postCode, $rentalMethods, $capacity);
    }

    /**
     * Overwrite everything except the identifier, which is how a reload of the
     * upstream feed updates a station that already exists.
     *
     * @param list<string> $rentalMethods
     */
    public function fill(
        string $name,
        string $shortName,
        float $lat,
        float $lon,
        string $address,
        string $postCode,
        array $rentalMethods,
        int $capacity,
    ): void {
        $this->name = $name;
        $this->shortName = $shortName;
        $this->lat = $lat;
        $this->lon = $lon;
        $this->address = $address;
        $this->postCode = $postCode;
        $this->rentalMethods = $rentalMethods;
        $this->capacity = $capacity;
    }

    public function stationId(): string
    {
        return $this->stationId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function lat(): float
    {
        return $this->lat;
    }

    public function lon(): float
    {
        return $this->lon;
    }

    public function coordinate(): Coordinate
    {
        return new Coordinate($this->lat, $this->lon);
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
