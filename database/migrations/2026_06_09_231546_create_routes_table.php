<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routes', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('origin', 10);
            $blueprint->string('destination', 10);
            $blueprint->timestamps();

            $blueprint->unique(['origin', 'destination']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routes');
    }
};
