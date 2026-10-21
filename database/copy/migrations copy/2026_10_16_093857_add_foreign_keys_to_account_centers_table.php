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
        Schema::table('account_centers', function (Blueprint $table) {
            $table->foreign(['coa_id'], 'account_centers_coa_id_fkey')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cost_center_id'], 'account_centers_cost_center_id_fkey')->references(['id'])->on('cost_centers')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_centers', function (Blueprint $table) {
            $table->dropForeign('account_centers_coa_id_fkey');
            $table->dropForeign('account_centers_cost_center_id_fkey');
        });
    }
};
