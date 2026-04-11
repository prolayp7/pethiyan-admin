<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('minimum_order_quantity')->nullable()->default(1)->change();
            $table->integer('quantity_step_size')->nullable()->default(1)->change();
            $table->integer('total_allowed_quantity')->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->integer('minimum_order_quantity')->nullable(false)->default(1)->change();
            $table->integer('quantity_step_size')->nullable(false)->default(1)->change();
            $table->integer('total_allowed_quantity')->nullable(false)->default(1)->change();
        });
    }
};
