<?php

namespace App\DTOs;

use Carbon\Carbon;
use Spatie\LaravelData\Data;

class FlightDTO extends Data
{
    public function __construct(
        public string $flightNumber,
        public string $airline,
        public string $origin,
        public string $destination,
        public Carbon $departureAt,
        public Carbon $arrivalAt,
        public float $price,
        public string $currency,
        public int $seatsAvailable,
        public string $cabinClass,
        public string $rawHash,
    ) {}
}
