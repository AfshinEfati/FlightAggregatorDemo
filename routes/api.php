<?php

use App\Http\Controllers\Api\FlightController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/flights', [FlightController::class, 'search']);

    Route::prefix('admin')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::patch('/suppliers/{supplier}', [SupplierController::class, 'update']);
        Route::post('/suppliers/{supplier}/poll', [SupplierController::class, 'poll']);
    });
});
