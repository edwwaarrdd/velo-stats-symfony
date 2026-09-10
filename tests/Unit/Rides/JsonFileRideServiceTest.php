<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rides;

use App\Domain\Rides\Service\JsonFileRideService;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JsonFileRideServiceTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = tempnam(sys_get_temp_dir(), 'rides').'.json';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function testItReadsTheRidesOutOfTheWrappedExport(): void
    {
        $this->write(['data' => ['CustomerRides' => [self::ride(), self::ride(['id' => 12299801])]]]);

        $rides = (new JsonFileRideService($this->path))->fetchRides();

        self::assertSame([73147208, 12299801], array_keys($rides));
    }

    /**
     * The export is keyed by ride id, so a duplicate collapses rather than
     * producing two rows that fight over the same primary key.
     */
    public function testItCollapsesDuplicateRideIds(): void
    {
        $this->write(['data' => ['CustomerRides' => [
            self::ride(['bikeNumber' => '1111']),
            self::ride(['bikeNumber' => '2222']),
        ]]]);

        $rides = (new JsonFileRideService($this->path))->fetchRides();

        self::assertCount(1, $rides);
        self::assertSame('2222', $rides[73147208]->bikeNumber);
    }

    public function testItReportsAMissingExportClearly(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Rides export not found');

        (new JsonFileRideService('/nowhere/rides.json'))->fetchRides();
    }

    public function testItRejectsAMalformedExport(): void
    {
        file_put_contents($this->path, '{ not json');

        $this->expectException(JsonException::class);

        (new JsonFileRideService($this->path))->fetchRides();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function write(array $payload): void
    {
        file_put_contents($this->path, (string) json_encode($payload));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function ride(array $overrides = []): array
    {
        return array_replace([
            'id' => 73147208,
            'accountId' => 123,
            'status' => 'Completed',
            'duration' => 8,
            'bikeNumber' => '5097',
            'originStationCode' => '021',
            'originStation' => '021- Driekoningen',
            'originSlotId' => '15',
            'checkoutTime' => '2026-09-06 08:57:02',
            'destinationStationCode' => '041',
            'destinationStation' => '041- Van Eyck',
            'destinationSlotId' => '23',
            'checkinTime' => '2026-09-06 09:05:30',
        ], $overrides);
    }
}
