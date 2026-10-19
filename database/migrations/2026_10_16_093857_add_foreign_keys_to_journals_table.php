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
        Schema::table('journals', function (Blueprint $table) {
            $table->foreign(['depo_id'], 'journals_depo_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['journal_source_id'], 'journals_journal_source_id_fkey')->references(['id'])->on('journal_sources')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropForeign('journals_depo_id_fkey');
            $table->dropForeign('journals_journal_source_id_fkey');
        });
    }
};
