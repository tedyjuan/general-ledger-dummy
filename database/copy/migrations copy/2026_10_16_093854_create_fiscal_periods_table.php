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
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('depo_id');
            $table->integer('year');
            $table->integer('period');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['closed', 'open', 'undefined'])->nullable()->default('undefined');
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));

            $table->unique(['depo_id', 'year', 'period'], 'fiscal_periods_depo_id_year_period_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
