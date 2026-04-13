<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('trending_products');
        Schema::create('trending_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->unique();
            $table->unsignedBigInteger('search_count')->default(0)->comment('Times product appeared in search results');
            $table->unsignedBigInteger('view_count')->default(0)->comment('Product page views');
            $table->unsignedBigInteger('sale_count')->default(0)->comment('Units sold in window');
            $table->unsignedInteger('score')->default(0)->comment('Computed trending score');
            $table->enum('period', ['daily', 'weekly'])->default('weekly');
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->index(['score', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trending_products');
    }
};
