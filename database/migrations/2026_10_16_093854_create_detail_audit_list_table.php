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
        Schema::create('detail_audit_list', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('audit_list_id');
            $table->unsignedInteger('journal_id')->nullable();
            $table->string('voucher_number');
            $table->string('batch_number');
            $table->timestampTz('created_at')->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_audit_list');
    }
};
