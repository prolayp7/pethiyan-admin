<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 0) Drop old foreign keys first so state_id can be remapped from pin_states IDs to states IDs.
        Schema::table('pin_service_areas', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['city_id']);
        });

        Schema::table('pin_districts', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
        });

        Schema::table('pin_cities', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['district_id']);
        });

        // 1) Ensure every legacy pin state exists in main states table.
        DB::statement("
            INSERT INTO states (country_id, name, state_code, gst_code, is_ut, created_at, updated_at)
            SELECT 101, ps.name, NULL, NULL, 0, NOW(), NOW()
            FROM pin_states ps
            LEFT JOIN states s ON LOWER(TRIM(s.name)) = LOWER(TRIM(ps.name))
            WHERE s.id IS NULL
        ");

        // 2) Map state_id across tables from pin_states -> states by state name.
        DB::statement("
            UPDATE pin_service_areas psa
            JOIN pin_states ps ON ps.id = psa.state_id
            JOIN states s ON LOWER(TRIM(s.name)) = LOWER(TRIM(ps.name))
            SET psa.state_id = s.id
        ");

        DB::statement("
            UPDATE pin_districts pd
            JOIN pin_states ps ON ps.id = pd.state_id
            JOIN states s ON LOWER(TRIM(s.name)) = LOWER(TRIM(ps.name))
            SET pd.state_id = s.id
        ");

        DB::statement("
            UPDATE pin_cities pc
            JOIN pin_states ps ON ps.id = pc.state_id
            JOIN states s ON LOWER(TRIM(s.name)) = LOWER(TRIM(ps.name))
            SET pc.state_id = s.id
        ");

        // 3) Rename tables as requested.
        Schema::rename('pin_districts', 'districts');
        Schema::rename('pin_cities', 'cities');

        // 4) Recreate FKs to new canonical tables.
        Schema::table('districts', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('pin_service_areas', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('states')->nullOnDelete();
            $table->foreign('district_id')->references('id')->on('districts')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
        });

        // 5) Remove no-longer-needed table.
        Schema::dropIfExists('pin_states');
    }

    public function down(): void
    {
        // Recreate legacy pin_states.
        Schema::create('pin_states', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('iso_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement("
            INSERT INTO pin_states (name, iso_code, is_active, created_at, updated_at)
            SELECT DISTINCT s.name, NULL, 1, NOW(), NOW()
            FROM states s
            JOIN pin_service_areas psa ON psa.state_id = s.id
        ");

        DB::statement("
            UPDATE pin_service_areas psa
            JOIN states s ON s.id = psa.state_id
            JOIN pin_states ps ON LOWER(TRIM(ps.name)) = LOWER(TRIM(s.name))
            SET psa.state_id = ps.id
        ");

        DB::statement("
            UPDATE districts d
            JOIN states s ON s.id = d.state_id
            JOIN pin_states ps ON LOWER(TRIM(ps.name)) = LOWER(TRIM(s.name))
            SET d.state_id = ps.id
        ");

        DB::statement("
            UPDATE cities c
            JOIN states s ON s.id = c.state_id
            JOIN pin_states ps ON LOWER(TRIM(ps.name)) = LOWER(TRIM(s.name))
            SET c.state_id = ps.id
        ");

        Schema::table('pin_service_areas', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['district_id']);
            $table->dropForeign(['city_id']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['district_id']);
        });

        Schema::table('districts', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
        });

        Schema::rename('districts', 'pin_districts');
        Schema::rename('cities', 'pin_cities');

        Schema::table('pin_districts', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('pin_states')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('pin_cities', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('pin_states')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('district_id')->references('id')->on('pin_districts')->cascadeOnUpdate()->cascadeOnDelete();
        });

        Schema::table('pin_service_areas', function (Blueprint $table) {
            $table->foreign('state_id')->references('id')->on('pin_states')->nullOnDelete();
            $table->foreign('district_id')->references('id')->on('pin_districts')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('pin_cities')->nullOnDelete();
        });
    }
};
