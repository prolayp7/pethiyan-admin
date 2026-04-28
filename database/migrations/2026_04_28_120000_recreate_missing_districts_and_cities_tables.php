<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('districts')) {
            Schema::create('districts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_id')->constrained('states')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['state_id', 'name']);
                $table->index('name');
            });
        }

        if (!Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('state_id')->constrained('states')->cascadeOnUpdate()->cascadeOnDelete();
                $table->foreignId('district_id')->constrained('districts')->cascadeOnUpdate()->cascadeOnDelete();
                $table->string('name', 100);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['district_id', 'name']);
                $table->index('name');
            });
        }

        // Re-add FK constraints on pin_service_areas if the columns exist but FKs are missing
        $hasFk = fn(string $col) => collect(
            \DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pin_service_areas'
                AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$col])
        )->isNotEmpty();

        Schema::table('pin_service_areas', function (Blueprint $table) use ($hasFk) {
            if (Schema::hasColumn('pin_service_areas', 'district_id') && !$hasFk('district_id')) {
                $table->foreign('district_id')->references('id')->on('districts')->nullOnDelete();
            }
            if (Schema::hasColumn('pin_service_areas', 'city_id') && !$hasFk('city_id')) {
                $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pin_service_areas', function (Blueprint $table) {
            if (Schema::hasColumn('pin_service_areas', 'city_id')) {
                $table->dropConstrainedForeignId('city_id');
            }
            if (Schema::hasColumn('pin_service_areas', 'district_id')) {
                $table->dropConstrainedForeignId('district_id');
            }
        });

        Schema::dropIfExists('cities');
        Schema::dropIfExists('districts');
    }
};
