<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->increments('id');

            $table->string('company_code', 20);
            $table->integer('account_number');               // header 1001 / ledger 100101 / subledger 10010101
            $table->integer('header_code')->nullable();      // -> account_number milik header
            $table->integer('reference_code')->nullable();   // -> account_number milik ledger (khusus subledger)

            $table->string('name');
            $table->string('description');

            // rantai trial balance account group (pakai kode)
            $table->string('tbag_1', 20)->nullable();
            $table->string('tbag_2', 20)->nullable();
            $table->string('tbag_3', 20)->nullable();

            // string(30) supaya tipenya sama dengan trial_balance_account_group_1.account_type (syarat FK)
            $table->string('account_type', 30)->nullable();

            $table->enum('account_method', ['debit', 'credit'])->nullable();
            $table->enum('account_group', ['kas', 'bank', 'inventory', 'sales'])->nullable();
            $table->enum('cost_center_type', ['all', 'depo', 'unit'])->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // ---------- UNIQUE ----------
            // nomor akun unik per company (dirujuk FK header)
            $table->unique(['company_code', 'account_number'], 'coa_company_account_number_key');
            // pendukung FK subledger -> ledger
            $table->unique(['company_code', 'header_code', 'account_number'], 'coa_company_header_account_key');

            // ---------- INDEX ----------
            $table->index(['company_code', 'header_code'], 'idx_coa_company_header');
            $table->index(['company_code', 'header_code', 'reference_code'], 'idx_coa_company_header_reference');
            $table->index(['company_code', 'tbag_1', 'account_type'], 'idx_coa_tbag_1');
            $table->index(['company_code', 'tbag_2', 'tbag_1', 'account_type'], 'idx_coa_tbag_2');
            $table->index(['company_code', 'tbag_3', 'tbag_2', 'tbag_1', 'account_type'], 'idx_coa_tbag_3');

            // ---------- FOREIGN KEY ----------
            // company
            $table->foreign('company_code', 'fk_coa_company')
                ->references('code')->on('companies')
                ->cascadeOnUpdate()->restrictOnDelete();

            // ledger/subledger -> header (company yang sama)
            $table->foreign(['company_code', 'header_code'], 'fk_coa_header')
                ->references(['company_code', 'account_number'])
                ->on('chart_of_accounts')
                ->restrictOnUpdate()->restrictOnDelete();

            // subledger -> ledger (company & header harus sama dengan milik ledger)
            $table->foreign(['company_code', 'header_code', 'reference_code'], 'fk_coa_reference')
                ->references(['company_code', 'header_code', 'account_number'])
                ->on('chart_of_accounts')
                ->restrictOnUpdate()->restrictOnDelete();

            // trial balance group 1
            $table->foreign(['company_code', 'tbag_1', 'account_type'], 'fk_coa_tbag_1')
                ->references(['company_code', 'code', 'account_type'])
                ->on('trial_balance_account_group_1')
                ->cascadeOnUpdate()->restrictOnDelete();

            // trial balance group 2 (urutan kolom sama dengan unique_tbag_2)
            $table->foreign(['company_code', 'tbag_2', 'tbag_1', 'account_type'], 'fk_coa_tbag_2')
                ->references(['company_code', 'code', 'tbag_1_code', 'tbag_1_account_type'])
                ->on('trial_balance_account_group_2')
                ->cascadeOnUpdate()->restrictOnDelete();

            // trial balance group 3 (urutan kolom sama dengan unique_tbag_3)
            $table->foreign(['company_code', 'tbag_3', 'tbag_2', 'tbag_1', 'account_type'], 'fk_coa_tbag_3')
                ->references(['company_code', 'code', 'tbag_2_code', 'tbag_1_code', 'tbag_1_account_type'])
                ->on('trial_balance_account_group_3')
                ->cascadeOnUpdate()->restrictOnDelete();
        });

        // ---------- CHECK (MySQL 8.0.16+) ----------
        // 1. subledger wajib punya header_code (menutup celah FK komposit yang dilewati saat NULL)
        // 2. akun tidak boleh menunjuk dirinya sendiri
        DB::statement('
            ALTER TABLE chart_of_accounts
                ADD CONSTRAINT chk_coa_reference_needs_header
                    CHECK (reference_code IS NULL OR header_code IS NOT NULL),
                ADD CONSTRAINT chk_coa_not_self
                    CHECK (account_number <> header_code AND account_number <> reference_code)
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
