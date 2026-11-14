<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostingBalance extends Model
{
    protected $table = 'posting_balances';

    protected $fillable = [
        'company_code',
        'depo_code',
        'year',
        'period',
        'cost_center_code',
        'coa_code',
        'opening_balance',
        'debit',
        'credit',
    ];

    // period & year JANGAN di-cast integer, supaya '01' tidak jadi 1
    protected $casts = [
        'opening_balance' => 'integer',
        'debit'           => 'integer',
        'credit'          => 'integer',
    ];
}
