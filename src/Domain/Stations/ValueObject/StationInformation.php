<?php

declare(strict_types=1);

namespace App\Domain\Stations\ValueObject;

final readonly class StationInformation
{
    /**
     * @param list<string> $rentalMethods
     */
    public function __construct(
        public string $stationId,
        public string $name,
        public string $shortName,
        public float $lat,
        public float $lon,
        public string $address,
        public string $postCode,
        public array $rentalMethods,
        public int $capacity,
    ) {
    }

    /**
     * The two defaulted fields are the ones the feed has been seen to omit.
     * Everything else is required, and a feed missing it is a feed worth
     * failing on rather than silently storing an empty station.
     *
     * @param array<string, mixed> $station
     */
    public static function fromGbfs(array $station): self
    {
        return new self(
            (string) $station['station_id'],
            (string) $station['name'],
            (string) $station['short_name'],
            (float) $station['lat'],
            (float) $station['lon'],
            (string) $station['address'],
            (string) $station['post_code'],
            array_values($station['rental_methods'] ?? []),
            (int) ($station['capacity'] ?? 0),
        );
    }
}
