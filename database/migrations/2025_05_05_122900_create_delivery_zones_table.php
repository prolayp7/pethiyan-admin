<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('delivery_zones')) {
            Schema::create('delivery_zones', function (Blueprint $table) {
                $table->id();
                $table->string('name', 255);
                $table->string('slug', 191)->unique();
                $table->decimal('center_latitude', 10, 8);
                $table->decimal('center_longitude', 11, 8);
                $table->double('radius_km');
                $table->json('boundary_json')->nullable();
                $table->enum('status', ["active", "inactive"]);
                $table->timestamps();
            });

            return;
        }

        if (Schema::hasColumn('delivery_zones', 'slug')) {
            // Handle partially-created tables on old MySQL versions.
            try {
                DB::statement('ALTER TABLE `delivery_zones` MODIFY `slug` VARCHAR(191) NOT NULL');
            } catch (\Throwable $e) {
                // No-op if already compatible.
            }

            try {
                DB::statement('ALTER TABLE `delivery_zones` ADD UNIQUE `delivery_zones_slug_unique`(`slug`)');
            } catch (\Throwable $e) {
                // No-op if index already exists.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_zones');
    }
};
