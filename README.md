# ✈️ Flight Aggregator Demo

This is a Laravel-based flight aggregator that polls multiple Iranian airline supplier APIs, normalizes their data, and provides a unified search interface.

## 🏗️ Architecture

- **Adapter Pattern**: Each supplier has a dedicated adapter to handle its specific API response format.
- **Queue/Jobs**: Polling is done asynchronously via Laravel Queues.
- **Cache-Aside**: Search results are cached in Redis to improve performance.
- **Observer/Events**: Cache is automatically invalidated when new data is synced from a supplier.

## 🚀 Quick Start

1.  **Clone the repository**:
    ```bash
    git clone <repository-url>
    cd flight-aggregator
    ```

2.  **Setup Environment**:
    ```bash
    cp .env.example .env
    ```

3.  **Start Docker Containers**:
    ```bash
    docker-compose up -d
    ```

4.  **Initialize Application**:
    ```bash
    docker-compose exec app php artisan key:generate
    docker-compose exec app php artisan migrate --seed
    ```

## 🔌 API Endpoints

- **Search Flights**: `GET /api/v1/flights?origin=THR&destination=MHD`
- **List Suppliers**: `GET /api/v1/admin/suppliers`
- **Update Supplier**: `PATCH /api/v1/admin/suppliers/{id}`
- **Manual Poll**: `POST /api/v1/admin/suppliers/{id}/poll`

## 🛠️ Development

- **Run Tests**: `php artisan test`
- **Swagger Docs**: Visit `http://localhost:8000/api/documentation` (after generating)
- **Generate Docs**: `php artisan l5-swagger:generate`
- **Manual Polling**: `php artisan suppliers:poll`

## 📦 Docker Services

- **PHP 8.3-FPM**: Main application logic.
- **Nginx**: Web server.
- **MySQL 8.0**: Persistent storage for flights and configuration.
- **Redis 7**: Caching and queue management.
- **Supervisor**: Manages queue workers and the scheduler.
