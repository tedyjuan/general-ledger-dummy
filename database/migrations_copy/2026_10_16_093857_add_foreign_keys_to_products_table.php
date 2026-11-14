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
        Schema::table('products', function (Blueprint $table) {
            $table->foreign(['coa_id'], 'products_coa_id_fkey')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['company_id'], 'products_company_id_fkey')->references(['id'])->on('companies')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_coa_id_fkey');
            $table->dropForeign('products_company_id_fkey');
        });
    }
};
