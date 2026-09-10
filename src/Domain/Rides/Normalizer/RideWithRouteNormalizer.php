<?php

declare(strict_types=1);

namespace App\Domain\Rides\Normalizer;

use App\Domain\Rides\Entity\Ride;
use App\Domain\Rides\ReadModel\RideWithRoute;
use App\Support\ApiDateTime;
use App\Support\Round;
use DateTimeImmutable;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Turns a ride into the object the API publishes, including the three figures
 * that are derived rather than stored.
 */
final class RideWithRouteNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @return array<string, mixed>
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array
    {
        \assert($data instanceof RideWithRoute);

        $ride = $data->ride;
        $route = $data->route;

        $distanceMeters = $route?->distanceMeters();
        $expectedDurationSeconds = $route?->durationSeconds();
        $actualDurationSeconds = self::actualDurationSeconds($ride);

        $weather = $ride->weather();

        return [
            'ride_id' => $ride->rideId(),
            'account_id' => $ride->accountId(),
            'status' => $ride->status(),
            'duration' => $ride->duration(),
            'bike_number' => $ride->bikeNumber(),
            'origin_station_code' => $ride->originStationCode(),
            'origin_station' => $ride->originStation(),
            'origin_slot_id' => $ride->originSlotId(),
            'checkout_time' => ApiDateTime::datetime($ride->checkoutTime()),
            'destination_station_code' => $ride->destinationStationCode(),
            'destination_station' => $ride->destinationStation(),
            'destination_slot_id' => $ride->destinationSlotId(),
            'checkin_time' => ApiDateTime::datetime($ride->checkinTime()),
            'distance_meters' => $distanceMeters,
            'speed_kmh' => self::speedKmh($distanceMeters, $actualDurationSeconds),
            'expected_duration_seconds' => Round::money($expectedDurationSeconds),
            'actual_duration_seconds' => $actualDurationSeconds,
            'duration_vs_expected_seconds' => self::durationVsExpectedSeconds(
                $actualDurationSeconds,
                $expectedDurationSeconds,
            ),
            'weather' => $weather === null ? null : $this->normalizer->normalize($weather, $format, $context),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof RideWithRoute;
    }

    /**
     * @return array<class-string|string, bool>
     */
    public function getSupportedTypes(?string $format): array
    {
        return [RideWithRoute::class => true];
    }

    /**
     * How long the bike was actually out, to the second.
     *
     * The stored duration is whole minutes, which is too coarse for anything
     * derived from it.
     */
    private static function actualDurationSeconds(Ride $ride): ?float
    {
        return Round::money(
            $ride->checkinTime()->getTimestamp() - $ride->checkoutTime()->getTimestamp(),
        );
    }

    /**
     * Average speed over the ride, using the exact seconds rather than the
     * rounded minutes, which would overstate it.
     */
    private static function speedKmh(?float $distanceMeters, ?float $seconds): ?float
    {
        if ($distanceMeters === null || $seconds === null || $seconds <= 0.0) {
            return null;
        }

        return Round::money(($distanceMeters / 1000) / ($seconds / 3600));
    }

    /**
     * How much longer the ride took than the router predicted. Negative means
     * the rider beat the prediction.
     */
    private static function durationVsExpectedSeconds(?float $actual, ?float $expected): ?float
    {
        if ($actual === null || $expected === null) {
            return null;
        }

        return Round::money($actual - $expected);
    }
}
