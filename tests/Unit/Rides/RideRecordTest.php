<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rides;

use App\Domain\Rides\ValueObject\RideRecord;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RideRecordTest extends TestCase
{
    public function testItRenamesTheExportsCamelCaseFields(): void
    {
        $record = RideRecord::fromExport(self::export());

        self::assertSame(73147208, $record->rideId);
        self::assertSame(123, $record->accountId);
        self::assertSame('5097', $record->bikeNumber);
        self::assertSame('021', $record->originStationCode);
        self::assertSame('23', $record->destinationSlotId);
    }

    /**
     * The export's timestamps carry no zone. Reading them as anything but UTC
     * would shift every ride, and every other port reads them this way.
     */
    public function testItReadsTheNaiveTimestampsAsUtc(): void
    {
        $record = RideRecord::fromExport(self::export());

        self::assertSame('2026-09-06T08:57:02+00:00', $record->checkoutTime->format('c'));
        self::assertSame('2026-09-06T09:05:30+00:00', $record->checkinTime->format('c'));
    }

    /**
     * The station code is zero-padded in the export and must stay a string, or
     * "021" silently becomes 21 and matches no station.
     */
    public function testItKeepsZeroPaddedCodesAsStrings(): void
    {
        $record = RideRecord::fromExport(self::export());

        self::assertSame('021', $record->originStationCode);
    }

    public function testItRejectsAnUnreadableTimestamp(): void
    {
        $this->expectException(RuntimeException::class);

        RideRecord::fromExport(self::export(['checkoutTime' => 'the sixth of September']));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private static function export(array $overrides = []): array
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
