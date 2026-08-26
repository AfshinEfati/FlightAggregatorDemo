<?php

namespace App\Services;

use App\Adapters\V16SupplierAdapter;
use App\Contracts\FlightSupplierInterface;
use App\Models\Supplier;

class SupplierRegistryService
{
    public function resolve(Supplier $supplier): FlightSupplierInterface
    {
        return new V16SupplierAdapter($supplier);
    }
}
