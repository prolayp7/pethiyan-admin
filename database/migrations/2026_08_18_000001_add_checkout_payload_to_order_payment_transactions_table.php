<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_payment_transactions', function (Blueprint $table) {
            // Holds the validated checkout payload (address_id, shipping_rate_id,
            // promo_code, etc.) for a payment that was initiated before the
            // internal Order exists yet — see EasepayController::createOrder()
            // and EasepayService::handleOrderWebhook(). Null for every
            // transaction row that isn't a pre-order-creation placeholder.
            $table->json('checkout_payload')->nullable()->after('payment_details');
        });
    }

    public function down(): void
    {
        Schema::table('order_payment_transactions', function (Blueprint $table) {
            $table->dropColumn('checkout_payload');
        });
    }
};
