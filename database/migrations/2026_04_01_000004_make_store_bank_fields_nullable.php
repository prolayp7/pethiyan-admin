<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('landmark', 100)->nullable()->change();
            $table->string('tax_name', 250)->nullable()->change();
            $table->string('tax_number', 250)->nullable()->change();
            $table->string('bank_name', 250)->nullable()->change();
            $table->string('bank_branch_code', 250)->nullable()->change();
            $table->string('account_holder_name', 250)->nullable()->change();
            $table->string('account_number', 250)->nullable()->change();
            $table->string('routing_number', 250)->nullable()->change();
            $table->string('bank_account_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('landmark', 100)->nullable(false)->change();
            $table->string('tax_name', 250)->nullable(false)->change();
            $table->string('tax_number', 250)->nullable(false)->change();
            $table->string('bank_name', 250)->nullable(false)->change();
            $table->string('bank_branch_code', 250)->nullable(false)->change();
            $table->string('account_holder_name', 250)->nullable(false)->change();
            $table->string('account_number', 250)->nullable(false)->change();
            $table->string('routing_number', 250)->nullable(false)->change();
            $table->string('bank_account_type')->nullable(false)->change();
        });
    }
};
