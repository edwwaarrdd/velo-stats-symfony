<?php

declare(strict_types=1);

namespace App\Domain\Rides\ValueObject;

use DateTimeImmutable;
use DateTimeZone;

/**
 * One ride as the export describes it. The export uses camelCase keys and
 * naive timestamps; this is where both are translated, once.
 */
final readonly class RideRecord
{
    /**
     * The timestamps in the export carry no zone. They are Antwerp local time
     * in name only: the rest of this application works in UTC, and the other
     * ports read them the same way.
     */
    private const string DATETIME_FORMAT = 'Y-m-d H:i:s';

    public function __construct(
        public int $rideId,
        public int $accountId,
        public string $status,
        public int $duration,
        public string $bikeNumber,
        public string $originStationCode,
        public string $originStation,
        public string $originSlotId,
        public DateTimeImmutable $checkoutTime,
        public string $destinationStationCode,
        public string $destinationStation,
        public string $destinationSlotId,
        public DateTimeImmutable $checkinTime,
    ) {
    }

    /**
     * @param array<string, mixed> $ride
     */
    public static function fromExport(array $ride): self
    {
        return new self(
            (int) $ride['id'],
            (int) $ride['accountId'],
            (string) $ride['status'],
            (int) $ride['duration'],
            (string) $ride['bikeNumber'],
            (string) $ride['originStationCode'],
            (string) $ride['originStation'],
            (string) $ride['originSlotId'],
            self::parseDateTime((string) $ride['checkoutTime']),
            (string) $ride['destinationStationCode'],
            (string) $ride['destinationStation'],
            (string) $ride['destinationSlotId'],
            self::parseDateTime((string) $ride['checkinTime']),
        );
    }

    private static function parseDateTime(string $value): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat(
            self::DATETIME_FORMAT,
            $value,
            new DateTimeZone('UTC'),
        );

        if ($parsed === false) {
            throw new \RuntimeException("Ride export has an unreadable timestamp: {$value}.");
        }

        return $parsed;
    }
}
