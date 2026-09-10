<?php

declare(strict_types=1);

namespace App\Domain\Routing\ValueObject;

final readonly class Route
{
    public function __construct(
        public float $distanceMeters,
        public float $durationSeconds,
    ) {
    }

    /**
     * @param array<string, mixed> $route one entry from an OSRM `routes` array
     */
    public static function fromOsrmRoute(array $route): self
    {
        return new self((float) $route['distance'], (float) $route['duration']);
    }
}
