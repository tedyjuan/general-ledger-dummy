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
        Schema::create('posting_balances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('depo_id')->nullable();
            $table->integer('year');
            $table->string('period');
            $table->unsignedInteger('cost_center_id')->nullable()->index('idx_cost_center_id');
            $table->unsignedInteger('coa_id')->nullable()->index('idx_coa_id');
            $table->bigInteger('opening_balance')->nullable();
            $table->bigInteger('debit')->nullable();
            $table->bigInteger('credit')->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));

            $table->unique(['depo_id', 'year', 'period', 'cost_center_id', 'coa_id'], 'posting_balances_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posting_balances');
    }
};
