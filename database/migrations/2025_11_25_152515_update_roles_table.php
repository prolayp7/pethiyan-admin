<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE `roles` MODIFY `name` VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE `roles` MODIFY `guard_name` VARCHAR(100) NOT NULL');
        } catch (\Throwable $e) {
            // No-op if already compatible.
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_name_guard_name_unique');
            });
        } catch (\Throwable $e) {
            // No-op if index does not exist.
        }

        try {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['team_id', 'guard_name', 'name']);
            });
        } catch (\Throwable $e) {
            // No-op if index already exists.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'guard_name', 'name']);
            $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
        });
    }
};
