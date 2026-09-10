<?php

declare(strict_types=1);

namespace App\Tests\Unit\Weather;

use App\Domain\Weather\Service\OpenMeteoWeatherService;
use App\Support\Coordinate;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenMeteoWeatherServiceTest extends TestCase
{
    /**
     * The archive answers a whole day in parallel columns, so the right hour
     * has to be found by its timestamp rather than assumed to be at an offset.
     */
    public function testItSelectsTheHourMatchingTheRequestedTime(): void
    {
        $service = self::serviceReturning(self::dayPayload());

        $observation = $service->getWeather(
            new Coordinate(51.21, 4.41),
            new DateTimeImmutable('2026-09-06 09:05:30', new DateTimeZone('UTC')),
        );

        self::assertSame(18.0, $observation->temperatureC);
        self::assertSame(3, $observation->weatherCode);
        self::assertSame('2026-09-06T09:00:00+00:00', $observation->observedAt->format('c'));
    }

    public function testItTranslatesTheUpstreamVariableNames(): void
    {
        $service = self::serviceReturning(self::dayPayload());

        $observation = $service->getWeather(
            new Coordinate(51.21, 4.41),
            new DateTimeImmutable('2026-09-06 09:05:30', new DateTimeZone('UTC')),
        );

        self::assertSame(17.1, $observation->apparentTemperatureC);
        self::assertSame(11.2, $observation->windSpeedKmh);
        self::assertSame(68.0, $observation->relativeHumidityPercent);
    }

    public function testItConvertsTheRequestedTimeToUtcBeforeMatching(): void
    {
        $service = self::serviceReturning(self::dayPayload());

        // 11:05 in Brussels is 09:05 UTC, so this must find the same hour.
        $observation = $service->getWeather(
            new Coordinate(51.21, 4.41),
            new DateTimeImmutable('2026-09-06 11:05:30', new DateTimeZone('Europe/Brussels')),
        );

        self::assertSame('2026-09-06T09:00:00+00:00', $observation->observedAt->format('c'));
    }

    public function testItFailsWhenTheArchiveReturnsAnError(): void
    {
        $service = self::serviceReturning(['error' => true, 'reason' => 'Out of range']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Out of range');

        $service->getWeather(
            new Coordinate(51.21, 4.41),
            new DateTimeImmutable('2026-09-06 09:05:30', new DateTimeZone('UTC')),
        );
    }

    public function testItFailsWhenTheRequestedHourIsMissing(): void
    {
        $service = self::serviceReturning(self::dayPayload());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('2026-09-06T23:00');

        $service->getWeather(
            new Coordinate(51.21, 4.41),
            new DateTimeImmutable('2026-09-06 23:30:00', new DateTimeZone('UTC')),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function dayPayload(): array
    {
        return [
            'hourly' => [
                'time' => ['2026-09-06T08:00', '2026-09-06T09:00', '2026-09-06T10:00'],
                'temperature_2m' => [16.5, 18.0, 19.4],
                'apparent_temperature' => [15.2, 17.1, 18.8],
                'precipitation' => [0.0, 0.0, 0.1],
                'rain' => [0.0, 0.0, 0.1],
                'snowfall' => [0.0, 0.0, 0.0],
                'cloud_cover' => [30.0, 42.0, 55.0],
                'wind_speed_10m' => [10.1, 11.2, 12.3],
                'wind_gusts_10m' => [22.0, 24.5, 26.0],
                'wind_direction_10m' => [200.0, 210.0, 220.0],
                'relative_humidity_2m' => [70.0, 68.0, 65.0],
                'weather_code' => [1, 3, 61],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function serviceReturning(array $payload): OpenMeteoWeatherService
    {
        return new OpenMeteoWeatherService(
            new MockHttpClient(new MockResponse((string) json_encode($payload))),
            'https://archive.example/v1/archive',
        );
    }
}
