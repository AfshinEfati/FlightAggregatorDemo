<?php

namespace App\Adapters;

use App\DTOs\FlightDTO;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class V16SupplierAdapter extends BaseSupplierAdapter
{
    public function fetchFlights(string $origin, string $destination): Collection
    {
        $data = $this->makeRequest($origin, $destination);

        if (!$data || !isset($data['AvailableFlights'])) {
            return collect();
        }

        return collect($data['AvailableFlights'])->map(function ($flight) use ($origin, $destination) {
            $departureAt = Carbon::parse($flight['DepartureDate'] . ' ' . $flight['DepartureTime']);
            $arrivalAt = Carbon::parse($flight['ArrivalDate'] . ' ' . $flight['ArrivalTime']);
            
            $rawHash = md5($this->supplier->id . $flight['FlightNumber'] . $departureAt->toIso8601String());

            return new FlightDTO(
                flightNumber: $flight['FlightNumber'],
                airline: $flight['Airline'] ?? 'Unknown',
                origin: $origin,
                destination: $destination,
                departureAt: $departureAt,
                arrivalAt: $arrivalAt,
                price: (float) $flight['Price'],
                currency: $flight['Currency'] ?? 'IRR',
                seatsAvailable: (int) ($flight['Capacity'] ?? 0),
                cabinClass: $flight['CabinClass'] ?? 'Economy',
                rawHash: $rawHash
            );
        });
    }
}
