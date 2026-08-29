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

        if (! isset($data['AvailableFlights']) || ! is_array($data['AvailableFlights'])) {
            return collect();
        }

        return collect($data['AvailableFlights'])->map(function (array $flight) use ($origin, $destination) {
            $departureAt = Carbon::parse($flight['DepartureDate'].' '.$flight['DepartureTime']);
            $arrivalAt = Carbon::parse($flight['ArrivalDate'].' '.$flight['ArrivalTime']);
            $normalizedOrigin = strtoupper($origin);
            $normalizedDestination = strtoupper($destination);
            $flightNumber = strtoupper(trim((string) $flight['FlightNumber']));
            $cabinClass = trim((string) ($flight['CabinClass'] ?? 'Economy'));

            $rawHash = hash('sha256', implode('|', [
                (string) $this->supplier->id,
                $flightNumber,
                $normalizedOrigin,
                $normalizedDestination,
                $departureAt->toIso8601String(),
                strtoupper($cabinClass),
            ]));

            return new FlightDTO(
                flightNumber: $flightNumber,
                airline: $flight['Airline'] ?? 'Unknown',
                origin: $normalizedOrigin,
                destination: $normalizedDestination,
                departureAt: $departureAt,
                arrivalAt: $arrivalAt,
                price: (float) $flight['Price'],
                currency: $flight['Currency'] ?? 'IRR',
                seatsAvailable: (int) ($flight['Capacity'] ?? 0),
                cabinClass: $cabinClass,
                rawHash: $rawHash
            );
        });
    }
}
