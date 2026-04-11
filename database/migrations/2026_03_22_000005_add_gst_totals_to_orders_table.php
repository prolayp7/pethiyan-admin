<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add order-level GST summary columns.
 * Required for GST invoice header totals.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Customer delivery state (determines intra vs inter supply)
            $table->string('customer_state', 60)->nullable()->after('id')
                  ->comment('Customer delivery state name');
            $table->string('customer_state_code', 5)->nullable()->after('customer_state')
                  ->comment('2-letter state code e.g. MH, DL, KA');

            // Supply type for the whole order
            $table->enum('supply_type', ['intra', 'inter'])->default('intra')->after('customer_state_code')
                  ->comment('intra=same state as store, inter=different state');

            // Order-level GST totals (sum of all line items)
            $table->decimal('total_taxable_amount', 12, 2)->default(0)->after('supply_type');
            $table->decimal('total_cgst',           10, 2)->default(0)->after('total_taxable_amount');
            $table->decimal('total_sgst',           10, 2)->default(0)->after('total_cgst');
            $table->decimal('total_igst',           10, 2)->default(0)->after('total_sgst');
            $table->decimal('total_gst',            10, 2)->default(0)->after('total_igst')
                  ->comment('total_cgst + total_sgst + total_igst');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'customer_state', 'customer_state_code', 'supply_type',
                'total_taxable_amount', 'total_cgst', 'total_sgst',
                'total_igst', 'total_gst',
            ]);
        });
    }
};
