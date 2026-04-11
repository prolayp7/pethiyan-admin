<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id')->index();
            $table->string('name');
            $table->string('state_code', 10)->nullable()->comment('ISO alpha-2/3 code, e.g. TN, MH');
            $table->string('gst_code', 5)->nullable()->comment('GST/tax state code, e.g. 33 for Tamil Nadu');
            $table->boolean('is_ut')->default(false)->comment('True for Union Territories');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
