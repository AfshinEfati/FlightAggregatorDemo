<?php

namespace App\Contracts;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

interface FlightSupplierInterface
{
    /**
     * @return Collection<int, \App\DTOs\FlightDTO>
     */
    public function fetchFlights(
        string $origin,
        string $destination,
        ?CarbonInterface $departureDate = null
    ): Collection;

    public function getSupplierSlug(): string;
}
