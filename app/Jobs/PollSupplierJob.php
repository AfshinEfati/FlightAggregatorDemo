<?php

namespace App\Jobs;

use App\Models\Route;
use App\Models\Supplier;
use App\Services\FlightSyncService;
use App\Services\SupplierRegistryService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PollSupplierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        public Supplier $supplier,
        public Route $route,
        public ?string $departureDate = null
    ) {
        $this->onQueue('supplier-polling');
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }

    /**
     * Prevent two workers from polling the same supplier/route/date concurrently.
     * Duplicate scheduled jobs can be discarded because the next schedule tick
     * will enqueue fresh work again.
     */
    public function middleware(): array
    {
        $date = $this->departureDate
            ?? CarbonImmutable::now()->addDay()->toDateString();

        $key = sprintf(
            'supplier:%s:route:%s:date:%s',
            $this->supplier->getKey(),
            $this->route->getKey(),
            $date,
        );

        return [
            (new WithoutOverlapping($key))
                ->dontRelease()
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(
        SupplierRegistryService $registry,
        FlightSyncService $syncService
    ): void {
        if (! $this->supplier->is_active) {
            return;
        }

        try {
            $date = $this->departureDate
                ? CarbonImmutable::createFromFormat('Y-m-d', $this->departureDate)->startOfDay()
                : CarbonImmutable::now()->addDay()->startOfDay();

            $adapter = $registry->resolve($this->supplier);
            $flights = $adapter->fetchFlights(
                $this->route->origin,
                $this->route->destination,
                $date
            );

            if ($flights->isNotEmpty()) {
                $syncService->sync($this->supplier, $this->route, $flights);
            }
        } catch (Throwable $exception) {
            Log::error('Failed to poll supplier', [
                'exception' => $exception->getMessage(),
                'supplier' => $this->supplier->id,
                'route' => $this->route->id,
                'departure_date' => $this->departureDate,
                'attempt' => $this->attempts(),
            ]);

            throw $exception;
        }
    }
}
