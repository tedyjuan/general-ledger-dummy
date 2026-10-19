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
        Schema::table('audit_list', function (Blueprint $table) {
            $table->foreign(['depo_id'], 'audit_list_depo_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['journal_id'], 'audit_list_journal_id_fkey')->references(['id'])->on('journals')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['user_id'], 'audit_list_user_id_fkey')->references(['id'])->on('users')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['journal_item_id'], 'fk_journal_item_id')->references(['id'])->on('journal_items')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_list', function (Blueprint $table) {
            $table->dropForeign('audit_list_depo_id_fkey');
            $table->dropForeign('audit_list_journal_id_fkey');
            $table->dropForeign('audit_list_user_id_fkey');
            $table->dropForeign('fk_journal_item_id');
        });
    }
};
