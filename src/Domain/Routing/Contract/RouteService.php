<?php

declare(strict_types=1);

namespace App\Domain\Routing\Contract;

use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\ValueObject\Route;
use App\Support\Coordinate;

interface RouteService
{
    public function getRoute(Coordinate $origin, Coordinate $destination, TravelMode $mode): Route;
}
