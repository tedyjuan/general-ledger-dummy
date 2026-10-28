<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Casbin extends Model
{
    use HasFactory;

    protected $table = 'casbin_rules';
    protected $fillable = [
        'ptype',
        'v0',
        'v1',
        'v2',
        'v3',
        'v4',
        'v5',
    ];

    protected function casts(): array
    {
        return [
            'ptype' => 'string',
            'v0' => 'string',
            'v1' => 'string',
            'v2' => 'string',
            'v3' => 'string',
            'v4' => 'string',
            'v5' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
