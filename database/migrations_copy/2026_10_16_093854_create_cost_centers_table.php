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
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('area_id')->nullable();
            $table->unsignedInteger('division_id')->nullable();
            $table->unsignedInteger('department_id')->nullable();
            $table->unsignedInteger('segment_id')->nullable();
            $table->text('description')->nullable();
            $table->string('manager')->nullable();
            $table->unsignedInteger('company_id')->nullable();
            $table->timestampTz('updated_at')->default(DB::raw("now()"));
            $table->timestampTz('created_at')->default(DB::raw("now()"));

            $table->unique(['area_id', 'division_id', 'department_id', 'segment_id'], 'unique_cost_center_combination');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
