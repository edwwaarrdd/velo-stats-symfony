<?php

declare(strict_types=1);

namespace App\Domain\Rides\Service;

use App\Domain\Rides\Repository\RideRepository;
use App\Support\ApiDateTime;
use App\Support\Round;
use DateTimeImmutable;

/**
 * What the rides actually cost, and what they would have cost on the two
 * pay-as-you-go alternatives.
 */
final readonly class RideCostCalculator
{
    public const float ANNUAL_SUBSCRIPTION_PRICE_EUR = 58.0;

    public const int DAYS_PER_YEAR = 365;

    public const float DAY_PASS_PRICE_EUR = 5.0;

    public const float WEEK_PASS_PRICE_EUR = 12.0;

    public function __construct(
        private RideRepository $rides,
    ) {
    }

    /**
     * @return array<string, int|float|string|null>
     */
    public function calculate(): array
    {
        $checkoutTimes = $this->rides->allCheckoutTimes();
        $totalRides = count($checkoutTimes);

        if ($totalRides === 0) {
            return $this->emptySummary();
        }

        $timestamps = array_map(
            static fn (DateTimeImmutable $time): int => $time->getTimestamp(),
            $checkoutTimes,
        );
        $firstRide = $checkoutTimes[array_search(min($timestamps), $timestamps, true)];
        $lastRide = $checkoutTimes[array_search(max($timestamps), $timestamps, true)];

        // Inclusive of both end days, so a single-day history is one day long
        // rather than zero.
        $dateRangeDays = (int) $firstRide->setTime(0, 0)
            ->diff($lastRide->setTime(0, 0))
            ->days + 1;

        $proratedSubscriptionPrice = Round::money(
            self::ANNUAL_SUBSCRIPTION_PRICE_EUR * $dateRangeDays / self::DAYS_PER_YEAR,
        );

        // Deliberately divides the rounded price rather than the exact one, so
        // the per-ride figure is consistent with the total shown beside it.
        $costPerRide = Round::money($proratedSubscriptionPrice / $totalRides);

        $rideDays = count(array_unique(array_map(
            static fn (DateTimeImmutable $time): string => $time->format('Y-m-d'),
            $checkoutTimes,
        )));

        // ISO week numbering, so a week spanning New Year counts once.
        $rideWeeks = count(array_unique(array_map(
            static fn (DateTimeImmutable $time): string => $time->format('o-W'),
            $checkoutTimes,
        )));

        $dayPassEquivalent = Round::money($rideDays * self::DAY_PASS_PRICE_EUR);
        $weekPassEquivalent = Round::money($rideWeeks * self::WEEK_PASS_PRICE_EUR);

        return [
            'total_rides' => $totalRides,
            'first_ride_date' => ApiDateTime::date($firstRide),
            'last_ride_date' => ApiDateTime::date($lastRide),
            'date_range_days' => $dateRangeDays,
            'subscription_price_eur' => self::ANNUAL_SUBSCRIPTION_PRICE_EUR,
            'prorated_subscription_price_eur' => $proratedSubscriptionPrice,
            'cost_per_ride_eur' => $costPerRide,
            'day_pass_equivalent_eur' => $dayPassEquivalent,
            'week_pass_equivalent_eur' => $weekPassEquivalent,
            'money_saved_vs_day_passes_eur' => Round::money($dayPassEquivalent - $proratedSubscriptionPrice),
            'money_saved_vs_week_passes_eur' => Round::money($weekPassEquivalent - $proratedSubscriptionPrice),
        ];
    }

    /**
     * @return array<string, int|float|string|null>
     */
    private function emptySummary(): array
    {
        return [
            'total_rides' => 0,
            'first_ride_date' => null,
            'last_ride_date' => null,
            'date_range_days' => null,
            'subscription_price_eur' => self::ANNUAL_SUBSCRIPTION_PRICE_EUR,
            'prorated_subscription_price_eur' => null,
            'cost_per_ride_eur' => null,
            'day_pass_equivalent_eur' => null,
            'week_pass_equivalent_eur' => null,
            'money_saved_vs_day_passes_eur' => null,
            'money_saved_vs_week_passes_eur' => null,
        ];
    }
}
