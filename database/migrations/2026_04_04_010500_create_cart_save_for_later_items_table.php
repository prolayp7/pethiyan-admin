<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cart_save_for_later_items')) {
            return;
        }

        Schema::create('cart_save_for_later_items', function (Blueprint $table) {
            $table->id();
            // NOTE: Existing carts table uses MyISAM in this project DB,
            // so we keep plain indexed IDs instead of FK constraints.
            $table->unsignedBigInteger('cart_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('product_variant_id')->index();
            $table->unsignedBigInteger('store_id')->index();
            $table->integer('quantity');
            $table->enum('save_for_later', ['0', '1'])->default('1');
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'product_variant_id', 'store_id'], 'cart_sfl_unique_item');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_save_for_later_items');
    }
};
