<?php

namespace App\Services;

use App\Models\Flight;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class FlightSearchService
{
    public function search(array $params): Collection
    {
        $origin = $params['origin'];
        $destination = $params['destination'];
        $date = $params['date'] ?? null;

        $cacheKey = "flights:{$origin}:{$destination}" . ($date ? ":{$date}" : "");
        $ttl = config('suppliers.cache_ttl', 300);

        return Cache::remember($cacheKey, $ttl, function () use ($origin, $destination, $date) {
            $query = Flight::with('supplier')
                ->where('origin', $origin)
                ->where('destination', $destination);

            if ($date) {
                $query->whereDate('departure_at', $date);
            }

            return $query->get();
        });
    }

    public function clearCache(string $origin, string $destination): void
    {
        // Simple invalidation. In production, we might need a more sophisticated approach
        // if we have date-specific keys.
        $cacheKey = "flights:{$origin}:{$destination}";
        Cache::forget($cacheKey);
        
        // Also clear common date-based keys if possible or use tags (if supported by store)
    }
}
