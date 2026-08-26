<?php

use App\Jobs\PollSupplierJob;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

if (app()->runningInConsole() && ! app()->runningUnitTests()) {
    try {
        if (Schema::hasTable('suppliers') && Schema::hasTable('routes')) {
            $suppliers = Supplier::query()->where('is_active', true)->get();
            $routes = Route::all();

            foreach ($suppliers as $supplier) {
                $interval = max(1, (int) $supplier->poll_interval_minutes);

                foreach ($routes as $route) {
                    $scheduledJob = Schedule::job(new PollSupplierJob($supplier, $route));

                    if ($interval >= 60) {
                        $scheduledJob->hourly();
                    } else {
                        $scheduledJob->cron("*/{$interval} * * * *");
                    }

                    $scheduledJob->withoutOverlapping();
                }
            }
        }
    } catch (\Throwable $exception) {
        Log::debug('Supplier polling schedules were not registered', [
            'message' => $exception->getMessage(),
        ]);
    }
}
