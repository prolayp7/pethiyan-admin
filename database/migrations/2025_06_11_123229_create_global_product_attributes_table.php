<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('global_product_attributes')) {
            Schema::create('global_product_attributes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('seller_id');
                $table->string('title', 255);
                $table->string('slug', 191)->unique();
                $table->string('label');
                $table->enum('swatche_type', ['text', 'color', 'image'])->default('text');
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();

                $table->foreign('seller_id')->references('id')->on('sellers');
            });

            return;
        }

        if (Schema::hasColumn('global_product_attributes', 'slug')) {
            try {
                DB::statement('ALTER TABLE `global_product_attributes` MODIFY `slug` VARCHAR(191) NOT NULL');
            } catch (\Throwable $e) {
                // No-op if already compatible.
            }

            try {
                DB::statement('ALTER TABLE `global_product_attributes` ADD UNIQUE `global_product_attributes_slug_unique`(`slug`)');
            } catch (\Throwable $e) {
                // No-op if index already exists.
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_product_attributes');
    }
};
