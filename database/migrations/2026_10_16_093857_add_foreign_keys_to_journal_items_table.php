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
        Schema::table('journal_items', function (Blueprint $table) {
            $table->foreign(['ref_journal_item_id'], 'fk_ref_journal_item_id')->references(['id'])->on('journal_items')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['chart_of_account_id'], 'journal_items_chart_of_account_id_fkey')->references(['id'])->on('chart_of_accounts')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['cost_center_id'], 'journal_items_cost_center_id_fkey')->references(['id'])->on('cost_centers')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['journal_id'], 'journal_items_journal_id_fkey')->references(['id'])->on('journals')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['product_mapped_id'], 'journal_items_product_mapped_id_fkey')->references(['id'])->on('product_mapped')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropForeign('fk_ref_journal_item_id');
            $table->dropForeign('journal_items_chart_of_account_id_fkey');
            $table->dropForeign('journal_items_cost_center_id_fkey');
            $table->dropForeign('journal_items_journal_id_fkey');
            $table->dropForeign('journal_items_product_mapped_id_fkey');
        });
    }
};
