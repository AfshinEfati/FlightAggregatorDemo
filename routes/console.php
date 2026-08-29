<?php

use App\Jobs\PollSupplierJob;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

$shouldRegisterSchedules = app()->runningInConsole() && app()->runningUnitTests() === false;

if ($shouldRegisterSchedules) {
    try {
        if (Schema::hasTable('suppliers') && Schema::hasTable('routes')) {
            $suppliers = Supplier::query()->where('is_active', true)->get();
            $routes = Route::all();

            foreach ($suppliers as $supplier) {
                $interval = max(1, intval($supplier->poll_interval_minutes));

                foreach ($routes as $route) {
                    Schedule::job(new PollSupplierJob($supplier, $route))
                        ->name("poll-supplier:{$supplier->id}:route:{$route->id}")
                        ->everyMinute()
                        ->when(function () use ($interval): bool {
                            $currentMinute = intdiv(now()->timestamp, 60);

                            return $currentMinute % $interval === 0;
                        });
                }
            }
        }
    } catch (Throwable $exception) {
        Log::debug('Supplier polling schedules were not registered', [
            'message' => $exception->getMessage(),
        ]);
    }
}
