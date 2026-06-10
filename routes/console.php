<?php

use App\Models\Supplier;
use App\Models\Route;
use App\Jobs\PollSupplierJob;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// Dynamic: reads all active suppliers from DB, schedules each independently
if (app()->runningInConsole() && !app()->runningUnitTests()) {
    try {
        if (Schema::hasTable('suppliers') && Schema::hasTable('routes')) {
            $suppliers = Supplier::where('is_active', true)->get();
            $routes = Route::all();

            foreach ($suppliers as $supplier) {
                foreach ($routes as $route) {
                    Schedule::job(new PollSupplierJob($supplier, $route))
                        ->everyTwoMinutes(); 
                }
            }
        }
    } catch (\Exception $e) {
        // Migration might not have run yet
    }
}
