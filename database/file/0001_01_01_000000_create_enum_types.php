<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $types = [
        'account_method_enum'             => ['debit', 'credit'],
        'account_type_enum'               => ['asset', 'liability', 'equity', 'income', 'expense', 'retained earning account'],
        'audit_action_enum'               => ['CREATED', 'UPDATED', 'DELETED', 'SYSTEM_UPDATED', 'UPDATE_STATUS'],
        'coa_group_enum'                  => ['kas', 'bank', 'inventory', 'sales'],
        'cost_center_type_enum'           => ['all', 'depo', 'unit'],
        'data_indicator_data_type_enum'   => ['VB', 'VT', 'LA'],
        'depo_status_enum'                => ['pusat', 'depo'],
        'fiscal_period_status_enum'       => ['closed', 'open', 'undefined'],
        'journal_status_enum'             => ['posted', 'unposted', 'temporary'],
        'product_mapped_kind_enum'        => ['debit', 'credit'],
    ];

    public function up(): void
    {
        foreach ($this->types as $name => $values) {
            $list = implode(', ', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $values));
            DB::unprepared("CREATE TYPE {$name} AS ENUM ({$list})");
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->types) as $name) {
            DB::unprepared("DROP TYPE IF EXISTS {$name} CASCADE");
        }
    }
};
