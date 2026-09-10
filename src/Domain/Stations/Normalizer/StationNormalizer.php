<?php

declare(strict_types=1);

namespace App\Domain\Stations\Normalizer;

use App\Domain\Stations\Entity\Station;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Turns a station into the object the API publishes: only what a map needs.
 * The address, capacity and rental methods are stored but not exposed.
 */
final class StationNormalizer implements NormalizerInterface
{
    /**
     * @return array<string, string|float>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        \assert($data instanceof Station);

        return [
            'station_id' => $data->stationId(),
            'name' => $data->name(),
            'lat' => $data->lat(),
            'lon' => $data->lon(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Station;
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [Station::class => true];
    }
}
