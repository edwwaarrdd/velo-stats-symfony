<?php

declare(strict_types=1);

namespace App\Domain\Weather\ValueObject;

use DateTimeImmutable;
use DateTimeZone;

final readonly class WeatherObservation
{
    public function __construct(
        public float $temperatureC,
        public float $apparentTemperatureC,
        public float $precipitationMm,
        public float $rainMm,
        public float $snowfallCm,
        public float $cloudCoverPercent,
        public float $windSpeedKmh,
        public float $windGustsKmh,
        public float $windDirectionDegrees,
        public float $relativeHumidityPercent,
        public int $weatherCode,
        public DateTimeImmutable $observedAt,
    ) {
    }

    /**
     * Open-Meteo answers in columns rather than rows: `time` is a list of
     * hours, and every variable is a parallel list. One index therefore
     * selects one hour across all of them.
     *
     * @param array<string, list<mixed>> $hourly
     */
    public static function fromOpenMeteoHourly(array $hourly, int $index): self
    {
        return new self(
            (float) $hourly['temperature_2m'][$index],
            (float) $hourly['apparent_temperature'][$index],
            (float) $hourly['precipitation'][$index],
            (float) $hourly['rain'][$index],
            (float) $hourly['snowfall'][$index],
            (float) $hourly['cloud_cover'][$index],
            (float) $hourly['wind_speed_10m'][$index],
            (float) $hourly['wind_gusts_10m'][$index],
            (float) $hourly['wind_direction_10m'][$index],
            (float) $hourly['relative_humidity_2m'][$index],
            (int) $hourly['weather_code'][$index],
            new DateTimeImmutable((string) $hourly['time'][$index], new DateTimeZone('UTC')),
        );
    }
}
