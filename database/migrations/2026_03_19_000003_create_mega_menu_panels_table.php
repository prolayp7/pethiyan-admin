<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Each row is one sidebar tab in the mega menu (e.g. "Stand-Up Pouches").
        // It belongs to the menu_item whose type = 'mega_menu' (the "Categories" nav item).
        Schema::create('mega_menu_panels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();

            $table->string('label');                       // e.g. "Stand-Up Pouches"
            $table->string('href');                        // e.g. "/categories/standup-pouches"
            $table->string('accent_color', 20)->nullable(); // hex brand colour for this panel
            $table->string('image_path')->nullable();      // hero banner image
            $table->string('tagline')->nullable();         // subtitle shown in panel header

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['menu_item_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mega_menu_panels');
    }
};
