<?php

declare(strict_types=1);

namespace App\Domain\Routing\Service;

use App\Domain\Routing\Contract\RouteService;
use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\ValueObject\Route;
use App\Support\Coordinate;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Routes between two points using a public OSRM instance.
 */
final readonly class OsrmRouteService implements RouteService
{
    public function __construct(
        private HttpClientInterface $http,
        private string $baseUrl,
    ) {
    }

    public function getRoute(Coordinate $origin, Coordinate $destination, TravelMode $mode): Route
    {
        // OSRM expects coordinates as "lon,lat", not "lat,lon".
        $coordinates = "{$origin->lon},{$origin->lat};{$destination->lon},{$destination->lat}";

        $url = sprintf(
            '%s/%s/route/v1/%s/%s',
            $this->baseUrl,
            $mode->osrmInstancePath(),
            $mode->value,
            $coordinates,
        );

        $payload = $this->http
            ->request('GET', $url, [
                // The geometry is never stored, so asking for it would only
                // make the response bigger.
                'query' => ['overview' => 'false'],
                'timeout' => 10,
                'max_duration' => 10,
            ])
            ->toArray();

        if (($payload['code'] ?? null) !== 'Ok') {
            throw new RuntimeException(
                'OSRM request failed: '.($payload['message'] ?? $payload['code'] ?? 'unknown error'),
            );
        }

        if (($payload['routes'][0] ?? null) === null) {
            throw new RuntimeException('OSRM returned no route for the requested coordinates.');
        }

        return Route::fromOsrmRoute($payload['routes'][0]);
    }
}
