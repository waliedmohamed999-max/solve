<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceApp extends Model
{
    protected $fillable = [
        'name',
        'category',
        'provider',
        'status',
        'description',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
        ];
    }
}
