<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('delivery_boy_withdrawal_requests')) {
            Schema::table('delivery_boy_withdrawal_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('delivery_boy_withdrawal_requests', 'processed_by_admin_id')) {
                    $table->unsignedBigInteger('processed_by_admin_id')->nullable()->after('processed_by');
                    $table->index('processed_by_admin_id', 'dbwr_processed_by_admin_idx');
                    $table->foreign('processed_by_admin_id', 'dbwr_processed_by_admin_fk')
                        ->references('id')
                        ->on('admin_users')
                        ->onDelete('set null');
                }
            });

            DB::statement("
                UPDATE delivery_boy_withdrawal_requests wr
                INNER JOIN admin_users au ON au.legacy_user_id = wr.processed_by
                SET wr.processed_by_admin_id = au.id
                WHERE wr.processed_by IS NOT NULL
                  AND wr.processed_by_admin_id IS NULL
            ");
        }

        if (Schema::hasTable('seller_withdrawal_requests')) {
            Schema::table('seller_withdrawal_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('seller_withdrawal_requests', 'processed_by_admin_id')) {
                    $table->unsignedBigInteger('processed_by_admin_id')->nullable()->after('processed_by');
                    $table->index('processed_by_admin_id', 'swr_processed_by_admin_idx');
                    $table->foreign('processed_by_admin_id', 'swr_processed_by_admin_fk')
                        ->references('id')
                        ->on('admin_users')
                        ->onDelete('set null');
                }
            });

            DB::statement("
                UPDATE seller_withdrawal_requests wr
                INNER JOIN admin_users au ON au.legacy_user_id = wr.processed_by
                SET wr.processed_by_admin_id = au.id
                WHERE wr.processed_by IS NOT NULL
                  AND wr.processed_by_admin_id IS NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('delivery_boy_withdrawal_requests')) {
            Schema::table('delivery_boy_withdrawal_requests', function (Blueprint $table) {
                if (Schema::hasColumn('delivery_boy_withdrawal_requests', 'processed_by_admin_id')) {
                    $table->dropForeign('dbwr_processed_by_admin_fk');
                    $table->dropIndex('dbwr_processed_by_admin_idx');
                    $table->dropColumn('processed_by_admin_id');
                }
            });
        }

        if (Schema::hasTable('seller_withdrawal_requests')) {
            Schema::table('seller_withdrawal_requests', function (Blueprint $table) {
                if (Schema::hasColumn('seller_withdrawal_requests', 'processed_by_admin_id')) {
                    $table->dropForeign('swr_processed_by_admin_fk');
                    $table->dropIndex('swr_processed_by_admin_idx');
                    $table->dropColumn('processed_by_admin_id');
                }
            });
        }
    }
};
