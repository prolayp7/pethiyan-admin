<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_disputes', function (Blueprint $table) {
            $table->id();
            $table->string('razorpay_dispute_id')->unique();
            $table->string('razorpay_payment_id');
            $table->foreignId('order_payment_transaction_id')->nullable()->constrained('order_payment_transactions')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('INR');
            $table->enum('status', ['created', 'under_review', 'won', 'lost', 'closed', 'action_required'])->default('created');
            $table->string('reason_code')->nullable();
            $table->string('reason_description')->nullable();
            $table->timestamp('respond_by')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('razorpay_payment_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_disputes');
    }
};
