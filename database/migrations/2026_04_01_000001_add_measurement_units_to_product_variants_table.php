<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            if (!Schema::hasColumn('product_variants', 'capacity')) {
                $table->float('capacity')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('product_variants', 'capacity_unit')) {
                $table->string('capacity_unit', 20)->nullable()->after('capacity');
            }
            if (!Schema::hasColumn('product_variants', 'weight_unit')) {
                $table->string('weight_unit', 20)->nullable()->after('weight');
            }
            if (!Schema::hasColumn('product_variants', 'height_unit')) {
                $table->string('height_unit', 20)->nullable()->after('height');
            }
            if (!Schema::hasColumn('product_variants', 'breadth_unit')) {
                $table->string('breadth_unit', 20)->nullable()->after('breadth');
            }
            if (!Schema::hasColumn('product_variants', 'length_unit')) {
                $table->string('length_unit', 20)->nullable()->after('length');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $drop = [];
            foreach (['capacity', 'capacity_unit', 'weight_unit', 'height_unit', 'breadth_unit', 'length_unit'] as $column) {
                if (Schema::hasColumn('product_variants', $column)) {
                    $drop[] = $column;
                }
            }

            if (!empty($drop)) {
                $table->dropColumn($drop);
            }
        });
    }
};

