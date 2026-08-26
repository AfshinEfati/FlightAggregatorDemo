<?php

namespace App\Adapters;

use App\DTOs\FlightDTO;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class V16SupplierAdapter extends BaseSupplierAdapter
{
    public function fetchFlights(
        string $origin,
        string $destination,
        ?CarbonInterface $departureDate = null
    ): Collection {
        $departureDate ??= now()->addDay();

        $data = $this->makeRequest($origin, $destination, $departureDate);

        if (!isset($data['AvailableFlights']) || !is_array($data['AvailableFlights'])) {
            return collect();
        }

        return collect($data['AvailableFlights'])->map(function (array $flight) use ($origin, $destination) {
            $departureAt = Carbon::parse($flight['DepartureDate'].' '.$flight['DepartureTime']);
            $arrivalAt = Carbon::parse($flight['ArrivalDate'].' '.$flight['ArrivalTime']);

            $rawHash = md5(
                $this->supplier->id.$flight['FlightNumber'].$departureAt->toIso8601String()
            );

            return new FlightDTO(
                flightNumber: $flight['FlightNumber'],
                airline: $flight['Airline'] ?? 'Unknown',
                origin: strtoupper($origin),
                destination: strtoupper($destination),
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
