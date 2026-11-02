<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->integer('account_number');            // kode akun
            $table->string('name');
            $table->string('description')->nullable();

            // Akun induk & akun referensi (dalam company yang sama)
            $table->integer('header_code')->nullable();
            $table->integer('reference_code')->nullable();

            // Rantai Trial Balance Group 3 -> 2 -> 1
            // NOT NULL supaya FK komposit selalu divalidasi MySQL
            // (kalau ada kolom NULL, MySQL melewati pengecekan FK).
            // Jika klasifikasi memang opsional, ubah 4 kolom ini jadi ->nullable()
            // dan validasi "semua terisi atau semua kosong" di aplikasi.
            $table->string('tbag_3_code', 20);
            $table->string('tbag_2_code', 20);
            $table->string('tbag_1_code', 20);
            $table->string('account_type', 30);           // harus sama tipe dengan tbag_1_account_type

            $table->enum('account_method', ['debit', 'credit'])->nullable();
            $table->enum('account_group', ['kas', 'bank', 'inventory', 'sales'])->nullable();
            $table->enum('cost_center_type', ['all', 'depo', 'unit'])->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Target FK self-reference (urutan kolom harus sama dengan FK)
            $table->unique(['company_code', 'account_number'], 'unique_coa');

            // Index untuk FK & query hierarki
            $table->index(['company_code', 'header_code'], 'idx_coa_header');
            $table->index(['company_code', 'reference_code'], 'idx_coa_reference');
            $table->index(
                ['company_code', 'tbag_3_code', 'tbag_2_code', 'tbag_1_code', 'account_type'],
                'idx_coa_tbag'
            );

            // Header: akun induk
            $table->foreign(['company_code', 'header_code'], 'fk_coa_header')
                ->references(['company_code', 'account_number'])
                ->on('chart_of_accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Reference: akun lain
            $table->foreign(['company_code', 'reference_code'], 'fk_coa_reference')
                ->references(['company_code', 'account_number'])
                ->on('chart_of_accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Trial Balance Group 3
            // Pastikan group_3 punya unique 'unique_tbag_3' dengan urutan kolom persis:
            // (company_code, code, tbag_2_code, tbag_1_code, tbag_1_account_type)
            $table->foreign(
                ['company_code', 'tbag_3_code', 'tbag_2_code', 'tbag_1_code', 'account_type'],
                'fk_coa_tbag_3'
            )
                ->references(['company_code', 'code', 'tbag_2_code', 'tbag_1_code', 'tbag_1_account_type'])
                ->on('trial_balance_account_group_3')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
