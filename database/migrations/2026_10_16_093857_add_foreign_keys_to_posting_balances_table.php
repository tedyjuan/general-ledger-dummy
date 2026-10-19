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
        Schema::table('posting_balances', function (Blueprint $table) {
            $table->foreign(['coa_id'], 'posting_balances_coa_id_fkey')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cost_center_id'], 'posting_balances_cost_center_id_fkey')->references(['id'])->on('cost_centers')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['depo_id'], 'posting_balances_depo_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posting_balances', function (Blueprint $table) {
            $table->dropForeign('posting_balances_coa_id_fkey');
            $table->dropForeign('posting_balances_cost_center_id_fkey');
            $table->dropForeign('posting_balances_depo_id_fkey');
        });
    }
};
