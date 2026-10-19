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
        Schema::table('data_indicators', function (Blueprint $table) {
            $table->foreign(['depo_id'], 'data_indicators_depo_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('data_indicators', function (Blueprint $table) {
            $table->dropForeign('data_indicators_depo_id_fkey');
        });
    }
};
