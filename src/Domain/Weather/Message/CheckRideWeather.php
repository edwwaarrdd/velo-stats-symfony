<?php

declare(strict_types=1);

namespace App\Domain\Weather\Message;

final readonly class CheckRideWeather
{
    public function __construct(
        public int $rideId,
        public bool $force = false,
    ) {
    }
}
