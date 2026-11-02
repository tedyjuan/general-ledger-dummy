<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_balance_account_group_1', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->string('code', 20);
            $table->string('description', 150)->nullable();
            $table->string('account_type', 30);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // kunci unik yang dirujuk FK group 2
            $table->unique(['company_code', 'code', 'account_type'], 'unique_tbag_1');
            $table->index(['company_code', 'account_type'], 'idx_tbag_1_company_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_balance_account_group_1');
    }
};
