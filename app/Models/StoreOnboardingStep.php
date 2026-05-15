<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreOnboardingStep extends Model
{
    protected $fillable = [
        'store_id',
        'step_key',
        'title',
        'status',
        'payload',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
