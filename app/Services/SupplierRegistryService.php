<?php

namespace App\Services;

use App\Adapters\V16SupplierAdapter;
use App\Contracts\FlightSupplierInterface;
use App\Models\Supplier;
use InvalidArgumentException;

class SupplierRegistryService
{
    public function resolve(Supplier $supplier): FlightSupplierInterface
    {
        // For this demo, all use the V16 format as requested.
        // In a real app, we might switch based on supplier slug or a 'type' field.
        return new V16SupplierAdapter($supplier);
    }
}
