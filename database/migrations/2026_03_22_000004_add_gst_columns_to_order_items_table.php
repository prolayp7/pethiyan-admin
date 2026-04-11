<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add GST breakdown per line item on order_items.
 *
 * Indian GST invoice requirement: each line must show
 *   taxable_amount, gst_rate, [cgst+sgst OR igst], total_amount
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // HSN code per line (may differ from product HSN if customised)
            $table->string('hsn_code', 20)->nullable()->after('product_id')
                  ->comment('HSN/SAC code for this line item');

            // Applied GST slab
            $table->decimal('gst_rate', 5, 2)->default(0)->after('hsn_code')
                  ->comment('Total GST % applied (e.g. 18.00)');

            // Supply type determines CGST+SGST vs IGST
            $table->enum('gst_type', ['intra', 'inter'])->default('intra')->after('gst_rate')
                  ->comment('intra=CGST+SGST, inter=IGST');

            // Taxable value (before GST)
            $table->decimal('taxable_amount', 12, 2)->default(0)->after('gst_type');

            // GST component amounts
            $table->decimal('cgst_amount', 10, 2)->default(0)->after('taxable_amount');
            $table->decimal('sgst_amount', 10, 2)->default(0)->after('cgst_amount');
            $table->decimal('igst_amount', 10, 2)->default(0)->after('sgst_amount');

            // Total tax on this line
            $table->decimal('total_tax_amount', 10, 2)->default(0)->after('igst_amount');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn([
                'hsn_code', 'gst_rate', 'gst_type',
                'taxable_amount', 'cgst_amount', 'sgst_amount',
                'igst_amount', 'total_tax_amount',
            ]);
        });
    }
};
