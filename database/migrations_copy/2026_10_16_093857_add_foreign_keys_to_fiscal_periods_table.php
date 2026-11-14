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
        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->foreign(['depo_id'], 'fiscal_periods_depo_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fiscal_periods', function (Blueprint $table) {
            $table->dropForeign('fiscal_periods_depo_id_fkey');
        });
    }
};
