<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // 'header' = main nav  |  'footer' = footer nav columns
            $table->enum('location', ['header', 'footer'])->default('header')->after('slug');
        });

        // Backfill existing header_main record
        DB::table('menus')->where('slug', 'header_main')->update(['location' => 'header']);
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};
