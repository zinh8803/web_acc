<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaGiamGia extends Model
{
    protected $table = 'ma_giam_gia';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_khuyen_mai',
        'ten_ma_giam_gia',
        'mo_ta',
        'loai_giam_gia',
        'gia_tri_giam',
        'giam_toi_da',
        'don_hang_toi_thieu',
        'ap_dung_cho',
        'ap_dung_loai_san_pham',
        'gioi_han_su_dung',
        'gioi_han_moi_nguoi_dung',
        'so_lan_da_dung',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'trang_thai',
        'nguoi_tao_id',
        // Aliases allowed in fill
        'ma_giam_gia',
        'ten_ma_giam',
        'code',
        'name',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_per_user',
        'starts_at',
        'ends_at',
        'status',
        'gia_tri_don_toi_thieu',
        'gioi_han_tong_luot_dung',
        'gioi_han_moi_nguoi',
        'so_luot_da_dung',
        'bat_dau_luc',
        'ket_thuc_luc',
    ];

    protected $casts = [
        'gia_tri_giam' => 'decimal:2',
        'giam_toi_da' => 'decimal:0',
        'don_hang_toi_thieu' => 'decimal:0',
        'so_lan_da_dung' => 'integer',
        'gioi_han_su_dung' => 'integer',
        'gioi_han_moi_nguoi_dung' => 'integer',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
    ];

    protected $appends = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'usage_limit',
        'usage_per_user',
        'used_count',
        'starts_at',
        'ends_at',
        'status',
    ];

    // Accessors & Mutators for code / ma_khuyen_mai / ma_giam_gia
    public function getCodeAttribute(): ?string
    {
        return $this->attributes['ma_khuyen_mai'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['ma_khuyen_mai'] = $value;
    }

    public function getMaGiamGiaAttribute(): ?string
    {
        return $this->attributes['ma_khuyen_mai'] ?? null;
    }

    public function setMaGiamGiaAttribute($value): void
    {
        $this->attributes['ma_khuyen_mai'] = $value;
    }

    // Name / ten_ma_giam_gia / ten_ma_giam
    public function getNameAttribute(): ?string
    {
        return $this->attributes['ten_ma_giam_gia'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['ten_ma_giam_gia'] = $value;
    }

    public function getTenMaGiamAttribute(): ?string
    {
        return $this->attributes['ten_ma_giam_gia'] ?? null;
    }

    public function setTenMaGiamAttribute($value): void
    {
        $this->attributes['ten_ma_giam_gia'] = $value;
    }

    // discount_type
    public function getDiscountTypeAttribute(): ?string
    {
        return $this->attributes['loai_giam_gia'] ?? null;
    }

    public function setDiscountTypeAttribute($value): void
    {
        $this->attributes['loai_giam_gia'] = $value;
    }

    // discount_value
    public function getDiscountValueAttribute(): mixed
    {
        return $this->attributes['gia_tri_giam'] ?? null;
    }

    public function setDiscountValueAttribute($value): void
    {
        $this->attributes['gia_tri_giam'] = $value;
    }

    // max_discount_amount
    public function getMaxDiscountAmountAttribute(): mixed
    {
        return $this->attributes['giam_toi_da'] ?? null;
    }

    public function setMaxDiscountAmountAttribute($value): void
    {
        $this->attributes['giam_toi_da'] = $value;
    }

    // min_order_amount / don_hang_toi_thieu / gia_tri_don_toi_thieu
    public function getMinOrderAmountAttribute(): mixed
    {
        return $this->attributes['don_hang_toi_thieu'] ?? null;
    }

    public function setMinOrderAmountAttribute($value): void
    {
        $this->attributes['don_hang_toi_thieu'] = $value;
    }

    public function getGiaTriDonToiThieuAttribute(): mixed
    {
        return $this->attributes['don_hang_toi_thieu'] ?? null;
    }

    public function setGiaTriDonToiThieuAttribute($value): void
    {
        $this->attributes['don_hang_toi_thieu'] = $value;
    }

    // usage_limit / gioi_han_su_dung / gioi_han_tong_luot_dung
    public function getUsageLimitAttribute(): mixed
    {
        return $this->attributes['gioi_han_su_dung'] ?? null;
    }

    public function setUsageLimitAttribute($value): void
    {
        $this->attributes['gioi_han_su_dung'] = $value;
    }

    public function getGioiHanTongLuotDungAttribute(): mixed
    {
        return $this->attributes['gioi_han_su_dung'] ?? null;
    }

    public function setGioiHanTongLuotDungAttribute($value): void
    {
        $this->attributes['gioi_han_su_dung'] = $value;
    }

    // usage_per_user / gioi_han_moi_nguoi_dung / gioi_han_moi_nguoi
    public function getUsagePerUserAttribute(): mixed
    {
        return $this->attributes['gioi_han_moi_nguoi_dung'] ?? null;
    }

    public function setUsagePerUserAttribute($value): void
    {
        $this->attributes['gioi_han_moi_nguoi_dung'] = $value;
    }

    public function getGioiHanMoiNguoiAttribute(): mixed
    {
        return $this->attributes['gioi_han_moi_nguoi_dung'] ?? null;
    }

    public function setGioiHanMoiNguoiAttribute($value): void
    {
        $this->attributes['gioi_han_moi_nguoi_dung'] = $value;
    }

    // used_count / so_lan_da_dung / so_luot_da_dung
    public function getUsedCountAttribute(): mixed
    {
        return $this->attributes['so_lan_da_dung'] ?? 0;
    }

    public function setUsedCountAttribute($value): void
    {
        $this->attributes['so_lan_da_dung'] = $value;
    }

    public function getSoLuotDaDungAttribute(): mixed
    {
        return $this->attributes['so_lan_da_dung'] ?? 0;
    }

    public function setSoLuotDaDungAttribute($value): void
    {
        $this->attributes['so_lan_da_dung'] = $value;
    }

    // starts_at / ngay_bat_dau / bat_dau_luc
    public function getStartsAtAttribute(): mixed
    {
        return $this->ngay_bat_dau;
    }

    public function setStartsAtAttribute($value): void
    {
        $this->attributes['ngay_bat_dau'] = $value;
    }

    public function getBatDauLucAttribute(): mixed
    {
        return $this->ngay_bat_dau;
    }

    public function setBatDauLucAttribute($value): void
    {
        $this->attributes['ngay_bat_dau'] = $value;
    }

    // ends_at / ngay_ket_thuc / ket_thuc_luc
    public function getEndsAtAttribute(): mixed
    {
        return $this->ngay_ket_thuc;
    }

    public function setEndsAtAttribute($value): void
    {
        $this->attributes['ngay_ket_thuc'] = $value;
    }

    public function getKetThucLucAttribute(): mixed
    {
        return $this->ngay_ket_thuc;
    }

    public function setKetThucLucAttribute($value): void
    {
        $this->attributes['ngay_ket_thuc'] = $value;
    }

    // status / trang_thai
    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['trang_thai'] = $value;
    }
}
