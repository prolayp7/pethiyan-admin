<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('payment_webhook_logs')) {
            $this->ensureIndexes();
            return;
        }

        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50)->index();
            $table->string('event_name')->nullable()->index();
            $table->string('delivery_id')->nullable()->index();
            $table->unsignedBigInteger('order_payment_transaction_id')->nullable()->index();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->string('status', 50)->index();
            $table->boolean('signature_valid')->default(false)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('message')->nullable();
            $table->json('request_headers')->nullable();
            $table->longText('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });

        $this->ensureIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }

    private function ensureIndexes(): void
    {
        $indexes = collect(DB::select('SHOW INDEX FROM payment_webhook_logs'))
            ->pluck('Key_name')
            ->unique()
            ->flip();

        Schema::table('payment_webhook_logs', function (Blueprint $table) use ($indexes) {
            if (!$indexes->has('payment_webhook_logs_gateway_index')) {
                $table->index('gateway');
            }

            if (!$indexes->has('payment_webhook_logs_event_name_index')) {
                $table->index('event_name');
            }

            if (!$indexes->has('payment_webhook_logs_delivery_id_index')) {
                $table->index('delivery_id');
            }

            if (!$indexes->has('payment_webhook_logs_order_payment_transaction_id_index')) {
                $table->index('order_payment_transaction_id');
            }

            if (!$indexes->has('payment_webhook_logs_order_id_index')) {
                $table->index('order_id');
            }

            if (!$indexes->has('payment_webhook_logs_status_index')) {
                $table->index('status');
            }

            if (!$indexes->has('payment_webhook_logs_signature_valid_index')) {
                $table->index('signature_valid');
            }

            if (!$indexes->has('payment_webhook_logs_processed_at_index')) {
                $table->index('processed_at');
            }
        });
    }
};