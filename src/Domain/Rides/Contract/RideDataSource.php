<?php

declare(strict_types=1);

namespace App\Domain\Rides\Contract;

use App\Domain\Rides\ValueObject\RideRecord;

interface RideDataSource
{
    /**
     * Every ride in the export, keyed by ride id. A duplicate id in the source
     * collapses to the last occurrence.
     *
     * @return array<int, RideRecord>
     */
    public function fetchRides(): array;
}
