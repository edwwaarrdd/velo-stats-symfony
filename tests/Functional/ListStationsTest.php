<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class ListStationsTest extends DatabaseTestCase
{
    public function testItReturnsAnEmptyListWhenThereAreNoStations(): void
    {
        self::assertSame(['results' => []], $this->getJson('/stations'));
    }

    /**
     * Only what a map needs. The address, capacity and rental methods are
     * stored but deliberately not published.
     */
    public function testItPublishesOnlyTheIdNameAndPosition(): void
    {
        $this->givenStation();

        $result = $this->getJson('/stations')['results'][0];

        self::assertSame([
            'station_id' => '021',
            'name' => '021- Driekoningen',
            'lat' => 51.2189,
            'lon' => 4.4131,
        ], $result);
    }

    public function testItKeepsZeroPaddedStationCodesAsStrings(): void
    {
        $this->givenStation(['station_id' => '007']);

        self::assertSame('007', $this->getJson('/stations')['results'][0]['station_id']);
    }
}
