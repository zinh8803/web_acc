<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanPham extends Model
{
    use HasFactory;

    protected $table = 'san_pham';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_san_pham',
        'loai_san_pham',
        'danh_muc_id',
        'ten_san_pham',
        'duong_dan_tinh',
        'mo_ta_ngan',
        'noi_dung_chi_tiet',
        'anh_dai_dien',
        'chinh_sach_bao_hanh_id',
        'gia_thap_nhat',
        'trang_thai',
        'noi_bat',
        'cau_hinh_bieu_mau_dich_vu',
        'so_luong_da_ban',
        'luot_xem',
        'diem_danh_gia_tb',
        'so_luot_danh_gia',
    ];

    protected $casts = [
        'gia_thap_nhat' => 'decimal:0',
        'noi_bat' => 'boolean',
        'cau_hinh_bieu_mau_dich_vu' => 'array',
        'diem_danh_gia_tb' => 'float',
        'so_luong_da_ban' => 'integer',
        'luot_xem' => 'integer',
        'so_luot_danh_gia' => 'integer',
    ];

    protected $appends = [
        'name',
        'slug',
        'sku',
        'thumbnail_url',
        'min_price',
        'type',
        'status',
        'is_featured',
        'short_description',
        'description',
        'rating_avg',
        'rating_count',
        'sold_count',
        'view_count',
        'category_id',
        'warranty_policy_id',
    ];

    // English compatibility accessors
    public function getNameAttribute(): ?string
    {
        return $this->attributes['ten_san_pham'] ?? null;
    }

    public function getSlugAttribute(): ?string
    {
        return $this->attributes['duong_dan_tinh'] ?? null;
    }

    public function getSkuAttribute(): ?string
    {
        return $this->attributes['ma_san_pham'] ?? null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->attributes['anh_dai_dien'] ?? null;
    }

    public function getMinPriceAttribute(): ?float
    {
        return isset($this->attributes['gia_thap_nhat']) ? (float) $this->attributes['gia_thap_nhat'] : null;
    }

    public function getTypeAttribute(): string
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

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? 'ACTIVE';
    }

    public function getIsFeaturedAttribute(): bool
    {
        return (bool) ($this->attributes['noi_bat'] ?? false);
    }

    public function getShortDescriptionAttribute(): ?string
    {
        return $this->attributes['mo_ta_ngan'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['noi_dung_chi_tiet'] ?? null;
    }

    public function getRatingAvgAttribute(): float
    {
        return (float) ($this->attributes['diem_danh_gia_tb'] ?? 5.0);
    }

    public function getRatingCountAttribute(): int
    {
        return (int) ($this->attributes['so_luot_danh_gia'] ?? 0);
    }

    public function getSoldCountAttribute(): int
    {
        return (int) ($this->attributes['so_luong_da_ban'] ?? 0);
    }

    public function getViewCountAttribute(): int
    {
        return (int) ($this->attributes['luot_xem'] ?? 0);
    }

    public function getCategoryIdAttribute(): ?int
    {
        return $this->attributes['danh_muc_id'] ?? null;
    }

    public function getWarrantyPolicyIdAttribute(): ?int
    {
        return $this->attributes['chinh_sach_bao_hanh_id'] ?? null;
    }

    // Relationships
    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function bienThes(): HasMany
    {
        return $this->hasMany(BienTheSanPham::class, 'san_pham_id');
    }

    public function chinhSachBaoHanh(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function taiKhoanGames(): HasMany
    {
        return $this->hasMany(TaiKhoanGame::class, 'san_pham_id');
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGia::class, 'san_pham_id');
    }
}
