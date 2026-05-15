<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteMedia extends Model
{
    protected $fillable = [
        'purpose',
        'original_name',
        'path',
        'disk',
        'mime_type',
        'size',
    ];
}