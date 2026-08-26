<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Models\Route;
use App\Models\Supplier;
use App\Services\FlightSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightSearchCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_cache_invalidation_refreshes_date_specific_searches(): void
    {
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'slug' => 'test',
            'base_url' => 'https://example.com/api',
        ]);

        $route = Route::create([
            'origin' => 'THR',
            'destination' => 'MHD',
        ]);

        $date = now()->addDay()->toDateString();

        $this->createFlight($supplier, $route, 'FL100', $date, 'hash-100');

        $service = app(FlightSearchService::class);

        $firstSearch = $service->search([
            'origin' => 'thr',
            'destination' => 'mhd',
            'date' => $date,
        ]);

        $this->assertCount(1, $firstSearch);

        $this->createFlight($supplier, $route, 'FL200', $date, 'hash-200');

        $cachedSearch = $service->search([
            'origin' => 'THR',
            'destination' => 'MHD',
            'date' => $date,
        ]);

        $this->assertCount(1, $cachedSearch);

        $service->clearCache('thr', 'mhd');

        $refreshedSearch = $service->search([
            'origin' => 'THR',
            'destination' => 'MHD',
            'date' => $date,
        ]);

        $this->assertCount(2, $refreshedSearch);
    }

    private function createFlight(
        Supplier $supplier,
        Route $route,
        string $flightNumber,
        string $date,
        string $rawHash
    ): Flight {
        return Flight::create([
            'supplier_id' => $supplier->id,
            'route_id' => $route->id,
            'flight_number' => $flightNumber,
            'airline' => 'Test Air',
            'origin' => $route->origin,
            'destination' => $route->destination,
            'departure_at' => "{$date} 08:00:00",
            'arrival_at' => "{$date} 10:00:00",
            'price' => 1000000,
            'currency' => 'IRR',
            'seats_available' => 10,
            'cabin_class' => 'Economy',
            'raw_hash' => $rawHash,
            'last_synced_at' => now(),
        ]);
    }
}
