<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 255);
            $table->unsignedSmallInteger('result_count')->default(0);
            $table->json('entity_types')->nullable()->comment('e.g. ["products","blogs","categories"]');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('query');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
