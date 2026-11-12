<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->string('product_code', 50);
            $table->string('product_name');
            $table->string('coa_code', 50);          // = chart_of_accounts.account_number

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // ---------- UNIQUE ----------
            // company_code di depan, urutannya harus sama dengan FK dari product_mapped
            $table->unique(['company_code', 'product_code'], 'products_company_product_key');

            // ---------- INDEX ----------
            $table->index(['company_code', 'coa_code'], 'idx_products_company_coa');

            // ---------- FOREIGN KEY ----------
            $table->foreign('company_code', 'fk_products_company')
                ->references('code')->on('companies')
                ->cascadeOnUpdate()->restrictOnDelete();

            $table->foreign(['company_code', 'coa_code'], 'fk_products_coa')
                ->references(['company_code', 'account_number'])
                ->on('chart_of_accounts')
                ->restrictOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
