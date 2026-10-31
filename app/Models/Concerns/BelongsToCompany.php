<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        // Semua query model ini otomatis: WHERE company_code = session
        static::addGlobalScope('company', function (Builder $builder) {
            // Seeder, artisan, dan queue tidak punya session
            if (app()->runningInConsole()) {
                return;
            }

            $code = session('company_code');

            if ($code) {
                $builder->where($builder->getModel()->getTable() . '.company_code', $code);
            } else {
                // Session kosong: jangan tampilkan data company mana pun
                $builder->whereRaw('1 = 0');
            }
        });

        // company_code terisi otomatis saat create
        static::creating(function ($model) {
            if (empty($model->company_code) && session()->has('company_code')) {
                $model->company_code = session('company_code');
            }
        });
    }
}
