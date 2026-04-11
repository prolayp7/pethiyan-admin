<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Each panel has 1-N columns (e.g. "By Closure", "By Material", "By Use")
        Schema::create('mega_menu_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('mega_menu_panels')->cascadeOnDelete();

            $table->string('heading');                    // e.g. "By Closure"
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['panel_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mega_menu_columns');
    }
};
