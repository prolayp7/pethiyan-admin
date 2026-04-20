<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dbName = env('DB_DATABASE');

        // Drop existing foreign keys if they exist (by checking information_schema)
        $fks = DB::select(
            'SELECT COLUMN_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, 'reviews']
        );

        $fkMap = [];
        foreach ($fks as $fk) {
            $fkMap[$fk->COLUMN_NAME] = $fk->CONSTRAINT_NAME;
        }

        if (isset($fkMap['order_id'])) {
            Schema::table('reviews', function (Blueprint $table) use ($fkMap) {
                $table->dropForeign($fkMap['order_id']);
            });
        }

        if (isset($fkMap['order_item_id'])) {
            Schema::table('reviews', function (Blueprint $table) use ($fkMap) {
                $table->dropForeign($fkMap['order_item_id']);
            });
        }

        if (isset($fkMap['store_id'])) {
            Schema::table('reviews', function (Blueprint $table) use ($fkMap) {
                $table->dropForeign($fkMap['store_id']);
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
            $table->unsignedBigInteger('order_item_id')->nullable()->change();
            $table->unsignedBigInteger('store_id')->nullable()->change();
        });

        // Re-create foreign keys if not present
        $fksAfter = DB::select(
            'SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, 'reviews']
        );

        $present = array_map(fn($r) => $r->COLUMN_NAME, $fksAfter);

        if (!in_array('order_id', $present, true)) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            });
        }

        if (!in_array('order_item_id', $present, true)) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null');
            });
        }

        if (!in_array('store_id', $present, true)) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        $dbName = env('DB_DATABASE');

        $fks = DB::select(
            'SELECT COLUMN_NAME, CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, 'reviews']
        );

        $fkMap = [];
        foreach ($fks as $fk) {
            $fkMap[$fk->COLUMN_NAME] = $fk->CONSTRAINT_NAME;
        }

        if (isset($fkMap['order_id'])) {
            Schema::table('reviews', function (Blueprint $table) use ($fkMap) {
                $table->dropForeign($fkMap['order_id']);
            });
        }

        if (isset($fkMap['order_item_id'])) {
            Schema::table('reviews', function (Blueprint $table) use ($fkMap) {
                $table->dropForeign($fkMap['order_item_id']);
            });
        }

        if (isset($fkMap['store_id'])) {
            Schema::table('reviews', function (Blueprint $table) use ($fkMap) {
                $table->dropForeign($fkMap['store_id']);
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
            $table->unsignedBigInteger('order_item_id')->nullable(false)->change();
            $table->unsignedBigInteger('store_id')->nullable(false)->change();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('cascade');
        });
    }
};
