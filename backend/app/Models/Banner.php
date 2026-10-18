<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'image_url', 'link_url', 'title', 'sort_order', 'status', 'starts_at', 'ends_at'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
