<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pin_service_areas', function (Blueprint $table) {
            $table->foreignId('zone_id')->nullable()->after('zone')->constrained('pin_zones')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->after('zone1')->constrained('pin_regions')->nullOnDelete();
            $table->foreignId('state_id')->nullable()->after('state')->constrained('pin_states')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('district')->constrained('pin_districts')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('city')->constrained('pin_cities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pin_service_areas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('zone_id');
            $table->dropConstrainedForeignId('region_id');
            $table->dropConstrainedForeignId('state_id');
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
