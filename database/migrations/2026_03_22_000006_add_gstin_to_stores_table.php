<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add GST registration details to stores.
 * GSTIN appears on tax invoices as the supplier's GST number.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // 15-character GSTIN: 2(state) + 10(PAN) + 1(entity) + 1(Z) + 1(check)
            $table->string('gstin', 15)->nullable()->after('id')
                  ->comment('GST Identification Number (15 chars)');

            // Store registration state — used to determine intra/inter supply
            $table->string('state_name', 60)->nullable()->after('gstin');
            $table->string('state_code', 5)->nullable()->after('state_name')
                  ->comment('2-letter state code e.g. MH, DL, KA, GJ');

            // Whether GST is registered (unregistered = composition scheme or exempt)
            $table->boolean('gst_registered')->default(false)->after('state_code');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['gstin', 'state_name', 'state_code', 'gst_registered']);
        });
    }
};
