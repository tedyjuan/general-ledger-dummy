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
        Schema::table('detail_audit_list', function (Blueprint $table) {
            $table->foreign(['audit_list_id'], 'detail_audit_list_audit_list_id_fkey')->references(['id'])->on('audit_list')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['journal_id'], 'detail_audit_list_journal_id_fkey')->references(['id'])->on('journals')->onUpdate('no action')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_audit_list', function (Blueprint $table) {
            $table->dropForeign('detail_audit_list_audit_list_id_fkey');
            $table->dropForeign('detail_audit_list_journal_id_fkey');
        });
    }
};
