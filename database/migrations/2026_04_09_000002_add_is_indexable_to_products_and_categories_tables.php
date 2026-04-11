<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_indexable')->default(true)->after('metadata');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_indexable')->default(true)->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_indexable');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('is_indexable');
        });
    }
};
