<?php

namespace Tests\Feature;

use App\Adapters\V16SupplierAdapter;
use App\Exceptions\SupplierRequestException;
use App\Models\Supplier;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class V16SupplierAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_requested_departure_date_and_normalizes_flights(): void
    {
        Http::fake([
            'supplier.test/*' => Http::response([
                'AvailableFlights' => [
                    [
                        'FlightNumber' => 'IR123',
                        'Airline' => 'Demo Air',
                        'DepartureDate' => '2026-09-10',
                        'DepartureTime' => '08:30',
                        'ArrivalDate' => '2026-09-10',
                        'ArrivalTime' => '10:00',
                        'Price' => 1250000,
                        'Currency' => 'IRR',
                        'Capacity' => 7,
                        'CabinClass' => 'Economy',
                    ],
                ],
            ], 200),
        ]);

        $supplier = Supplier::create([
            'name' => 'Demo Supplier',
            'slug' => 'demo',
            'base_url' => 'https://supplier.test/search',
            'timeout_seconds' => 5,
            'retry_attempts' => 1,
        ]);

        $adapter = new V16SupplierAdapter($supplier);
        $departureDate = CarbonImmutable::parse('2026-09-10');

        $flights = $adapter->fetchFlights('thr', 'mhd', $departureDate);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->url() === 'https://supplier.test/search'
                && $payload['Origin'] === 'THR'
                && $payload['Destination'] === 'MHD'
                && $payload['DepartureDate'] === '2026-09-10';
        });

        $this->assertCount(1, $flights);

        $flight = $flights->first();

        $this->assertSame('IR123', $flight->flightNumber);
        $this->assertSame('Demo Air', $flight->airline);
        $this->assertSame('THR', $flight->origin);
        $this->assertSame('MHD', $flight->destination);
        $this->assertSame(1250000.0, $flight->price);
        $this->assertSame(7, $flight->seatsAvailable);
    }

    public function test_it_throws_when_the_supplier_request_fails(): void
    {
        Http::fake([
            'supplier.test/*' => Http::response(['message' => 'temporarily unavailable'], 503),
        ]);

        $supplier = Supplier::create([
            'name' => 'Failing Supplier',
            'slug' => 'failing',
            'base_url' => 'https://supplier.test/search',
            'timeout_seconds' => 5,
            'retry_attempts' => 1,
        ]);

        $this->expectException(SupplierRequestException::class);

        (new V16SupplierAdapter($supplier))->fetchFlights(
            'THR',
            'MHD',
            CarbonImmutable::parse('2026-09-10')
        );
    }
}
