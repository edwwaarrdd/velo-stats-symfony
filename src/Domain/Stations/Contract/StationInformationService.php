<?php

declare(strict_types=1);

namespace App\Domain\Stations\Contract;

use App\Domain\Stations\ValueObject\StationInformation;

interface StationInformationService
{
    /**
     * Every station the operator currently publishes, keyed by station id.
     *
     * @return array<string, StationInformation>
     */
    public function fetchStations(): array;
}
