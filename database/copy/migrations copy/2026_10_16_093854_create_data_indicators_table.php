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
        Schema::create('data_indicators', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('depo_id')->nullable();
            $table->timestampTz('transaction_date');
            $table->integer('year')->nullable();
            $table->string('period')->nullable();
            $table->integer('total')->nullable();
            $table->enum('data_type', ['VB', 'VT', 'LA'])->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));

            $table->unique(['depo_id', 'transaction_date', 'data_type'], 'unique_data_indicator_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_indicators');
    }
};
