<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpsertColumns;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Hash;

class UserImport implements
    ToModel,
    WithHeadingRow,
    WithUpserts,
    WithUpsertColumns,
    WithValidation,
    SkipsEmptyRows
{
    private string $defaultHash;

    public function __construct()
    {
        $this->defaultHash = Hash::make(config('auth.default_password'));
    }
    public function prepareForValidation(array $row): array
    {
        $row['role_code'] = strtoupper(trim((string) ($row['role_code'] ?? '')));
        $row['username']  = strtolower(trim((string) ($row['username'] ?? '')));
        $row['name']      = trim((string) ($row['name'] ?? ''));

        return $row;
    }

    public function model(array $row): Model|array|null
    {
        return new User([
            'role_code'           => $row['role_code'],
            'name'                => $row['name'],
            'username'            => $row['username'],
            'password_hash'       => $this->defaultHash,
            'is_password_default' => true,
        ]);
    }

    public function uniqueBy(): string|array
    {
        return 'username';
    }

    // import ulang tidak me-reset password user yang sudah ada
    public function upsertColumns(): array
    {
        return ['role_code', 'name'];
    }

    public function rules(): array
    {
        return [
            'role_code' => ['required', 'exists:roles,code'],
            'name'      => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:100'],
        ];
    }
}
