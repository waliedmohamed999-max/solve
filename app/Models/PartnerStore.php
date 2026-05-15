<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerStore extends Model
{
    protected $fillable = [
        'partner_id',
        'store_id',
        'name',
        'brand_name',
        'owner_name',
        'owner_email',
        'owner_phone',
        'status',
        'plan',
        'domain',
        'store_url',
        'logo',
        'payment_status',
        'subscription_started_at',
        'subscription_renews_at',
        'payment_provider',
        'shipping_provider',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'subscription_started_at' => 'date',
            'subscription_renews_at' => 'date',
            'metadata' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(PartnerUser::class, 'store_id', 'store_id');
    }
}
