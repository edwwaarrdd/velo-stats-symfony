<?php

declare(strict_types=1);

namespace App\Domain\Rides\Message;

/**
 * Asks for one ride's cycling distance to be looked up and cached.
 *
 * Only the identifier travels, so a message stays small and always acts on the
 * ride as it is when the worker picks it up rather than when it was queued.
 */
final readonly class CheckRideDistance
{
    public function __construct(
        public int $rideId,
    ) {
    }
}
