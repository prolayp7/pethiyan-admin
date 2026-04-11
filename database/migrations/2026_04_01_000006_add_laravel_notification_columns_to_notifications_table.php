<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add missing Laravel database-notification columns
        Schema::table('notifications', function (Blueprint $table) {
            // Laravel notification JSON payload
            $table->text('data')->nullable()->after('type');

            // Polymorphic notifiable (who receives the notification)
            $table->string('notifiable_type')->nullable()->after('data');
            $table->unsignedBigInteger('notifiable_id')->nullable()->after('notifiable_type');

            // When the notification was read (null = unread)
            $table->timestamp('read_at')->nullable()->after('notifiable_id');
        });

        // Change id from auto-increment bigint to UUID string
        // (Laravel's database notification channel uses UUID primary keys)
        DB::statement('ALTER TABLE `notifications` MODIFY `id` VARCHAR(36) NOT NULL');
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['data', 'notifiable_type', 'notifiable_id', 'read_at']);
        });

        DB::statement('ALTER TABLE `notifications` MODIFY `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT');
    }
};
