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
                    $this->flightPayload(),
                ],
            ], 200),
        ]);

        $supplier = $this->createSupplier();
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

    public function test_hash_is_stable_when_mutable_inventory_fields_change(): void
    {
        Http::fake([
            'supplier.test/*' => Http::sequence()
                ->push([
                    'AvailableFlights' => [
                        $this->flightPayload([
                            'Price' => 1250000,
                            'Capacity' => 7,
                        ]),
                    ],
                ])
                ->push([
                    'AvailableFlights' => [
                        $this->flightPayload([
                            'Price' => 1450000,
                            'Capacity' => 2,
                        ]),
                    ],
                ]),
        ]);

        $adapter = new V16SupplierAdapter($this->createSupplier());
        $departureDate = CarbonImmutable::parse('2026-09-10');

        $first = $adapter->fetchFlights('THR', 'MHD', $departureDate)->first();
        $second = $adapter->fetchFlights('THR', 'MHD', $departureDate)->first();

        $this->assertSame($first->rawHash, $second->rawHash);
        $this->assertNotSame($first->price, $second->price);
        $this->assertNotSame($first->seatsAvailable, $second->seatsAvailable);
    }

    public function test_hash_distinguishes_cabin_variants_of_the_same_flight(): void
    {
        Http::fake([
            'supplier.test/*' => Http::response([
                'AvailableFlights' => [
                    $this->flightPayload(['CabinClass' => 'Economy']),
                    $this->flightPayload(['CabinClass' => 'Business']),
                ],
            ], 200),
        ]);

        $adapter = new V16SupplierAdapter($this->createSupplier());
        $flights = $adapter->fetchFlights(
            'THR',
            'MHD',
            CarbonImmutable::parse('2026-09-10')
        );

        $this->assertCount(2, $flights);
        $this->assertNotSame($flights[0]->rawHash, $flights[1]->rawHash);
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

    private function createSupplier(): Supplier
    {
        return Supplier::create([
            'name' => 'Demo Supplier',
            'slug' => 'demo',
            'base_url' => 'https://supplier.test/search',
            'timeout_seconds' => 5,
            'retry_attempts' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function flightPayload(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}
