<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance tax_rates to be GST-aware.
 *
 * Indian GST slabs (2026): 0%, 5%, 12%, 18%, 28%
 * - Intra-state supply  → CGST (rate/2) + SGST (rate/2)
 * - Inter-state supply  → IGST (full rate)
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            // Which official GST slab this rate belongs to
            $table->enum('gst_slab', ['0', '5', '12', '18', '28'])->default('18')->after('rate');

            // Pre-computed halves for intra-state (CGST + SGST = gst_slab)
            $table->decimal('cgst_rate', 5, 2)->default(0)->after('gst_slab')
                  ->comment('CGST rate = gst_slab / 2');
            $table->decimal('sgst_rate', 5, 2)->default(0)->after('cgst_rate')
                  ->comment('SGST/UTGST rate = gst_slab / 2');
            $table->decimal('igst_rate', 5, 2)->default(0)->after('sgst_rate')
                  ->comment('IGST rate = full gst_slab for inter-state supply');

            // Human-readable description of what goods/services this applies to
            $table->string('description')->nullable()->after('igst_rate');

            // Whether this is a GST-specific rate (vs generic non-GST rate)
            $table->boolean('is_gst')->default(true)->after('description');

            // Active/inactive control
            $table->boolean('is_active')->default(true)->after('is_gst');
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropColumn(['gst_slab', 'cgst_rate', 'sgst_rate', 'igst_rate', 'description', 'is_gst', 'is_active']);
        });
    }
};
