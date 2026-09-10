<?php

declare(strict_types=1);

namespace App\Domain\Rides\ReadModel;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Routing\Entity\StationRoute;

/**
 * A ride paired with the cached route for its station pair, if one exists.
 *
 * The route hangs off the pair of station codes rather than off the ride, so it
 * cannot be a relation on the entity. Pairing them here keeps the list query to
 * a single round trip instead of one lookup per ride.
 */
final readonly class RideWithRoute
{
    public function __construct(
        public Ride $ride,
        public ?StationRoute $route,
    ) {
    }
}
