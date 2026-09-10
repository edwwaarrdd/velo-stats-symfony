<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class RideCostTest extends DatabaseTestCase
{
    public function testItReportsTheSubscriptionPriceEvenWithNoRides(): void
    {
        $cost = $this->getJson('/rides/cost');

        self::assertSame(0, $cost['total_rides']);
        self::assertSame(58.0, $cost['subscription_price_eur']);
        self::assertNull($cost['first_ride_date']);
        self::assertNull($cost['cost_per_ride_eur']);
        self::assertNull($cost['money_saved_vs_day_passes_eur']);
    }

    public function testItProratesTheSubscriptionOverTheRideHistory(): void
    {
        $this->givenRide(['ride_id' => 1, 'checkout_time' => '2026-01-01 08:00:00']);
        $this->givenRide(['ride_id' => 2, 'checkout_time' => '2026-01-05 08:00:00']);
        $this->givenRide(['ride_id' => 3, 'checkout_time' => '2026-01-10 08:00:00']);

        $cost = $this->getJson('/rides/cost');

        self::assertSame(3, $cost['total_rides']);
        self::assertSame('2026-01-01', $cost['first_ride_date']);
        self::assertSame('2026-01-10', $cost['last_ride_date']);
        // Inclusive of both end days.
        self::assertSame(10, $cost['date_range_days']);
        self::assertSame(1.59, $cost['prorated_subscription_price_eur']);
        self::assertSame(0.53, $cost['cost_per_ride_eur']);
    }

    /**
     * Two rides on one day are one day pass, not two.
     */
    public function testItCountsDayPassesByDistinctDay(): void
    {
        $this->givenRide(['ride_id' => 1, 'checkout_time' => '2026-01-01 08:00:00']);
        $this->givenRide(['ride_id' => 2, 'checkout_time' => '2026-01-01 18:00:00']);
        $this->givenRide(['ride_id' => 3, 'checkout_time' => '2026-01-02 08:00:00']);

        self::assertSame(10.0, $this->getJson('/rides/cost')['day_pass_equivalent_eur']);
    }

    /**
     * ISO week numbering, so a week straddling New Year counts once rather
     * than twice.
     */
    public function testItCountsWeekPassesByIsoWeek(): void
    {
        // Both of these fall in ISO week 2026-W01.
        $this->givenRide(['ride_id' => 1, 'checkout_time' => '2025-12-30 08:00:00']);
        $this->givenRide(['ride_id' => 2, 'checkout_time' => '2026-01-02 08:00:00']);

        self::assertSame(12.0, $this->getJson('/rides/cost')['week_pass_equivalent_eur']);
    }

    public function testItTreatsASingleDayHistoryAsOneDayLong(): void
    {
        $this->givenRide(['checkout_time' => '2026-01-01 08:00:00']);

        self::assertSame(1, $this->getJson('/rides/cost')['date_range_days']);
    }
}
