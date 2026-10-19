<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('casbin_rule', function (Blueprint $table) {
            $table->string('id', 191)->primary();
            $table->string('ptype', 191)->nullable();
            $table->string('v0', 191)->nullable();
            $table->string('v1', 191)->nullable();
            $table->string('v2', 191)->nullable();
            $table->string('v3', 191)->nullable();
            $table->string('v4', 191)->nullable();
            $table->string('v5', 191)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('casbin_rule');
    }
};
