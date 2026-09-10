# Velo Stats

Symfony app for tracking Velo Antwerp bike-share stations, ride history, routing and weather.

Ride history is loaded from a JSON export, station information from the public Velo Antwerp GBFS feed. Each ride is
then enriched in the background: the cycling distance between its two stations comes from the public OSRM routing
API, and the weather at its origin station and checkin time comes from the free Open-Meteo archive. The API serves
the combined data as JSON.

This is a port of the Laravel application of the same name, and serves byte-identical JSON on every endpoint.

## Setup

```
docker compose up -d --build
```

This starts five services:
- `app` – the API, served on port `8000`
- `redis` – transport backing the message queues
- `worker` – consumer for the `default` queue
- `worker-ride-distance` – consumer for the `ride_distance_checks` queue, one job at a time, so calls to the free
  routing API are never made concurrently
- `worker-ride-weather` – consumer for the `ride_weather_checks` queue, one job at a time, so calls to the free
  Open-Meteo API are never made concurrently

Every container runs the migrations on start, so no manual setup is needed.

Verify the app is up and running:

```
curl http://localhost:8000/_healthcheck
```

Should return a 200 OK response.

Stop everything with:

```
docker compose down
```

The project directory is bind-mounted into every container, so code edits are picked up without a rebuild.
Dependencies live in the image rather than the mount, so after changing `composer.json` rebuild and recreate:

```
docker compose up -d --build --force-recreate --renew-anon-volumes
```

### Loading data

A fresh database is empty. Populate it in this order:

```
docker compose exec app php bin/console stations:load
docker compose exec app php bin/console rides:load
docker compose exec app php bin/console rides:check-distances
docker compose exec app php bin/console rides:check-weather
```

The last two commands queue one message per ride and return immediately. The dedicated workers drain them one call
at a time, which takes a few minutes for a full ride history. Both are safe to re-run: a ride is only queued while
its `distance_checked_at` or `weather_checked_at` is still null, so a failed check is picked up next time and a
finished one is never repeated. `rides:check-weather --force` re-fetches every ride regardless.

## Endpoints

| Method | Path | Returns |
|---|---|---|
| GET | `/_healthcheck` | `{"message":"ok"}` |
| GET | `/rides` | Every ride, newest first, with its cached distance, derived speed and weather |
| GET | `/rides/summary` | Totals and averages across the whole history |
| GET | `/rides/cost` | What the subscription cost per ride, against day and week passes |
| GET | `/stations` | Every known station with its position |

None of them take a parameter.

## Configuration

Environment variables, set in `docker-compose.yml` and documented in `.env.example`:

| Variable | Default | Description |
|---|---|---|
| `DATABASE_URL` | `sqlite:///database/database.sqlite` | Doctrine connection string |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | Comma-separated origins allowed to call the API |
| `REDIS_HOST` | `127.0.0.1` | Redis host backing the transports |
| `REDIS_PORT` | `6379` | Redis port |
| `MESSENGER_TRANSPORT_DSN` | `redis://127.0.0.1:6379` | Base DSN the three transports extend |
| `RIDES_JSON_PATH` | `data/rides.json` | Path to the rides JSON export, relative to the project root |

The three upstream endpoints can be overridden with `VELO_ANTWERP_STATION_INFORMATION_URL`, `OSRM_BASE_URL` and
`OPEN_METEO_ARCHIVE_URL`.

## Production

`docker-compose.prod.yml` runs the shape this would actually be deployed in: nginx in front of a fixed PHP-FPM pool,
OPcache with JIT and timestamp checks off, no bind mounts, and the seeded SQLite database baked into the image.

```
docker compose -f docker-compose.prod.yml up -d --build
```

Because the database is inside the image, re-seeding means rebuilding. The container is capped at four CPUs and the
FPM pool at four children, matching the other velo-stats backends so the benchmark in `velo-stats-speedtest`
compares them on an equal share of the machine.

## Layout

Code is grouped by domain rather than by kind, so everything one part of the app needs sits together.

```
src/
  Domain/Rides/       ride history: the export reader, the list and its two read models
  Domain/Stations/    the station feed
  Domain/Routing/     cycling distances, cached per station pair
  Domain/Weather/     historical weather, cached per ride
  Health/             the health check
  Http/               cross-origin access
  Support/            JSON encoding, date formatting, rounding
```

Within a domain:

| Directory | Holds |
|---|---|
| `Entity` | Doctrine entities |
| `Repository` | every query against those entities, and nothing else |
| `Contract` | the interface an upstream service is used through |
| `Service` | the implementations, and the caches wrapping them |
| `RequestHandler` | one invokable class per endpoint, carrying its own route |
| `Normalizer` | mapping an entity to the JSON an endpoint returns |
| `ReadModel` | the shapes a read query assembles before serialisation |
| `Message`, `MessageHandler` | the background checks |
| `Command` | the console commands |
| `ValueObject`, `Enum` | the types that travel between them |

There are no controllers. Each endpoint is a single-action request handler with its `#[Route]` on it, so adding an
endpoint means adding one class and changing nothing else.

## Tests

```
php bin/phpunit
```

Two suites. `Unit` covers the pieces where a small mistake changes the API output: rounding, date formatting, JSON
encoding, the export parser and the three upstream clients. `Functional` boots the kernel against a throwaway
SQLite database and covers all five endpoints and both background checks. Neither touches the network or Redis.
