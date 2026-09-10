<?php

declare(strict_types=1);

namespace App\Tests\Unit\Stations;

use App\Domain\Stations\Service\VeloAntwerpStationInformationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class VeloAntwerpStationInformationServiceTest extends TestCase
{
    public function testItKeysTheStationsByTheirId(): void
    {
        $stations = self::serviceReturning([
            'data' => ['stations' => [
                self::station(['station_id' => '021', 'name' => '021- Driekoningen']),
                self::station(['station_id' => '041', 'name' => '041- Van Eyck']),
            ]],
        ])->fetchStations();

        self::assertSame(['021', '041'], array_keys($stations));
        self::assertSame('041- Van Eyck', $stations['041']->name);
    }

    /**
     * The feed has been seen to omit these two, and a station missing them is
     * still a station worth storing.
     */
    public function testItDefaultsTheFieldsTheFeedSometimesOmits(): void
    {
        $station = self::serviceReturning([
            'data' => ['stations' => [self::station(omit: ['rental_methods', 'capacity'])]],
        ])->fetchStations()['021'];

        self::assertSame([], $station->rentalMethods);
        self::assertSame(0, $station->capacity);
    }

    /**
     * The feed sometimes hands back rental methods as an object with numeric
     * keys, which would serialise as an object rather than a list.
     */
    public function testItNormalisesRentalMethodsToAList(): void
    {
        $station = self::serviceReturning([
            'data' => ['stations' => [self::station(['rental_methods' => [1 => 'KEY', 3 => 'CREDITCARD']])]],
        ])->fetchStations()['021'];

        self::assertSame(['KEY', 'CREDITCARD'], $station->rentalMethods);
    }

    /**
     * @param array<string, mixed> $overrides
     * @param list<string>         $omit
     *
     * @return array<string, mixed>
     */
    private static function station(array $overrides = [], array $omit = []): array
    {
        $station = array_replace([
            'station_id' => '021',
            'name' => '021- Driekoningen',
            'short_name' => '021',
            'lat' => 51.2189,
            'lon' => 4.4131,
            'address' => 'Driekoningenstraat',
            'post_code' => '2600',
            'rental_methods' => ['KEY', 'CREDITCARD'],
            'capacity' => 24,
        ], $overrides);

        foreach ($omit as $key) {
            unset($station[$key]);
        }

        return $station;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function serviceReturning(array $payload): VeloAntwerpStationInformationService
    {
        return new VeloAntwerpStationInformationService(
            new MockHttpClient(new MockResponse((string) json_encode($payload))),
            'https://gbfs.example/station_information.json',
        );
    }
}
