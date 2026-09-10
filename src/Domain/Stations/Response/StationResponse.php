<?php

declare(strict_types=1);

namespace App\Domain\Stations\Response;

/**
 * Shapes a station for the API. Only what a map needs is exposed; the address,
 * capacity and rental methods stay internal.
 */
final class StationResponse
{
    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, string|float>
     */
    public static function fromRow(array $row): array
    {
        return [
            'station_id' => (string) $row['station_id'],
            'name' => (string) $row['name'],
            'lat' => (float) $row['lat'],
            'lon' => (float) $row['lon'],
        ];
    }
}
