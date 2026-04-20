<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // If a foreign key exists for user_id, drop it first. Use information_schema to detect
        $dbName = env('DB_DATABASE');
        $fk = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, 'reviews', 'user_id']
        );

        if ($fk) {
            Schema::table('reviews', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        // Re-create foreign key if it's not present
        $fkCheck = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, 'reviews', 'user_id']
        );

        if (!$fkCheck) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        $dbName = env('DB_DATABASE');
        $fk = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$dbName, 'reviews', 'user_id']
        );

        if ($fk) {
            Schema::table('reviews', function (Blueprint $table) use ($fk) {
                $table->dropForeign($fk->CONSTRAINT_NAME);
            });
        }

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
