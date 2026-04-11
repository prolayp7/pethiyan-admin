<?php

use App\Enums\Banner\BannerPositionEnum;
use App\Enums\Banner\BannerTypeEnum;
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
        if (! Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->id(); // bigint [pk, increment]
                $table->enum('type', BannerTypeEnum::values()); // enum
                $table->string('title', 255); // varchar(255)
                $table->string('slug', 191)->unique(); // varchar(191) [unique]
                $table->string('custom_url', 255)->nullable(); // varchar(255) [null]
                $table->unsignedBigInteger('product_id')->nullable(); // bigint [null]
                $table->unsignedBigInteger('category_id')->nullable(); // bigint [null]
                $table->unsignedBigInteger('brand_id')->nullable(); // bigint [null]
                $table->enum('position', BannerPositionEnum::values()); // enum
                $table->enum('visibility_status', ['published', 'draft'])->default('draft'); // enum
                $table->integer('display_order')->default(0); // integer
                $table->json('metadata')->nullable(); // json [null]
                $table->timestamps(); // created_at, updated_at

                // Foreign key constraints
                $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
                $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            });

            return;
        }

        if (Schema::hasColumn('banners', 'slug')) {
            try {
                DB::statement('ALTER TABLE `banners` MODIFY `slug` VARCHAR(191) NOT NULL');
            } catch (\Throwable $e) {
                // No-op if already compatible.
            }

            try {
                DB::statement('ALTER TABLE `banners` ADD UNIQUE `banners_slug_unique`(`slug`)');
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
        Schema::dropIfExists('banners');
    }
};
