<?php

namespace App\Adapters;

use App\Contracts\FlightSupplierInterface;
use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseSupplierAdapter implements FlightSupplierInterface
{
    protected Supplier $supplier;

    public function __construct(Supplier $supplier)
    {
        $this->supplier = $supplier;
    }

    public function getSupplierSlug(): string
    {
        return $this->supplier->slug;
    }

    protected function makeRequest(string $origin, string $destination)
    {
        try {
            $response = Http::timeout($this->supplier->timeout_seconds ?? 30)
                ->retry($this->supplier->retry_attempts ?? 3, 100)
                ->post($this->supplier->base_url, [
                    'Origin' => $origin,
                    'Destination' => $destination,
                    'DepartureDate' => now()->addDay()->format('Y-m-d'), // Example
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Supplier {$this->supplier->slug} request failed", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error("Supplier {$this->supplier->slug} exception", [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
