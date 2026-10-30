<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRulesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        $connection = config('lauthz.basic.database.connection') ?: config('database.default');
        Schema::connection($connection)->create(config('lauthz.basic.database.rules_table'), function (Blueprint $table) {
            $table->increments('id');
            $table->string('ptype', 20)->nullable();
            $table->string('v0', 50)->nullable();
            $table->string('v1', 50)->nullable();
            $table->string('v2', 50)->nullable();
            $table->string('v3', 50)->nullable();
            $table->string('v4', 50)->nullable();
            $table->string('v5', 50)->nullable();
            $table->timestamps();
            $table->index(['ptype', 'v0', 'v1', 'v2'], 'casbin_rule_lookup_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $connection = config('lauthz.basic.database.connection') ?: config('database.default');
        Schema::connection($connection)->dropIfExists(config('lauthz.basic.database.rules_table'));
    }
}
