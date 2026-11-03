<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanhMuc extends Model
{
    protected $table = 'danh_muc';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'danh_muc_cha_id',
        'loai_danh_muc',
        'ten_danh_muc',
        'duong_dan_tinh',
        'mo_ta',
        'anh_danh_muc',
        'cap_do',
        'duong_dan_cay',
        'thu_tu_hien_thi',
        'trang_thai',
        'tieu_de_seo',
        'mo_ta_seo',
    ];

    protected $casts = [
        'cap_do' => 'integer',
        'thu_tu_hien_thi' => 'integer',
    ];

    protected $appends = [
        'name',
        'slug',
        'description',
        'image_url',
        'type',
        'status',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['ten_danh_muc'] ?? null;
    }

    public function getSlugAttribute(): ?string
    {
        return $this->attributes['duong_dan_tinh'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['mo_ta'] ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->attributes['anh_danh_muc'] ?? null;
    }

    public function getTypeAttribute(): ?string
    {
        return $this->attributes['loai_danh_muc'] ?? null;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function danhMucCha(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_cha_id');
    }

    public function parent(): BelongsTo
    {
        return $this->danhMucCha();
    }

    public function danhMucCon(): HasMany
    {
        return $this->hasMany(DanhMuc::class, 'danh_muc_cha_id')->orderBy('thu_tu_hien_thi');
    }

    public function children(): HasMany
    {
        return $this->danhMucCon();
    }

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'danh_muc_id');
    }

    public function products(): HasMany
    {
        return $this->sanPhams();
    }
}
