<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pin_service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('pincode', 10)->unique();
            $table->string('state', 100);
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->enum('zone', ['A', 'B', 'C', 'D', 'E'])->default('E');
            $table->string('zone1', 50)->nullable()->comment('Region: North, South, East, West, etc.');
            $table->string('delivery_time', 30)->nullable()->comment('e.g. 1-2 Days');
            $table->boolean('is_serviceable')->default(true);
            $table->timestamps();

            $table->index('state');
            $table->index('district');
            $table->index('zone');
            $table->index('is_serviceable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pin_service_areas');
    }
};
