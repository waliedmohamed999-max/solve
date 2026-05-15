<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerUser extends Model
{
    protected $fillable = [
        'partner_store_id',
        'store_id',
        'name',
        'username',
        'email',
        'password_hash',
        'role',
        'status',
        'abilities',
        'invite_token',
        'invite_expires_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
        'invite_token',
    ];

    protected function casts(): array
    {
        return [
            'abilities' => 'array',
            'invite_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(PartnerStore::class, 'partner_store_id');
    }
}
