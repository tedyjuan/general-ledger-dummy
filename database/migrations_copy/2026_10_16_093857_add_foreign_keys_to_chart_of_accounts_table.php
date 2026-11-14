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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->foreign(['company_id'], 'chart_of_accounts_company_id_fkey')->references(['id'])->on('companies')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tbag_1'], 'chart_of_accounts_tbag_1_fkey')->references(['id'])->on('trial_balance_account_group_1')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['tbag_2'], 'chart_of_accounts_tbag_2_fkey')->references(['id'])->on('trial_balance_account_group_2')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['tbag_3'], 'chart_of_accounts_tbag_3_fkey')->references(['id'])->on('trial_balance_account_group_3')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['header_id'], 'fk_header_id')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['reference_id'], 'fk_reference_id')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropForeign('chart_of_accounts_company_id_fkey');
            $table->dropForeign('chart_of_accounts_tbag_1_fkey');
            $table->dropForeign('chart_of_accounts_tbag_2_fkey');
            $table->dropForeign('chart_of_accounts_tbag_3_fkey');
            $table->dropForeign('fk_header_id');
            $table->dropForeign('fk_reference_id');
        });
    }
};
