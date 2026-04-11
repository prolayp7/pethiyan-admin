<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_trust_badges', function (Blueprint $table) {
            $table->id();
            $table->string('icon_name', 80)->default('shield-check');  // lucide icon name
            $table->string('label', 80)->default('');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_trust_badges');
    }
};
