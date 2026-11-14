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
        Schema::create('journals', function (Blueprint $table) {
            $table->increments('id');
            $table->string('batch_number');
            $table->unsignedInteger('depo_id')->nullable();
            $table->unsignedInteger('journal_source_id')->nullable();
            $table->text('description')->nullable();
            $table->string('voucher_number');
            $table->date('transaction_date')->index('idx_journals_transaction_date_only');
            $table->bigInteger('total')->nullable();
            $table->enum('status', ['posted', 'unposted', 'temporary'])->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));
            $table->string('note')->nullable();
            $table->integer('sequence')->nullable();
            $table->text('source')->nullable();
            $table->string('user_create')->nullable();

            $table->index(['transaction_date'], 'idx_transaction_date');
            $table->index(['batch_number', 'depo_id', 'journal_source_id', 'voucher_number', 'transaction_date'], 'journals_batch_number_idx');
            $table->index(['depo_id', 'status', 'transaction_date', 'journal_source_id'], 'idx_journals_composite');
            $table->unique(['journal_source_id', 'depo_id', 'voucher_number', 'transaction_date', 'batch_number', 'sequence'], 'unique_journal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
