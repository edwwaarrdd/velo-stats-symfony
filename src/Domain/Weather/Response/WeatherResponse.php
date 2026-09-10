<?php

declare(strict_types=1);

namespace App\Domain\Weather\Response;

use App\Support\ApiDateTime;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Shapes a weather row for the API.
 */
final class WeatherResponse
{
    /**
     * The row arrives from the rides list query, where the weather columns are
     * null for a ride whose weather has not been fetched.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, float|int|string|null>|null
     */
    public static function fromRideRow(array $row): ?array
    {
        if ($row['observed_at'] === null) {
            return null;
        }

        return [
            'temperature_c' => (float) $row['temperature_c'],
            'apparent_temperature_c' => (float) $row['apparent_temperature_c'],
            'precipitation_mm' => (float) $row['precipitation_mm'],
            'rain_mm' => (float) $row['rain_mm'],
            'snowfall_cm' => (float) $row['snowfall_cm'],
            'cloud_cover_percent' => (float) $row['cloud_cover_percent'],
            'wind_speed_kmh' => (float) $row['wind_speed_kmh'],
            'wind_gusts_kmh' => (float) $row['wind_gusts_kmh'],
            'wind_direction_degrees' => (float) $row['wind_direction_degrees'],
            'relative_humidity_percent' => (float) $row['relative_humidity_percent'],
            'weather_code' => (int) $row['weather_code'],
            'observed_at' => ApiDateTime::datetime(
                new DateTimeImmutable((string) $row['observed_at'], new DateTimeZone('UTC')),
            ),
        ];
    }
}
