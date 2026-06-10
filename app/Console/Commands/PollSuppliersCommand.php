<?php

namespace App\Console\Commands;

use App\Jobs\PollSupplierJob;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Console\Command;

class PollSuppliersCommand extends Command
{
    protected $signature = 'suppliers:poll {supplier? : The slug of the supplier}';
    protected $description = 'Manually trigger supplier polling';

    public function handle(): int
    {
        $supplierSlug = $this->argument('supplier');

        $suppliers = $supplierSlug
            ? Supplier::where('slug', $supplierSlug)->where('is_active', true)->get()
            : Supplier::where('is_active', true)->get();

        if ($suppliers->isEmpty()) {
            $this->error("No active suppliers found.");
            return 1;
        }

        $routes = Route::all();

        if ($routes->isEmpty()) {
            $this->error("No routes found to poll.");
            return 1;
        }

        foreach ($suppliers as $supplier) {
            foreach ($routes as $route) {
                $this->info("Dispatching poll job for {$supplier->name} on route {$route->origin}-{$route->destination}");
                PollSupplierJob::dispatch($supplier, $route);
            }
        }

        $this->info("All jobs dispatched.");
        return 0;
    }
}
