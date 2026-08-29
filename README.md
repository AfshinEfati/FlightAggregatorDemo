# Flight Aggregator Demo

[![CI](https://github.com/AfshinEfati/FlightAggregatorDemo/actions/workflows/ci.yml/badge.svg)](https://github.com/AfshinEfati/FlightAggregatorDemo/actions/workflows/ci.yml)

A production-oriented **Laravel backend** that integrates multiple flight suppliers, polls them asynchronously, normalizes different supplier payloads into a shared domain model, stores unified flight data, and exposes a single search API.

The project is designed to demonstrate practical backend concerns found in integration-heavy systems: **queues, retries, idempotent synchronization, caching, failure isolation, scheduled jobs, API documentation, automated testing, and Dockerized infrastructure**.

## At a Glance

- Multiple external suppliers behind a shared adapter contract
- Queue-based supplier polling outside the HTTP request lifecycle
- Configurable timeout, retry, backoff, and polling intervals
- Job-level overlap protection for supplier/route/date polling
- DTO-based normalization into one internal flight representation
- Transactional and idempotent synchronization
- Redis-backed search caching with versioned invalidation
- Events/listeners for decoupled cache invalidation
- Separate PHP-FPM, queue-worker, and scheduler containers
- Runtime health checks for the HTTP and data-service path
- REST API with OpenAPI / Swagger documentation
- Automated PHPUnit, Pint, and Docker validation through GitHub Actions

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
Client ──► Nginx ──► REST API ──► Flight Search Service ──► Redis / Database
```

Runtime processes are isolated into independent Compose services:

```text
nginx ──► app (PHP-FPM)
            │
            ├── Redis
            └── MySQL

queue-worker ──► Redis / Supplier APIs / MySQL
scheduler    ──► dispatches supplier polling jobs
```

## Key Design Decisions

### Supplier isolation

Supplier-specific payloads are isolated behind `FlightSupplierInterface` and adapter implementations. The rest of the application works with normalized `FlightDTO` objects instead of depending on external response formats.

### Asynchronous polling

Supplier calls are dispatched to the `supplier-polling` queue. Slow or temporarily unavailable third-party APIs therefore do not block normal API requests.

Each supplier can define its own:

- `poll_interval_minutes`
- `timeout_seconds`
- `retry_attempts`
- `is_active`

`PollSupplierJob` applies a queue-level overlap lock keyed by **supplier + route + departure date**. This prevents two workers from polling the same logical workload concurrently while still allowing different suppliers, routes, and dates to run in parallel.

### Failure handling and retries

HTTP timeouts and failed supplier responses are surfaced as `SupplierRequestException` instances rather than being converted into an empty result set.

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

This keeps a real "zero flights" response distinct from an unavailable supplier.

### Idempotent synchronization

`FlightSyncService` persists normalized flights inside a database transaction and uses `updateOrCreate` with a stable identity hash.

The hash includes the supplier, flight number, origin, destination, departure time, and cabin class. Mutable inventory fields such as **price** and **seat availability** are intentionally excluded, so later polls update the same flight record instead of creating duplicates. Cabin variants remain distinct.

### Cache invalidation

Search results are cached per route and optional departure date.

```text
flights:{origin}:{destination}:v{version}:{date?}
```

After fresh supplier data is synchronized, the route cache version is incremented. New requests immediately move to the new namespace while old keys expire naturally.

### Process isolation

PHP-FPM, queue processing, and scheduling run as separate Docker Compose services rather than sharing one process supervisor. Each process has its own lifecycle and restart behavior, and queue workers can be scaled independently.

## Tech Stack

| Area | Technology |
| --- | --- |
| Backend | PHP 8.3, Laravel 13 |
| Database | MySQL 8 |
| Cache / Queue | Redis 7 |
| Web Server | Nginx |
| Runtime | PHP-FPM, Laravel Queue Worker, Laravel Scheduler |
| Infrastructure | Docker, Docker Compose |
| Testing | PHPUnit |
| Code Style | Laravel Pint |
| API Documentation | L5 Swagger / OpenAPI |
| CI | GitHub Actions |

## Quick Start

```bash
git clone https://github.com/AfshinEfati/FlightAggregatorDemo.git
cd FlightAggregatorDemo
cp .env.example .env
```

Build the application image and initialize Laravel:

```bash
docker compose build
docker compose run --rm app composer install
docker compose run --rm app php artisan key:generate
docker compose run --rm app php artisan migrate --seed
```

Start the runtime services:

```bash
docker compose up -d
```

The API is available at:

```text
http://localhost:8000
```

To run more queue workers:

```bash
docker compose up -d --scale queue-worker=2
```

Because the worker service does not use a fixed `container_name`, Compose can scale it horizontally.

## Supplier Polling

Poll all active suppliers for all configured routes. If no date is supplied, tomorrow is used:

```bash
docker compose exec app php artisan suppliers:poll
```

Poll a specific supplier:

```bash
docker compose exec app php artisan suppliers:poll sepehr
```

Poll a specific departure date:

```bash
docker compose exec app php artisan suppliers:poll --date=2026-09-10
```

Or combine both:

```bash
docker compose exec app php artisan suppliers:poll sepehr --date=2026-09-10
```

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

Generate Swagger documentation:

```bash
docker compose exec app php artisan l5-swagger:generate
```

Then open:

```text
http://localhost:8000/api/documentation
```

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
- stable identity hashes across price/capacity changes
- distinct identity hashes for cabin variants
- supplier polling overlap-lock identity

GitHub Actions validates Composer metadata, restores a Composer dependency cache, runs PHPUnit and Pint, validates `docker compose config`, and builds the application image on pushes and pull requests.

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

## What This Project Demonstrates

This repository is intentionally backend-focused. It shows how I approach third-party integrations and asynchronous workflows in Laravel: keeping external systems isolated, making repeated synchronization safe, preventing duplicate concurrent work, distinguishing failures from valid empty responses, isolating runtime processes, keeping cached data coherent, and covering critical behavior with automated tests.

## License

MIT
