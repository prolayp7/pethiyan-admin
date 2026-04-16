<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('user_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('billing_company_name')->nullable()->after('billing_name');
            $table->string('shipping_company_name')->nullable()->after('shipping_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['billing_company_name', 'shipping_company_name']);
        });

        Schema::table('addresses', function (Blueprint $table) {
            $table->dropColumn('company_name');
        });
    }
};