<?php

namespace App\Services;

use App\DTOs\FlightDTO;
use App\Events\FlightDataUpdated;
use App\Models\Flight;
use App\Models\Route;
use App\Models\Supplier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FlightSyncService
{
    /**
     * @param  Collection<int, FlightDTO>  $flights
     */
    public function sync(Supplier $supplier, Route $route, Collection $flights): void
    {
        DB::transaction(function () use ($supplier, $route, $flights) {
            foreach ($flights as $dto) {
                Flight::updateOrCreate(
                    ['raw_hash' => $dto->rawHash],
                    [
                        'supplier_id' => $supplier->id,
                        'route_id' => $route->id,
                        'flight_number' => $dto->flightNumber,
                        'airline' => $dto->airline,
                        'origin' => $dto->origin,
                        'destination' => $dto->destination,
                        'departure_at' => $dto->departureAt,
                        'arrival_at' => $dto->arrivalAt,
                        'price' => $dto->price,
                        'currency' => $dto->currency,
                        'seats_available' => $dto->seatsAvailable,
                        'cabin_class' => $dto->cabinClass,
                        'last_synced_at' => now(),
                    ]
                );
            }

            $supplier->update(['updated_at' => now()]);
        });

        event(new FlightDataUpdated($supplier, $route));
    }
}
