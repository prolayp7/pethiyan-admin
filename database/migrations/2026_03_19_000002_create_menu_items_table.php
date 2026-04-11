<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();

            $table->string('label');
            $table->string('href')->nullable();            // null for heading/divider types
            $table->string('target')->default('_self');    // '_self' | '_blank'

            // Type controls which dropdown/panel is rendered
            // link          → plain anchor
            // shop_dropdown → renders the Shop quick-links panel
            // mega_menu     → renders the sidebar + columns mega menu
            // heading       → non-clickable section label (mobile drawer)
            // divider       → visual separator
            $table->enum('type', ['link', 'shop_dropdown', 'mega_menu', 'heading', 'divider'])
                  ->default('link');

            // Used by shop_dropdown items (icon name from lucide-react, description, accent)
            $table->string('icon')->nullable();            // lucide icon name, e.g. "Star"
            $table->string('description')->nullable();     // short subtitle for dropdown card
            $table->string('accent_color', 20)->nullable(); // hex, e.g. "#f59e0b"
            $table->string('badge')->nullable();           // e.g. "Best Seller", "New"

            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();          // for any future extra data

            $table->timestamps();
            $table->softDeletes();

            $table->index(['menu_id', 'sort_order']);
            $table->index(['parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
