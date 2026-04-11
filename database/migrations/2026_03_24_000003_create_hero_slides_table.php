<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();           // stored path in public disk
            $table->string('eyebrow', 120)->default('');
            $table->string('heading', 300)->default('');   // \n for line breaks
            $table->text('description')->nullable();
            $table->string('primary_cta_label', 120)->default('');
            $table->string('primary_cta_href', 500)->default('/shop');
            $table->string('secondary_cta_label', 120)->default('');
            $table->string('secondary_cta_href', 500)->default('/contact');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
