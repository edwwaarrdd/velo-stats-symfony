<?php

declare(strict_types=1);

namespace App\Domain\Stations\Service;

use App\Domain\Stations\Contract\StationInformationService;
use App\Domain\Stations\ValueObject\StationInformation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Reads the operator's public GBFS station information feed.
 */
final readonly class VeloAntwerpStationInformationService implements StationInformationService
{
    public function __construct(
        private HttpClientInterface $http,
        private string $stationInformationUrl,
    ) {
    }

    public function fetchStations(): array
    {
        $payload = $this->http
            ->request('GET', $this->stationInformationUrl, [
                'timeout' => 10,
                'max_duration' => 10,
            ])
            ->toArray();

        $stations = [];

        foreach ($payload['data']['stations'] as $station) {
            $information = StationInformation::fromGbfs($station);
            $stations[$information->stationId] = $information;
        }

        return $stations;
    }
}
