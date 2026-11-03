<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BannerQuangCao extends Model
{
    protected $table = 'banner_quang_cao';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'duong_dan_anh',
        'duong_dan_lien_ket',
        'tieu_de',
        'thu_tu_hien_thi',
        'trang_thai',
        'ngay_bat_dau',
        'ngay_ket_thuc',
    ];

    protected $casts = [
        'thu_tu_hien_thi' => 'integer',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
    ];

    protected $appends = [
        'title',
        'image_url',
        'link_url',
        'sort_order',
        'status',
    ];

    public function getTitleAttribute(): ?string
    {
        return $this->attributes['tieu_de'] ?? null;
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->attributes['duong_dan_anh'] ?? null;
    }

    public function getLinkUrlAttribute(): ?string
    {
        return $this->attributes['duong_dan_lien_ket'] ?? null;
    }

    public function getSortOrderAttribute(): int
    {
        return (int) ($this->attributes['thu_tu_hien_thi'] ?? 0);
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }
}

