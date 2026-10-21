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
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->foreign(['area_id'], 'cost_centers_area_id_fkey')->references(['id'])->on('depos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['company_id'], 'cost_centers_company_id_fkey')->references(['id'])->on('companies')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['department_id'], 'cost_centers_department_id_fkey')->references(['id'])->on('departments')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['division_id'], 'cost_centers_division_id_fkey')->references(['id'])->on('divisions')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['segment_id'], 'cost_centers_segment_id_fkey')->references(['id'])->on('segments')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cost_centers', function (Blueprint $table) {
            $table->dropForeign('cost_centers_area_id_fkey');
            $table->dropForeign('cost_centers_company_id_fkey');
            $table->dropForeign('cost_centers_department_id_fkey');
            $table->dropForeign('cost_centers_division_id_fkey');
            $table->dropForeign('cost_centers_segment_id_fkey');
        });
    }
};
