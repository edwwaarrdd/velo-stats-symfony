<?php

declare(strict_types=1);

namespace App\Domain\Weather\Message;

/**
 * Asks for one ride's weather to be looked up and cached.
 */
final readonly class CheckRideWeather
{
    public function __construct(
        public int $rideId,
        public bool $force = false,
    ) {
    }
}
