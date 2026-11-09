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

        Schema::create('journal_sources', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->string('depo_code', 20);
            $table->string('code', 20);
            $table->string('description');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['company_code', 'depo_code', 'code'], 'journal_sources_company_depo_code_key');
            $table->index('depo_code');

            // sesudah
            $table->foreign('company_code')
                ->references('code')
                ->on('companies')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_sources');
    }
};
