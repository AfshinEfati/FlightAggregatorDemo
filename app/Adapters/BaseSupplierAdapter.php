<?php

namespace App\Adapters;

use App\Contracts\FlightSupplierInterface;
use App\Exceptions\SupplierRequestException;
use App\Models\Supplier;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    protected function makeRequest(
        string $origin,
        string $destination,
        CarbonInterface $departureDate
    ): array {
        try {
            $response = Http::acceptJson()
                ->timeout($this->supplier->timeout_seconds ?? 30)
                ->retry($this->supplier->retry_attempts ?? 3, 100)
                ->post($this->supplier->base_url, [
                    'Origin' => strtoupper($origin),
                    'Destination' => strtoupper($destination),
                    'DepartureDate' => $departureDate->format('Y-m-d'),
                ])
                ->throw();

            return $response->json() ?? [];
        } catch (Throwable $exception) {
            Log::warning('Supplier request failed', [
                'supplier' => $this->supplier->slug,
                'origin' => strtoupper($origin),
                'destination' => strtoupper($destination),
                'departure_date' => $departureDate->format('Y-m-d'),
                'message' => $exception->getMessage(),
            ]);

            throw SupplierRequestException::forSupplier(
                $this->supplier->slug,
                $exception
            );
        }
    }
}
