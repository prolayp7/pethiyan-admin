<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('payment_settlements')) {
            return;
        }

        Schema::create('payment_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_settlement_id')->unique();
            $table->string('razorpay_payment_id')->nullable();
            $table->foreignId('order_payment_transaction_id')->nullable()->constrained('order_payment_transactions')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('INR');
            $table->string('status', 50)->default('processed');
            $table->string('event_name', 100)->nullable();
            $table->string('settlement_reference')->nullable();
            $table->string('utr')->nullable();
            $table->timestamp('settled_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('razorpay_payment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settlements');
    }
};