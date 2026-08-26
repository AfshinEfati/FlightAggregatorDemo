# Flight Aggregator Demo

[![CI](https://github.com/AfshinEfati/FlightAggregatorDemo/actions/workflows/ci.yml/badge.svg)](https://github.com/AfshinEfati/FlightAggregatorDemo/actions/workflows/ci.yml)

A production-oriented Laravel backend that polls multiple flight suppliers asynchronously, normalizes heterogeneous supplier responses, stores unified flight data, and exposes a single search API.

This project focuses on backend architecture, queue-based integrations, caching, Dockerized infrastructure, and testable supplier adapters rather than UI concerns.

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
                              │
                              ▼
                       FlightDataUpdated
                              │
                              ▼
                     Cache Invalidation
                              │
                              ▼
Client ──► REST API ──► Flight Search Service ──► Redis / Database
```

## Engineering Highlights

- **Adapter pattern** isolates supplier-specific payloads behind a common contract.
- **Queue-based polling** keeps slow third-party integrations outside request/response cycles.
- **DTO normalization** provides one internal flight representation regardless of supplier format.
- **Transactional synchronization** uses `updateOrCreate` with stable raw hashes to make repeated syncs idempotent.
- **Versioned route cache invalidation** refreshes both general and date-specific search caches after supplier data changes.
- **Redis caching** reduces repeated database work for common searches.
- **Events and listeners** decouple synchronization from cache invalidation.
- **Docker Compose** provides PHP-FPM, Nginx, MySQL, Redis, Supervisor, and scheduler/worker support.
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

Start polling configured suppliers:

```bash
docker-compose exec app php artisan suppliers:poll
```

## API

### Search flights

```http
GET /api/v1/flights?origin=THR&destination=MHD
```

Optional date filter:

```http
GET /api/v1/flights?origin=THR&destination=MHD&date=2026-08-27
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

1. Calling the external supplier API.
2. Handling supplier-specific response structure.
3. Mapping results to `FlightDTO` objects.
4. Returning normalized data to the queue job.

`PollSupplierJob` then passes normalized flights to `FlightSyncService`, which persists them inside a database transaction and emits `FlightDataUpdated` after a successful sync.

## Caching Strategy

Searches are cached per route and optional departure date.

```text
flights:{origin}:{destination}:v{version}:{date?}
```

When fresh supplier data is synchronized, the route cache version is incremented. New requests immediately use a new namespace, so date-specific cached results cannot remain stale while old keys are allowed to expire naturally.

## Testing

Run the full test suite:

```bash
php artisan test
```

Run style checks:

```bash
vendor/bin/pint --test
```

The test suite covers API search behavior and route-level cache invalidation. CI executes these checks automatically on GitHub.

## Docker Services

| Service | Purpose |
| --- | --- |
| PHP 8.3-FPM | Laravel application runtime |
| Nginx | HTTP server |
| MySQL 8 | Persistent flight and supplier data |
| Redis 7 | Cache and queue backend |
| Supervisor | Queue worker process management |

## Project Structure

```text
app/
├── Adapters/        # Supplier-specific integrations
├── Contracts/       # Supplier interfaces
├── DTOs/            # Normalized data objects
├── Events/          # Domain/application events
├── Jobs/            # Queue polling jobs
├── Listeners/       # Cache invalidation listeners
├── Models/          # Eloquent models
└── Services/        # Search, sync, and supplier registry logic
```

## License

MIT
