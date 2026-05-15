<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformRecord extends Model
{
    protected $fillable = [
        'section',
        'record_id',
        'store_id',
        'partner_id',
        'status',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }
}
