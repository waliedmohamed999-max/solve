<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = [
        'store_id',
        'identity',
        'branding',
        'payments',
        'shipping',
        'taxes',
        'invoices',
    ];

    protected function casts(): array
    {
        return [
            'identity' => 'array',
            'branding' => 'array',
            'payments' => 'array',
            'shipping' => 'array',
            'taxes' => 'array',
            'invoices' => 'array',
        ];
    }
}
