<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE OR REPLACE VIEW cost_centers_view AS 
SELECT 
    cc.id,
    CONCAT(COALESCE(d.area_code, '99'), dp.code, dv.code, s.code) AS cost_center,
    CONCAT(COALESCE(d.alias, 'UMUM'), '/', dp.alias, '/', dv.alias, '/', s.alias) AS team,
    cc.description,
    cc.manager,
    cc.company_id,
    cc.created_at,
    cc.updated_at,
    cc.area_id,
    cc.division_id,
    cc.department_id,
    cc.segment_id
FROM cost_centers cc
JOIN depos d ON cc.area_id = d.id
JOIN divisions dv ON cc.division_id = dv.id
JOIN departments dp ON cc.department_id = dp.id
JOIN segments s ON cc.segment_id = s.id;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS cost_centers_view");
    }
};
