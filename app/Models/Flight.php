<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flight extends Model
{
    protected $fillable = [
        'supplier_id',
        'route_id',
        'flight_number',
        'airline',
        'origin',
        'destination',
        'departure_at',
        'arrival_at',
        'price',
        'currency',
        'seats_available',
        'cabin_class',
        'raw_hash',
        'last_synced_at',
    ];

    protected $casts = [
        'departure_at' => 'datetime',
        'arrival_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'price' => 'decimal:2',
        'seats_available' => 'integer',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
