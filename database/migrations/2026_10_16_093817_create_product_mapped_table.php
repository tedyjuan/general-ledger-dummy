<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_mapped', function (Blueprint $table) {
            $table->increments('id');

            $table->string('company_code', 20);
            $table->string('product_code', 50);          // = products.product_code
            $table->string('transaction_type', 100);
            $table->string('operational_type', 100);
            $table->string('cost_center_code', 30);      // = cost_centers.cost_center
            $table->string('coa_code', 50);              // = chart_of_accounts.account_number
            $table->enum('kind', ['debit', 'credit']);
            $table->string('do_type', 100);

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // ---------- UNIQUE ----------
            $table->unique(
                [
                    'company_code',
                    'product_code',
                    'transaction_type',
                    'operational_type',
                    'cost_center_code',
                    'coa_code',
                    'kind',
                    'do_type'
                ],
                'uq_product_mapped'
            );

            // ---------- INDEX ----------
            $table->index(['company_code', 'coa_code'], 'idx_pm_company_coa');
            $table->index(['company_code', 'cost_center_code'], 'idx_pm_company_cc');

            // ---------- FOREIGN KEY ----------
            $table->foreign(['company_code', 'product_code'], 'fk_pm_product')
                ->references(['company_code', 'product_code'])
                ->on('products')
                ->restrictOnUpdate()->cascadeOnDelete();

            $table->foreign(['company_code', 'coa_code'], 'fk_pm_coa')
                ->references(['company_code', 'account_number'])
                ->on('chart_of_accounts')
                ->restrictOnUpdate()->restrictOnDelete();

            $table->foreign(['company_code', 'cost_center_code'], 'fk_pm_cost_center')
                ->references(['company_code', 'cost_center'])
                ->on('cost_centers')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_mapped');
    }
};
    //     $operationalType = [
    //     '01-DIRECT'       => '01-Direct',
    //     '02-RETAIL'       => '02-Retail',
    //     '03-INDIRECT'     => '03-Indirect',
    //     '04-ANTAR-CABANG' => '04-Antar Cabang',
    //     '05-INTERN'       => '05-Intern',
    // ];

    // // Update tedy -- note stop sewa tadinya gak ada
    // $transactionType = [
    //     'JAMINAN'       => 'Jaminan',
    //     'JUAL'          => 'Jual',
    //     'PASANG TARIK'  => 'Pasang Tarik',
    //     'SEWA'          => 'Sewa',
    //     'RETUR'         => 'Retur',
    //     'TARIK JAMINAN' => 'Tarik Jaminan',
    //     'STOP SEWA'     => 'Stop Sewa',
    // ];

    // $kind = [
    //     'debit'  => 'Debit',
    //     'credit' => 'Kredit',
    // ];

    // $doType = [
    //     'LA5' => 'LA5',
    //     'LA8' => 'LA8',
    //     'LA6' => 'LA6',
    //     'LA1' => 'LA1',
    //     'LA3' => 'LA3',
    //     'LA4' => 'LA4',
    //     'LA7' => 'LA7',
    //     'LA2' => 'LA2',
    // ];
