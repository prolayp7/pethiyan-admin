<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'admin_user_id')) {
                $table->unsignedBigInteger('admin_user_id')->nullable()->after('user_id');
                $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('set null');
                $table->index('admin_user_id');
            }
        });

        DB::statement("
            UPDATE notifications n
            INNER JOIN admin_users au ON au.legacy_user_id = n.user_id
            SET n.admin_user_id = au.id
            WHERE n.sent_to = 'admin'
              AND n.user_id IS NOT NULL
              AND n.admin_user_id IS NULL
        ");
    }

    public function down(): void
    {
        if (!Schema::hasTable('notifications')) {
            return;
        }

        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'admin_user_id')) {
                $table->dropForeign(['admin_user_id']);
                $table->dropIndex(['admin_user_id']);
                $table->dropColumn('admin_user_id');
            }
        });
    }
};

