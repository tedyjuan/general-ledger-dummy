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

        Schema::create('depos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->string('code', 20);
            $table->string('npwp', 30)->nullable();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone_no')->nullable();
            $table->string('alias');
            $table->string('area_code');
            $table->integer('fiscal_year')->nullable();
            $table->enum('status_depo', ['pusat', 'depo'])->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('company_code', 'depos_company_code_fkey')
                ->references('code')
                ->on('companies')
                ->restrictOnDelete();

            $table->unique(['alias', 'company_code'], 'depos_alias_company_code_key');
            $table->unique(['area_code', 'company_code'], 'depos_area_code_company_code_key');
            $table->unique(['code', 'company_code'], 'depos_code_company_code_key');
            $table->unique(['name', 'company_code'], 'depos_name_company_code_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depos');
    }
};
