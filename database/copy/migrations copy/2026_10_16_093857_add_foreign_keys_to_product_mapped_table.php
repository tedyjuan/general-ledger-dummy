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
        Schema::table('product_mapped', function (Blueprint $table) {
            $table->foreign(['coa_id'], 'product_mapped_coa_id_fkey')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['cost_center_id'], 'product_mapped_cost_center_id_fkey')->references(['id'])->on('cost_centers')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['product_id'], 'product_mapped_product_id_fkey')->references(['id'])->on('products')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_mapped', function (Blueprint $table) {
            $table->dropForeign('product_mapped_coa_id_fkey');
            $table->dropForeign('product_mapped_cost_center_id_fkey');
            $table->dropForeign('product_mapped_product_id_fkey');
        });
    }
};
