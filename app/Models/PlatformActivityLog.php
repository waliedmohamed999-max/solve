<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformActivityLog extends Model
{
    protected $fillable = [
        'actor_type',
        'actor_id',
        'actor_name',
        'role',
        'store_id',
        'partner_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }
}
