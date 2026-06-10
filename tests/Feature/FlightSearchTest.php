<?php

namespace Tests\Feature;

use App\Models\Flight;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlightSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_flights_from_search(): void
    {
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'slug' => 'test',
            'base_url' => 'https://example.com/api',
        ]);

        $route = Route::create(['origin' => 'THR', 'destination' => 'MHD']);

        Flight::create([
            'supplier_id' => $supplier->id,
            'route_id' => $route->id,
            'flight_number' => 'FL123',
            'airline' => 'Test Air',
            'origin' => 'THR',
            'destination' => 'MHD',
            'departure_at' => now()->addDay(),
            'arrival_at' => now()->addDay()->addHours(2),
            'price' => 1000000,
            'currency' => 'IRR',
            'seats_available' => 10,
            'cabin_class' => 'Economy',
            'raw_hash' => 'hash123',
            'last_synced_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/flights?origin=THR&destination=MHD');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.flight_number', 'FL123');
    }
}
