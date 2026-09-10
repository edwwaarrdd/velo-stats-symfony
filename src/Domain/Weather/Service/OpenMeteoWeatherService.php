<?php

declare(strict_types=1);

namespace App\Domain\Weather\Service;

use App\Domain\Weather\Contract\WeatherService;
use App\Domain\Weather\ValueObject\WeatherObservation;
use App\Support\Coordinate;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class OpenMeteoWeatherService implements WeatherService
{
    public const array HOURLY_VARIABLES = [
        'temperature_2m',
        'apparent_temperature',
        'precipitation',
        'rain',
        'snowfall',
        'cloud_cover',
        'wind_speed_10m',
        'wind_gusts_10m',
        'wind_direction_10m',
        'relative_humidity_2m',
        'weather_code',
    ];

    public function __construct(
        private HttpClientInterface $http,
        private string $archiveUrl,
    ) {
    }

    public function getWeather(Coordinate $location, DateTimeInterface $at): WeatherObservation
    {
        $at = DateTimeImmutable::createFromInterface($at)->setTimezone(new DateTimeZone('UTC'));
        $date = $at->format('Y-m-d');

        // The archive is queried a whole day at a time and the hour is picked
        // out below, because it has no endpoint for a single hour.
        $payload = $this->http
            ->request('GET', $this->archiveUrl, [
                'query' => [
                    'latitude' => $location->lat,
                    'longitude' => $location->lon,
                    'start_date' => $date,
                    'end_date' => $date,
                    'hourly' => implode(',', self::HOURLY_VARIABLES),
                    'timezone' => 'UTC',
                ],
                'timeout' => 10,
                'max_duration' => 10,
            ])
            ->toArray();

        if (!isset($payload['hourly'])) {
            throw new RuntimeException('Open-Meteo request failed: '.($payload['reason'] ?? json_encode($payload)));
        }

        $targetHour = $at->format('Y-m-d\TH:00');
        $index = array_search($targetHour, $payload['hourly']['time'], strict: true);

        if (false === $index) {
            throw new RuntimeException("Open-Meteo response has no observation for {$targetHour}.");
        }

        return WeatherObservation::fromOpenMeteoHourly($payload['hourly'], $index);
    }
}
