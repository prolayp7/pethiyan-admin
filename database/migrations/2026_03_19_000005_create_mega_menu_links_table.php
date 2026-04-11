<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Individual links inside each mega menu column
        Schema::create('mega_menu_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('column_id')->constrained('mega_menu_columns')->cascadeOnDelete();

            $table->string('label');                      // e.g. "Ziplock Stand-Up"
            $table->string('href');                       // e.g. "/categories/ziplock-pouches"
            $table->string('target')->default('_self');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['column_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mega_menu_links');
    }
};
