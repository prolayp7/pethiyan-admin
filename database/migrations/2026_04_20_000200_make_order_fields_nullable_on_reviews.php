<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            try { $table->dropForeign(['order_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['order_item_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['store_id']); } catch (\Exception $e) {}

            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->unsignedBigInteger('order_item_id')->nullable()->change();
            $table->unsignedBigInteger('store_id')->nullable()->change();

            try { $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null'); } catch (\Exception $e) {}
            try { $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null'); } catch (\Exception $e) {}
            try { $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null'); } catch (\Exception $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            try { $table->dropForeign(['order_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['order_item_id']); } catch (\Exception $e) {}
            try { $table->dropForeign(['store_id']); } catch (\Exception $e) {}

            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_item_id')->nullable(false)->change();
            $table->unsignedBigInteger('store_id')->nullable(false)->change();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }
};
