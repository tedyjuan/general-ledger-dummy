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
        Schema::create('product_mapped', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('product_id');
            $table->string('transaction_type', 100)->nullable();
            $table->string('operational_type', 100)->nullable();
            $table->unsignedInteger('cost_center_id')->nullable();
            $table->unsignedInteger('coa_id')->nullable();
            $table->enum('kind', ['debit', 'credit'])->nullable();
            $table->string('do_type', 100)->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));

            $table->unique(['product_id', 'transaction_type', 'operational_type', 'cost_center_id', 'coa_id', 'kind', 'do_type'], 'product_mapped_product_id_transaction_type_operational_type_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_mapped');
    }
};
