<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posting_balances', function (Blueprint $table) {
            $table->id();

            $table->string('company_code', 20);
            $table->string('depo_code', 20);
            $table->char('year', 4);
            $table->char('period', 2);
            $table->string('cost_center_code', 30);
            $table->string('coa_code', 50);

            // signed BIGINT: boleh minus. Default 0 agar SUM tidak terganggu NULL 
            $table->bigInteger('opening_balance')->default(0);
            $table->bigInteger('debit')->default(0);
            $table->bigInteger('credit')->default(0);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // ---------- UNIQUE ----------
            $table->unique(
                ['company_code', 'depo_code', 'year', 'period', 'cost_center_code', 'coa_code'],
                'uq_posting_balances'
            );

            // ---------- INDEX (untuk FK & laporan) ----------
            $table->index(['company_code', 'coa_code'], 'idx_pb_company_coa');
            $table->index(['company_code', 'cost_center_code'], 'idx_pb_company_cc');
            $table->index(['company_code', 'year', 'period'], 'idx_pb_company_period');

            // ---------- FOREIGN KEY ----------
            $table->foreign(['company_code', 'coa_code'], 'fk_pb_coa')
                ->references(['company_code', 'account_number'])
                ->on('chart_of_accounts')
                ->restrictOnUpdate()->restrictOnDelete();

            $table->foreign(['company_code', 'cost_center_code'], 'fk_pb_cost_center')
                ->references(['company_code', 'cost_center'])
                ->on('cost_centers')
                ->restrictOnUpdate()->restrictOnDelete();

            // SESUAIKAN: kolom kode depo di tabel depos Anda
            $table->foreign(['depo_code', 'company_code'], 'fk_pb_depo')
                ->references(['code', 'company_code'])
                ->on('depos')
                ->restrictOnUpdate()->restrictOnDelete();
        });

        // Opsional (MySQL 8.0.16+): debit & kredit tidak boleh minus
        DB::statement('ALTER TABLE posting_balances ADD CONSTRAINT chk_pb_amount CHECK (debit >= 0 AND credit >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('posting_balances');
    }
};
