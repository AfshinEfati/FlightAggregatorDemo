<?php

namespace App\Contracts;

use App\Models\Supplier;
use Illuminate\Support\Collection;

interface FlightSupplierInterface
{
    /**
     * @return Collection<int, \App\DTOs\FlightDTO>
     */
    public function fetchFlights(string $origin, string $destination): Collection;

    public function getSupplierSlug(): string;
}
