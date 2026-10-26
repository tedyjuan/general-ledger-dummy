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
        Schema::create('trial_balance_account_group_2', function (Blueprint $table) {
            $table->increments('id');
            $table->string('company_code', 20);
            $table->string('code', 100);
            $table->string('description', 150)->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));
            $table->unsignedInteger('tbag_1')->nullable();

            $table->unique(['company_code', 'code', 'description', 'tbag_1'], 'unique_tbag_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_balance_account_group_2');
    }
};
