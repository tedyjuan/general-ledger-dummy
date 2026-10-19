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
        Schema::create('audit_list', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('action', ['CREATED', 'UPDATED', 'DELETED', 'SYSTEM_UPDATED', 'UPDATE_STATUS']);
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('depo_id');
            $table->text('detail');
            $table->string('batch_number')->nullable();
            $table->string('voucher_number')->nullable();
            $table->unsignedInteger('journal_id')->nullable();
            $table->string('latest_status')->nullable();
            $table->string('old_status')->nullable();
            $table->unsignedInteger('journal_item_id')->nullable();
            $table->string('source')->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_list');
    }
};
