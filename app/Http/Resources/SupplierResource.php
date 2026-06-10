<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'base_url' => $this->base_url,
            'poll_interval_minutes' => $this->poll_interval_minutes,
            'is_active' => $this->is_active,
            'timeout_seconds' => $this->timeout_seconds,
            'retry_attempts' => $this->retry_attempts,
            'last_synced_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
