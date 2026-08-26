# Flight Aggregator Demo

[![CI](https://github.com/AfshinEfati/FlightAggregatorDemo/actions/workflows/ci.yml/badge.svg)](https://github.com/AfshinEfati/FlightAggregatorDemo/actions/workflows/ci.yml)

A production-oriented Laravel backend that polls multiple flight suppliers asynchronously, normalizes heterogeneous supplier responses, stores unified flight data, and exposes a single search API.

This project focuses on backend architecture, queue-based third-party integrations, caching, failure handling, Dockerized infrastructure, and testable supplier adapters rather than UI concerns.

## Architecture

```text
Supplier APIs
     │
     ▼
Supplier Adapters
     │ normalize
     ▼
 Flight DTOs
     │
     ▼
Queued Polling Jobs ──► Flight Sync Service ──► MySQL
      │                       │
      │ retry/backoff         ▼
      │                FlightDataUpdated
      │                       │
      │                       ▼
      └──────────────► Cache Invalidation
                              │
                              ▼
Client ──► REST API ──► Flight Search Service ──► Redis / Database
```

## Engineering Highlights

- **Adapter pattern** isolates supplier-specific payloads behind a common contract.
- **Explicit departure-date polling** allows scheduled/default polling as well as manual date-specific synchronization.
- **Queue-based polling** keeps slow third-party integrations outside request/response cycles.
- **Retry and backoff** preserve supplier failures as exceptions so queue retries can recover from temporary outages.
- **Per-supplier schedules** honor each supplier's `poll_interval_minutes` instead of using one global polling frequency.
- **Overlap protection** prevents duplicate scheduled polling jobs from running concurrently for the same scheduled task.
- **DTO normalization** provides one internal flight representation regardless of supplier format.
- **Transactional synchronization** uses `updateOrCreate` with stable raw hashes to make repeated syncs idempotent.
- **Versioned route cache invalidation** refreshes both general and date-specific search caches after supplier data changes.
- **Redis caching** reduces repeated database work for common searches.
- **Events and listeners** decouple synchronization from cache invalidation.
- **Docker Compose** provides PHP-FPM, Nginx, MySQL, Redis, Supervisor, queue workers, and the Laravel scheduler.
- **OpenAPI / Swagger** documents the HTTP API.
- **Automated CI** runs Laravel tests and Pint style checks on pushes and pull requests.

## Stack

- PHP 8.3
- Laravel 13
- MySQL 8
- Redis 7
- Docker / Docker Compose
- Nginx
- Supervisor
- PHPUnit
- Laravel Pint
- L5 Swagger / OpenAPI

## Quick Start

```bash
git clone https://github.com/AfshinEfati/FlightAggregatorDemo.git
cd FlightAggregatorDemo
cp .env.example .env
docker-compose up -d --build
```

Initialize the application:

```bash
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate --seed
```

The `app` container runs PHP-FPM, queue workers, and `schedule:work` under Supervisor.

## Supplier Polling

Poll all active suppliers for all configured routes. If no date is supplied, tomorrow is used:

```bash
docker-compose exec app php artisan suppliers:poll
```

Poll a specific supplier:

```bash
docker-compose exec app php artisan suppliers:poll sepehr
```

Poll a specific departure date:

```bash
docker-compose exec app php artisan suppliers:poll --date=2026-09-10
```

Or combine both:

```bash
docker-compose exec app php artisan suppliers:poll sepehr --date=2026-09-10
```

Each supplier stores operational settings in the database, including:

- `poll_interval_minutes`
- `timeout_seconds`
- `retry_attempts`
- `is_active`

Scheduled polling reads `poll_interval_minutes` per supplier and dispatches jobs to the `supplier-polling` queue. Failed supplier HTTP calls are surfaced as `SupplierRequestException` instances so the queued job can retry using backoff delays.

## API

### Search flights

```http
GET /api/v1/flights?origin=THR&destination=MHD
```

Optional date filter:

```http
GET /api/v1/flights?origin=THR&destination=MHD&date=2026-09-10
```

### Supplier administration

```http
GET   /api/v1/admin/suppliers
PATCH /api/v1/admin/suppliers/{id}
POST  /api/v1/admin/suppliers/{id}/poll
```

Swagger documentation is available after generating it:

```bash
php artisan l5-swagger:generate
```

Then open:

```text
/api/documentation
```

## Supplier Flow

Each supplier is resolved through `SupplierRegistryService` and exposed through `FlightSupplierInterface`.

A supplier adapter is responsible for:

1. Calling the external supplier API for a route and departure date.
2. Applying supplier timeout/retry settings.
3. Preserving HTTP failures as domain-level supplier exceptions.
4. Handling supplier-specific response structure.
5. Mapping results to `FlightDTO` objects.
6. Returning normalized data to the queue job.

`PollSupplierJob` then passes normalized flights to `FlightSyncService`, which persists them inside a database transaction and emits `FlightDataUpdated` after a successful sync.

## Caching Strategy

Searches are cached per route and optional departure date.

```text
flights:{origin}:{destination}:v{version}:{date?}
```

When fresh supplier data is synchronized, the route cache version is incremented. New requests immediately use a new namespace, so date-specific cached results cannot remain stale while old keys are allowed to expire naturally.

## Failure Handling

Temporary supplier failures are intentionally different from an empty flight result.

```text
HTTP timeout / 5xx
      │
      ▼
SupplierRequestException
      │
      ▼
PollSupplierJob fails
      │
      ▼
Queue retry + backoff
```

This avoids silently treating an unavailable supplier as a valid response with zero flights.

## Testing

Run the full test suite:

```bash
php artisan test
```

Run style checks:

```bash
vendor/bin/pint --test
```

The test suite covers:

- flight search API behavior
- general and date-specific cache invalidation
- supplier response normalization
- requested departure-date propagation
- supplier HTTP failure handling
- CLI departure-date validation

CI executes the test suite and Pint checks automatically on GitHub pushes and pull requests.

## Docker Services

| Service | Purpose |
| --- | --- |
| PHP 8.3-FPM | Laravel application runtime |
| Nginx | HTTP server |
| MySQL 8 | Persistent flight and supplier data |
| Redis 7 | Cache and queue backend |
| Supervisor | PHP-FPM, queue workers, and scheduler process management |

## Project Structure

```text
app/
├── Adapters/        # Supplier-specific integrations
├── Contracts/       # Supplier interfaces
├── DTOs/            # Normalized data objects
├── Events/          # Domain/application events
├── Exceptions/      # Supplier/integration failures
├── Jobs/            # Queue polling jobs
├── Listeners/       # Cache invalidation listeners
├── Models/          # Eloquent models
└── Services/        # Search, sync, and supplier registry logic
```

## License

MIT
