<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier' => $this->supplier->name,
            'flight_number' => $this->flight_number,
            'airline' => $this->airline,
            'origin' => $this->origin,
            'destination' => $this->destination,
            'departure_at' => $this->departure_at->toDateTimeString(),
            'arrival_at' => $this->arrival_at->toDateTimeString(),
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'seats_available' => $this->seats_available,
            'cabin_class' => $this->cabin_class,
        ];
    }
}
