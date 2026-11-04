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
            $table->string('depo_code', 20);
            $table->string('year');
            $table->string('period');
            $table->string('start_date');
            $table->string('end_date');
            $table->enum('status', ['closed', 'open', 'undefined'])->nullable()->default('undefined');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['depo_code', 'year', 'period'], 'fiscal_periods_depo_id_year_period_key');
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
