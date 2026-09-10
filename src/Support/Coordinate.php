<?php

declare(strict_types=1);

namespace App\Support;

/**
 * A latitude and longitude pair, in that order. The upstream routing API wants
 * them the other way round, which is exactly why they travel together in a
 * named type rather than as two loose floats.
 */
final readonly class Coordinate
{
    public function __construct(
        public float $lat,
        public float $lon,
    ) {
    }
}
