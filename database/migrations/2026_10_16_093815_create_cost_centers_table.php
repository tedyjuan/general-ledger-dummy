
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::create('cost_centers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cost_center', 30)->unique();
            $table->string('name', 255);
            $table->string('company_code', 20);
            $table->string('area_code', 10);
            $table->string('department_code', 10);
            $table->string('division_code', 10);
            $table->string('segment_code', 10);

            $table->text('description')->nullable();
            $table->string('manager', 100)->nullable();

            $table->timestamps();

            // Index untuk filtering
            $table->index(['company_code', 'area_code'], 'cc_company_area_code_idx');
            $table->index(['company_code', 'department_code'], 'cc_company_department_idx');
            // pendukung FK komposit dari product_mapped
            $table->unique(['company_code', 'cost_center'], 'cc_company_cost_center_key');


            $table->foreign('company_code', 'fk_company_cc')
                ->references('code')->on('companies')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('segment_code', 'fk_segmen_cc')
                ->references('code')->on('segments')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('division_code', 'fk_division_cc')
                ->references('code')->on('divisions')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('department_code', 'fk_department_cc')
                ->references('code')->on('departments')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('area_code', 'fk_depo_cc')
                ->references('area_code')->on('depos')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
