<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sku', 'type', 'category_id', 'game_id', 'name', 'slug',
        'short_description', 'description', 'thumbnail_url',
        'warranty_policy_id', 'min_price', 'status', 'is_featured',
        'service_form_schema', 'sold_count', 'view_count',
        'rating_avg', 'rating_count', 'seo_title', 'seo_description', 'created_by'
    ];

    protected $casts = [
        'min_price' => 'decimal:0',
        'is_featured' => 'boolean',
        'service_form_schema' => 'array',
        'rating_avg' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function gameAccounts(): HasMany
    {
        return $this->hasMany(GameAccount::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
