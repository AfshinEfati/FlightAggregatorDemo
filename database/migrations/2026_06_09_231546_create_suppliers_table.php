<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('name');
            $blueprint->string('slug')->unique();
            $blueprint->string('base_url');
            $blueprint->string('api_key')->nullable();
            $blueprint->integer('poll_interval_minutes')->default(20);
            $blueprint->boolean('is_active')->default(true);
            $blueprint->integer('timeout_seconds')->default(30);
            $blueprint->integer('retry_attempts')->default(3);
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
