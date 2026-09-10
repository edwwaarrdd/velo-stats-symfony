<?php

declare(strict_types=1);

namespace App\Domain\Weather\Contract;

use App\Domain\Weather\ValueObject\WeatherObservation;
use App\Support\Coordinate;
use DateTimeInterface;

interface WeatherService
{
    public function getWeather(Coordinate $location, DateTimeInterface $at): WeatherObservation;
}
