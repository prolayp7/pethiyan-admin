<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('cart_save_for_later_items')) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $existingIndexes = collect(DB::select(
            "SELECT INDEX_NAME FROM information_schema.statistics WHERE table_schema = ? AND table_name = ?",
            [$dbName, 'cart_save_for_later_items']
        ))->pluck('INDEX_NAME')->all();

        $addIndexIfMissing = function (string $indexName, string $ddl) use ($existingIndexes) {
            if (!in_array($indexName, $existingIndexes, true)) {
                DB::statement($ddl);
            }
        };

        $addIndexIfMissing(
            'cart_save_for_later_items_cart_id_index',
            'ALTER TABLE cart_save_for_later_items ADD INDEX cart_save_for_later_items_cart_id_index (cart_id)'
        );
        $addIndexIfMissing(
            'cart_save_for_later_items_product_id_index',
            'ALTER TABLE cart_save_for_later_items ADD INDEX cart_save_for_later_items_product_id_index (product_id)'
        );
        $addIndexIfMissing(
            'cart_save_for_later_items_product_variant_id_index',
            'ALTER TABLE cart_save_for_later_items ADD INDEX cart_save_for_later_items_product_variant_id_index (product_variant_id)'
        );
        $addIndexIfMissing(
            'cart_save_for_later_items_store_id_index',
            'ALTER TABLE cart_save_for_later_items ADD INDEX cart_save_for_later_items_store_id_index (store_id)'
        );
        $addIndexIfMissing(
            'cart_sfl_unique_item',
            'ALTER TABLE cart_save_for_later_items ADD UNIQUE cart_sfl_unique_item (cart_id, product_id, product_variant_id, store_id)'
        );
    }

    public function down(): void
    {
        if (!Schema::hasTable('cart_save_for_later_items')) {
            return;
        }

        $dbName = DB::getDatabaseName();
        $existingIndexes = collect(DB::select(
            "SELECT INDEX_NAME FROM information_schema.statistics WHERE table_schema = ? AND table_name = ?",
            [$dbName, 'cart_save_for_later_items']
        ))->pluck('INDEX_NAME')->all();

        $dropIndexIfExists = function (string $indexName, string $ddl) use ($existingIndexes) {
            if (in_array($indexName, $existingIndexes, true)) {
                DB::statement($ddl);
            }
        };

        $dropIndexIfExists('cart_sfl_unique_item', 'ALTER TABLE cart_save_for_later_items DROP INDEX cart_sfl_unique_item');
        $dropIndexIfExists('cart_save_for_later_items_store_id_index', 'ALTER TABLE cart_save_for_later_items DROP INDEX cart_save_for_later_items_store_id_index');
        $dropIndexIfExists('cart_save_for_later_items_product_variant_id_index', 'ALTER TABLE cart_save_for_later_items DROP INDEX cart_save_for_later_items_product_variant_id_index');
        $dropIndexIfExists('cart_save_for_later_items_product_id_index', 'ALTER TABLE cart_save_for_later_items DROP INDEX cart_save_for_later_items_product_id_index');
        $dropIndexIfExists('cart_save_for_later_items_cart_id_index', 'ALTER TABLE cart_save_for_later_items DROP INDEX cart_save_for_later_items_cart_id_index');
    }
};

