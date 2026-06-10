<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_url',
        'api_key',
        'poll_interval_minutes',
        'is_active',
        'timeout_seconds',
        'retry_attempts',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'poll_interval_minutes' => 'integer',
        'timeout_seconds' => 'integer',
        'retry_attempts' => 'integer',
        'api_key' => 'encrypted',
    ];

    public function flights(): HasMany
    {
        return $this->hasMany(Flight::class);
    }
}
