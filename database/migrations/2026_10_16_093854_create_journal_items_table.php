<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('journal_id')->nullable()->index('idx_journal_items_journal_id');
            $table->unsignedInteger('cost_center_id')->nullable()->index('idx_journal_items_cost_center_id');
            $table->unsignedInteger('chart_of_account_id')->nullable()->index('journal_items_chart_of_account_id_idx');
            $table->unsignedInteger('product_mapped_id')->nullable();
            $table->string('journal_number')->nullable();
            $table->bigInteger('debit');
            $table->bigInteger('credit');
            $table->text('description')->nullable();
            $table->string('bku_id')->nullable();
            $table->string('transaction_type')->nullable();
            $table->integer('quantity')->nullable();
            $table->bigInteger('dpp')->nullable();
            $table->bigInteger('ppn')->nullable();
            $table->bigInteger('total')->nullable();
            $table->timestampTz('transaction_date')->nullable();
            $table->string('previous_account_number')->nullable();
            $table->integer('sequence_number')->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));
            $table->text('description_header')->nullable();
            $table->unsignedInteger('ref_journal_item_id')->nullable();
            $table->text('status_revisi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_items');
    }
};
