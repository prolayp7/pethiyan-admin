<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('state_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('state_name', 100);
            $table->string('state_code', 5)->unique(); // 2-letter code e.g. MH, DL
            $table->decimal('base_rate', 10, 2)->default(0);         // flat shipping fee
            $table->decimal('per_kg_rate', 10, 2)->default(0);       // per kg charge
            $table->decimal('free_shipping_above', 10, 2)->nullable(); // order total threshold
            $table->unsignedInteger('estimated_days_min')->default(3);
            $table->unsignedInteger('estimated_days_max')->default(7);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('state_shipping_rates');
    }
};
