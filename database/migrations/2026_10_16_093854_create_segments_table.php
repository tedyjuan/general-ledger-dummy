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
        Schema::create('segments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('name');
            $table->string('alias');
            $table->string('company_code', 20);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['alias', 'company_code'], 'segments_alias_company_code_key');
            $table->unique(['code', 'company_code'], 'segments_code_company_code_key');
            $table->unique(['name', 'company_code'], 'segments_name_company_code_key');
            $table->index('company_code');

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
        Schema::dropIfExists('segments');
    }
};
