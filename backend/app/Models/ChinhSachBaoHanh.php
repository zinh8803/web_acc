<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChinhSachBaoHanh extends Model
{
    protected $table = 'chinh_sach_bao_hanh';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_chinh_sach',
        'ten_chinh_sach',
        'loai_san_pham',
        'so_ngay_bao_hanh',
        'bao_hanh_tron_doi',
        'pham_vi_bao_hanh',
        'dieu_khoan_loai_tru',
        'so_lan_yeu_cau_toi_da',
        'phuong_an_giai_quyet',
        'thoi_gian_xu_ly_gio',
        'mo_ta',
        'trang_thai',
    ];

    protected $casts = [
        'bao_hanh_tron_doi' => 'boolean',
        'pham_vi_bao_hanh' => 'array',
        'phuong_an_giai_quyet' => 'array',
        'so_ngay_bao_hanh' => 'integer',
        'so_lan_yeu_cau_toi_da' => 'integer',
        'thoi_gian_xu_ly_gio' => 'integer',
    ];

    protected $appends = [
        'name',
        'product_type',
        'duration_days',
        'is_lifetime',
        'claim_sla_hours',
        'status',
        'description',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['ten_chinh_sach'] ?? null;
    }

    public function getProductTypeAttribute(): string
    {
        return $this->attributes['loai_san_pham'] ?? 'TAI_KHOAN';
    }

    public function setLoaiSanPhamAttribute($value): void
    {
        $val = strtoupper((string) $value);
        if ($val === 'ACCOUNT' || $val === 'TAIKHOAN' || $val === 'TAI_KHOAN') {
            $this->attributes['loai_san_pham'] = 'TAI_KHOAN';
        } elseif ($val === 'SERVICE' || $val === 'DICHVU' || $val === 'DICH_VU') {
            $this->attributes['loai_san_pham'] = 'DICH_VU';
        } else {
            $this->attributes['loai_san_pham'] = $val ?: 'TAI_KHOAN';
        }
    }

    public function getDurationDaysAttribute(): ?int
    {
        return isset($this->attributes['so_ngay_bao_hanh']) ? (int) $this->attributes['so_ngay_bao_hanh'] : null;
    }

    public function getIsLifetimeAttribute(): bool
    {
        return (bool) ($this->attributes['bao_hanh_tron_doi'] ?? false);
    }

    public function getClaimSlaHoursAttribute(): int
    {
        return (int) ($this->attributes['thoi_gian_xu_ly_gio'] ?? 24);
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? 'ACTIVE';
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['mo_ta'] ?? null;
    }

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'chinh_sach_bao_hanh_id');
    }

    public function chiTietDonHangs(): HasMany
    {
        return $this->hasMany(ChiTietDonHang::class, 'chinh_sach_bao_hanh_id');
    }
}
