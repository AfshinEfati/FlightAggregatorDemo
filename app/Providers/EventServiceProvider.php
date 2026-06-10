<?php

namespace App\Providers;

use App\Events\FlightDataUpdated;
use App\Listeners\InvalidateFlightCache;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        FlightDataUpdated::class => [
            InvalidateFlightCache::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
