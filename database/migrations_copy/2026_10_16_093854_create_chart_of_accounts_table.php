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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_number');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('header_id')->nullable()->index('idx_chart_of_accounts_header_id');
            $table->unsignedInteger('reference_id')->nullable()->index('idx_chart_of_accounts_reference_id');
            $table->string('name');
            $table->string('description');
            $table->unsignedInteger('tbag_1')->nullable();
            $table->unsignedInteger('tbag_2')->nullable();
            $table->unsignedInteger('tbag_3')->nullable();
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense', 'retained earning account'])->nullable();
            $table->enum('account_method', ['debit', 'credit'])->nullable();
            $table->enum('account_group', ['kas', 'bank', 'inventory', 'sales'])->nullable();
            $table->enum('cost_center_type', ['all', 'depo', 'unit'])->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));

            $table->unique(['account_number', 'company_id'], 'chart_of_accounts_account_number_company_id_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
