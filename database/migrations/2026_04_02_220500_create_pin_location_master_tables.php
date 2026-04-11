<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pin_zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // A/B/C/D/E
            $table->string('name', 100);
            $table->string('default_delivery_time', 30)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pin_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique(); // North/South/East/West...
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pin_states', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('iso_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pin_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('pin_states')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['state_id', 'name']);
            $table->index('name');
        });

        Schema::create('pin_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->constrained('pin_states')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('district_id')->constrained('pin_districts')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['district_id', 'name']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pin_cities');
        Schema::dropIfExists('pin_districts');
        Schema::dropIfExists('pin_states');
        Schema::dropIfExists('pin_regions');
        Schema::dropIfExists('pin_zones');
    }
};
