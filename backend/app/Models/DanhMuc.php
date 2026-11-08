<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanhMuc extends Model
{
    protected $table = 'danh_muc';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'loai_danh_muc',
        'ten_danh_muc',
        'duong_dan_tinh',
        'mo_ta',
        'thu_tu_hien_thi',
        'trang_thai',
        // Aliases allowed in mass assignment
        'name',
        'slug',
        'type',
        'description',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'thu_tu_hien_thi' => 'integer',
    ];

    protected $appends = [
        'name',
        'slug',
        'type',
        'description',
        'sort_order',
        'status',
    ];

    // Accessors / Mutators for English compatibility
    public function getNameAttribute(): ?string
    {
        return $this->attributes['ten_danh_muc'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['ten_danh_muc'] = $value;
    }

    public function getSlugAttribute(): ?string
    {
        return $this->attributes['duong_dan_tinh'] ?? null;
    }

    public function setSlugAttribute($value): void
    {
        $this->attributes['duong_dan_tinh'] = $value;
    }

    public function getTypeAttribute(): string
    {
        return $this->attributes['loai_danh_muc'] ?? 'TAI_KHOAN';
    }

    public function setTypeAttribute($value): void
    {
        $val = strtoupper((string) $value);
        if ($val === 'ACCOUNT' || $val === 'TAIKHOAN' || $val === 'TAI_KHOAN') {
            $this->attributes['loai_danh_muc'] = 'TAI_KHOAN';
        } elseif ($val === 'SERVICE' || $val === 'DICHVU' || $val === 'DICH_VU') {
            $this->attributes['loai_danh_muc'] = 'DICH_VU';
        } else {
            $this->attributes['loai_danh_muc'] = $val ?: 'TAI_KHOAN';
        }
    }

    public function setLoaiDanhMucAttribute($value): void
    {
        $this->setTypeAttribute($value);
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['mo_ta'] ?? null;
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['mo_ta'] = $value;
    }

    public function getSortOrderAttribute(): int
    {
        return (int) ($this->attributes['thu_tu_hien_thi'] ?? 0);
    }

    public function setSortOrderAttribute($value): void
    {
        $this->attributes['thu_tu_hien_thi'] = (int) $value;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? 'ACTIVE';
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trang_thai'] = $value;
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
