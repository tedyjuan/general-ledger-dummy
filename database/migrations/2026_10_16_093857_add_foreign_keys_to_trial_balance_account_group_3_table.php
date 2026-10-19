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
        Schema::table('trial_balance_account_group_3', function (Blueprint $table) {
            $table->foreign(['company_id'], 'trial_balance_account_group_3_company_id_fkey')->references(['id'])->on('companies')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['tbag_2'], 'trial_balance_account_group_3_tbag_2_fkey')->references(['id'])->on('trial_balance_account_group_2')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trial_balance_account_group_3', function (Blueprint $table) {
            $table->dropForeign('trial_balance_account_group_3_company_id_fkey');
            $table->dropForeign('trial_balance_account_group_3_tbag_2_fkey');
        });
    }
};
