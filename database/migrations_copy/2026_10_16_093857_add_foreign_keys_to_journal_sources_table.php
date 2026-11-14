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
        Schema::table('journal_sources', function (Blueprint $table) {
            $table->foreign(['depo_id'], 'journal_sources_depo_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_sources', function (Blueprint $table) {
            $table->dropForeign('journal_sources_depo_id_fkey');
        });
    }
};
