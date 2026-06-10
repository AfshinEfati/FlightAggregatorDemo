<?php

namespace Tests\Unit;

use App\Adapters\V16SupplierAdapter;
use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SupplierAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_v16_response_to_flight_dtos(): void
    {
        $supplier = Supplier::create([
            'name' => 'Test Supplier',
            'slug' => 'test',
            'base_url' => 'https://example.com/api',
            'poll_interval_minutes' => 10,
        ]);

        Http::fake([
            'https://example.com/api' => Http::response([
                'AvailableFlights' => [
                    [
                        'FlightNumber' => 'FL123',
                        'Airline' => 'Test Air',
                        'DepartureDate' => '2026-06-10',
                        'DepartureTime' => '10:00',
                        'ArrivalDate' => '2026-06-10',
                        'ArrivalTime' => '12:00',
                        'Price' => 1000000,
                        'Capacity' => 10,
                        'CabinClass' => 'Economy',
                    ]
                ]
            ], 200)
        ]);

        $adapter = new V16SupplierAdapter($supplier);
        $flights = $adapter->fetchFlights('THR', 'MHD');

        $this->assertCount(1, $flights);
        $this->assertEquals('FL123', $flights->first()->flightNumber);
        $this->assertEquals(1000000, $flights->first()->price);
    }
}
