<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trial_balance_account_group_3', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->string('code', 20);
            $table->string('description', 150)->nullable();

            // rantai induk (tipe & panjang harus sama dengan group 2)
            $table->string('tbag_2_code', 20);
            $table->string('tbag_1_code', 20);
            $table->string('tbag_1_account_type', 30);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(
                ['company_code', 'code', 'tbag_2_code', 'tbag_1_code', 'tbag_1_account_type'],
                'unique_tbag_3'
            );

            $table->foreign(
                ['company_code', 'tbag_2_code', 'tbag_1_code', 'tbag_1_account_type'],
                'fk_tbag_3_tbag_2'
            )
                ->references(['company_code', 'code', 'tbag_1_code', 'tbag_1_account_type'])
                ->on('trial_balance_account_group_2')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trial_balance_account_group_3');
    }
};
