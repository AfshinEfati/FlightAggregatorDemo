<?php

namespace App\Services;

use App\Models\Flight;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FlightSearchService
{
    public function search(array $params): Collection
    {
        $origin = strtoupper($params['origin']);
        $destination = strtoupper($params['destination']);
        $date = $params['date'] ?? null;

        $version = $this->cacheVersion($origin, $destination);
        $cacheKey = $this->cacheKey($origin, $destination, $date, $version);
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
        $origin = strtoupper($origin);
        $destination = strtoupper($destination);
        $versionKey = $this->versionKey($origin, $destination);
        $currentVersion = $this->cacheVersion($origin, $destination);

        Cache::forever($versionKey, $currentVersion + 1);
    }

    private function cacheVersion(string $origin, string $destination): int
    {
        return (int) Cache::get($this->versionKey($origin, $destination), 1);
    }

    private function versionKey(string $origin, string $destination): string
    {
        return "flights:version:{$origin}:{$destination}";
    }

    private function cacheKey(string $origin, string $destination, ?string $date, int $version): string
    {
        $key = "flights:{$origin}:{$destination}:v{$version}";

        return $date ? "{$key}:{$date}" : $key;
    }
}
