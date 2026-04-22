<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->unsignedBigInteger('faq_category_id')->nullable()->after('id');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('answer');

            $table->foreign('faq_category_id')
                  ->references('id')
                  ->on('faq_categories')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropForeign(['faq_category_id']);
            $table->dropColumn(['faq_category_id', 'sort_order']);
        });
    }
};
