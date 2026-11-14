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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('username', 191)->unique('users_username_key');
            $table->unsignedInteger('role_id');
            $table->text('password_hash');
            $table->boolean('is_password_default')->nullable()->default(false);
            $table->text('token_reset_password')->nullable();
            $table->timestampTz('created_at')->default(DB::raw("now()"));
            $table->timestampTz('updated_at')->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
