<?php

namespace App\Console\Commands;

use App\Jobs\PollSupplierJob;
use App\Models\Route;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PollSuppliersCommand extends Command
{
    protected $signature = 'suppliers:poll
        {supplier? : The slug of the supplier}
        {--date= : Departure date in Y-m-d format; defaults to tomorrow}';

    protected $description = 'Dispatch flight polling jobs for active suppliers and routes';

    public function handle(): int
    {
        $supplierSlug = $this->argument('supplier');
        $departureDate = $this->option('date');

        if ($departureDate && ! $this->isValidDate($departureDate)) {
            $this->error('The --date option must use the Y-m-d format.');

            return self::FAILURE;
        }

        $suppliers = $supplierSlug
            ? Supplier::where('slug', $supplierSlug)->where('is_active', true)->get()
            : Supplier::where('is_active', true)->get();

        if ($suppliers->isEmpty()) {
            $this->error('No active suppliers found.');

            return self::FAILURE;
        }

        $routes = Route::all();

        if ($routes->isEmpty()) {
            $this->error('No routes found to poll.');

            return self::FAILURE;
        }

        $effectiveDate = $departureDate ?: now()->addDay()->format('Y-m-d');

        foreach ($suppliers as $supplier) {
            foreach ($routes as $route) {
                $this->info(
                    "Dispatching {$supplier->name} for {$route->origin}-{$route->destination} on {$effectiveDate}"
                );

                PollSupplierJob::dispatch($supplier, $route, $effectiveDate);
            }
        }

        $this->info('All supplier polling jobs dispatched.');

        return self::SUCCESS;
    }

    private function isValidDate(string $date): bool
    {
        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date)->format('Y-m-d') === $date;
        } catch (\Throwable) {
            return false;
        }
    }
}
