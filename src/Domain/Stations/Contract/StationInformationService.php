<?php

declare(strict_types=1);

namespace App\Domain\Stations\Contract;

use App\Domain\Stations\ValueObject\StationInformation;

interface StationInformationService
{
    /**
     * @return array<string, StationInformation>
     */
    public function fetchStations(): array;
}
