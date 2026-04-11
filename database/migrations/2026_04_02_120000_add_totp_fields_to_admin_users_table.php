<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table) {
            if (!Schema::hasColumn('admin_users', 'totp_secret')) {
                $table->text('totp_secret')->nullable()->after('password');
            }

            if (!Schema::hasColumn('admin_users', 'totp_enabled_at')) {
                $table->timestamp('totp_enabled_at')->nullable()->after('totp_secret');
            }

            if (!Schema::hasColumn('admin_users', 'totp_recovery_codes')) {
                $table->text('totp_recovery_codes')->nullable()->after('totp_enabled_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('admin_users')) {
            return;
        }

        Schema::table('admin_users', function (Blueprint $table) {
            $dropColumns = [];

            if (Schema::hasColumn('admin_users', 'totp_recovery_codes')) {
                $dropColumns[] = 'totp_recovery_codes';
            }

            if (Schema::hasColumn('admin_users', 'totp_enabled_at')) {
                $dropColumns[] = 'totp_enabled_at';
            }

            if (Schema::hasColumn('admin_users', 'totp_secret')) {
                $dropColumns[] = 'totp_secret';
            }

            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
