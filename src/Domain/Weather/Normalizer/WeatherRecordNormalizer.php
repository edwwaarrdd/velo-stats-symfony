<?php

declare(strict_types=1);

namespace App\Domain\Weather\Normalizer;

use App\Domain\Weather\Entity\WeatherRecord;
use App\Support\ApiDateTime;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class WeatherRecordNormalizer implements NormalizerInterface
{
    /**
     * @return array<string, float|int|string|null>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        \assert($data instanceof WeatherRecord);

        return [
            'temperature_c' => $data->temperatureC(),
            'apparent_temperature_c' => $data->apparentTemperatureC(),
            'precipitation_mm' => $data->precipitationMm(),
            'rain_mm' => $data->rainMm(),
            'snowfall_cm' => $data->snowfallCm(),
            'cloud_cover_percent' => $data->cloudCoverPercent(),
            'wind_speed_kmh' => $data->windSpeedKmh(),
            'wind_gusts_kmh' => $data->windGustsKmh(),
            'wind_direction_degrees' => $data->windDirectionDegrees(),
            'relative_humidity_percent' => $data->relativeHumidityPercent(),
            'weather_code' => $data->weatherCode(),
            'observed_at' => ApiDateTime::datetime($data->observedAt()),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof WeatherRecord;
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [WeatherRecord::class => true];
    }
}
