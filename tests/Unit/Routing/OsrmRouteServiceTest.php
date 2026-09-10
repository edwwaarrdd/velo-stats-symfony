<?php

declare(strict_types=1);

namespace App\Tests\Unit\Routing;

use App\Domain\Routing\Enum\TravelMode;
use App\Domain\Routing\Service\OsrmRouteService;
use App\Support\Coordinate;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OsrmRouteServiceTest extends TestCase
{
    public function testItReadsTheDistanceAndDurationOfTheFirstRoute(): void
    {
        $service = self::serviceReturning([
            'code' => 'Ok',
            'routes' => [
                ['distance' => 1500.4, 'duration' => 400.2],
                ['distance' => 9999.0, 'duration' => 9999.0],
            ],
        ]);

        $route = $service->getRoute(new Coordinate(51.21, 4.41), new Coordinate(51.22, 4.42), TravelMode::Bike);

        self::assertSame(1500.4, $route->distanceMeters);
        self::assertSame(400.2, $route->durationSeconds);
    }

    /**
     * Coordinates go out as "lon,lat", and the travel mode picks both the
     * instance and the profile. Getting either wrong silently returns car
     * driving times, which is what happened before the instance path was
     * added.
     */
    public function testItBuildsTheModeSpecificUrlWithLongitudeFirst(): void
    {
        $requested = null;

        $client = new MockHttpClient(static function (string $method, string $url) use (&$requested): MockResponse {
            $requested = $url;

            return new MockResponse((string) json_encode([
                'code' => 'Ok',
                'routes' => [['distance' => 1.0, 'duration' => 1.0]],
            ]));
        });

        (new OsrmRouteService($client, 'https://routing.example'))
            ->getRoute(new Coordinate(51.21, 4.41), new Coordinate(51.22, 4.42), TravelMode::Bike);

        self::assertStringStartsWith(
            'https://routing.example/routed-bike/route/v1/bike/4.41,51.21;4.42,51.22',
            (string) $requested,
        );
        self::assertStringContainsString('overview=false', (string) $requested);
    }

    public function testItRoutesOnFootAgainstTheFootInstance(): void
    {
        $requested = null;

        $client = new MockHttpClient(static function (string $method, string $url) use (&$requested): MockResponse {
            $requested = $url;

            return new MockResponse((string) json_encode([
                'code' => 'Ok',
                'routes' => [['distance' => 1.0, 'duration' => 1.0]],
            ]));
        });

        (new OsrmRouteService($client, 'https://routing.example'))
            ->getRoute(new Coordinate(51.21, 4.41), new Coordinate(51.22, 4.42), TravelMode::Foot);

        self::assertStringContainsString('/routed-foot/route/v1/foot/', (string) $requested);
    }

    public function testItFailsWhenTheServiceCouldNotRoute(): void
    {
        $service = self::serviceReturning([
            'code' => 'NoRoute',
            'message' => 'Impossible route between points',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Impossible route between points');

        $service->getRoute(new Coordinate(51.21, 4.41), new Coordinate(51.22, 4.42), TravelMode::Bike);
    }

    public function testItFailsWhenAnOkResponseCarriesNoRoutes(): void
    {
        $service = self::serviceReturning(['code' => 'Ok', 'routes' => []]);

        $this->expectException(RuntimeException::class);

        $service->getRoute(new Coordinate(51.21, 4.41), new Coordinate(51.22, 4.42), TravelMode::Bike);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function serviceReturning(array $payload): OsrmRouteService
    {
        return new OsrmRouteService(
            new MockHttpClient(new MockResponse((string) json_encode($payload))),
            'https://routing.example',
        );
    }
}
