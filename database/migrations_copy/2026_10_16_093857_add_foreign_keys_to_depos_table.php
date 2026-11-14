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
        Schema::table('depos', function (Blueprint $table) {
            $table->foreign(['company_id'], 'depos_company_id_fkey')->references(['id'])->on('companies')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depos', function (Blueprint $table) {
            $table->dropForeign('depos_company_id_fkey');
        });
    }
};
