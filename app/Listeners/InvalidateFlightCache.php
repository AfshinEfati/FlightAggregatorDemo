<?php

namespace App\Listeners;

use App\Events\FlightDataUpdated;
use App\Services\FlightSearchService;

class InvalidateFlightCache
{
    public function __construct(
        protected FlightSearchService $searchService
    ) {}

    public function handle(FlightDataUpdated $event): void
    {
        $this->searchService->clearCache(
            $event->route->origin,
            $event->route->destination
        );
    }
}
