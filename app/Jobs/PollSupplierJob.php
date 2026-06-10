<?php

namespace App\Jobs;

use App\Models\Route;
use App\Models\Supplier;
use App\Services\FlightSyncService;
use App\Services\SupplierRegistryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PollSupplierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public Supplier $supplier,
        public Route $route
    ) {
        $this->onQueue('supplier-polling');
    }

    public function handle(
        SupplierRegistryService $registry,
        FlightSyncService $syncService
    ): void {
        if (!$this->supplier->is_active) {
            return;
        }

        try {
            $adapter = $registry->resolve($this->supplier);
            $flights = $adapter->fetchFlights($this->route->origin, $this->route->destination);

            if ($flights->isNotEmpty()) {
                $syncService->sync($this->supplier, $this->route, $flights);
            }
        } catch (\Exception $e) {
            Log::error("Failed to poll supplier: {$this->supplier->name}", [
                'exception' => $e->getMessage(),
                'supplier' => $this->supplier->id,
                'route' => $this->route->id,
            ]);
            throw $e;
        }
    }
}
