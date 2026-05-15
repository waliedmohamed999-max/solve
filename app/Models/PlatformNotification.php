<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformNotification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'body',
        'store_id',
        'partner_id',
        'severity',
        'url',
        'read_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'payload' => 'array',
        ];
    }
}
