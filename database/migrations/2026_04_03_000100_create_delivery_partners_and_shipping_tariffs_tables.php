<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('slug', 120)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipping_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_partner_id')->constrained('delivery_partners')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('zone_id')->constrained('pin_zones')->cascadeOnUpdate()->cascadeOnDelete();

            $table->decimal('upto_250', 10, 2)->default(0);
            $table->decimal('upto_500', 10, 2)->default(0);
            $table->decimal('every_500', 10, 2)->default(0);
            $table->decimal('per_kg', 10, 2)->default(0);
            $table->decimal('kg_2', 10, 2)->default(0);
            $table->decimal('above_5_surface', 10, 2)->default(0);
            $table->decimal('above_5_air', 10, 2)->default(0);
            $table->decimal('fuel_surcharge_percent', 5, 2)->default(0);
            $table->decimal('gst_percent', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['delivery_partner_id', 'zone_id']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_tariffs');
        Schema::dropIfExists('delivery_partners');
    }
};
