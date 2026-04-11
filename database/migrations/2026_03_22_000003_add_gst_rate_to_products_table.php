<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add a product-level GST rate override.
 * If set, this overrides the tax-class rate for this specific product.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Per-product GST rate override (null = use tax class rate)
            $table->enum('gst_rate', ['0', '5', '12', '18', '28'])->nullable()->after('hsn_code')
                  ->comment('Overrides tax class GST rate for this product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('gst_rate');
        });
    }
};
