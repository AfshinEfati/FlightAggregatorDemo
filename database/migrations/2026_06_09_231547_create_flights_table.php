<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $blueprint->foreignId('route_id')->constrained()->onDelete('cascade');
            $blueprint->string('flight_number');
            $blueprint->string('airline');
            $blueprint->string('origin', 10);
            $blueprint->string('destination', 10);
            $blueprint->dateTime('departure_at');
            $blueprint->dateTime('arrival_at');
            $blueprint->decimal('price', 15, 2);
            $blueprint->string('currency', 5);
            $blueprint->integer('seats_available');
            $blueprint->string('cabin_class');
            $blueprint->string('raw_hash')->unique();
            $blueprint->dateTime('last_synced_at');
            $blueprint->timestamps();

            $blueprint->index(['origin', 'destination', 'departure_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
