<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductMapped extends Model
{
    use HasFactory, BelongsToCompany;

    public const KINDS = [
        'debit'  => 'Debit',
        'credit' => 'Kredit',
    ];

    // key = nilai yang disimpan di database, value = teks yang tampil
    public const OPERATIONAL_TYPES = [
        '01-DIRECT'       => '01-Direct',
        '02-RETAIL'       => '02-Retail',
        '03-INDIRECT'     => '03-Indirect',
        '04-ANTAR-CABANG' => '04-Antar Cabang',
        '05-INTERN'       => '05-Intern',
    ];

    public const TRANSACTION_TYPES = [
        'JAMINAN'       => 'Jaminan',
        'JUAL'          => 'Jual',
        'PASANG TARIK'  => 'Pasang Tarik',
        'SEWA'          => 'Sewa',
        'RETUR'         => 'Retur',
        'TARIK JAMINAN' => 'Tarik Jaminan',
        'STOP SEWA'     => 'Stop Sewa',
    ];

    public const DO_TYPES = [
        'LA1' => 'LA1',
        'LA2' => 'LA2',
        'LA3' => 'LA3',
        'LA4' => 'LA4',
        'LA5' => 'LA5',
        'LA6' => 'LA6',
        'LA7' => 'LA7',
        'LA8' => 'LA8',
    ];

    protected $table = 'product_mapped';

    protected $fillable = [
        'company_code',
        'product_code',
        'transaction_type',
        'operational_type',
        'cost_center_code',
        'coa_code',
        'kind',
        'do_type',
    ];

    protected function casts(): array
    {
        return [
            'company_code'     => 'string',
            'product_code'     => 'string',
            'cost_center_code' => 'string',
            'coa_code'         => 'string',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }
}
