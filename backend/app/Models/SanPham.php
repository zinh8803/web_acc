<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'tieu_de_seo',
        'mo_ta_seo',
        'nguoi_tao_id',
    ];

    protected $casts = [
        'gia_thap_nhat' => 'decimal:0',
        'noi_bat' => 'boolean',
        'cau_hinh_bieu_mau_dich_vu' => 'array',
        'diem_danh_gia_tb' => 'float',
    ];

    protected $appends = [
        'name',
        'slug',
        'sku',
        'type',
        'short_description',
        'description',
        'thumbnail_url',
        'min_price',
        'status',
        'is_featured',
    ];

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

    public function getTypeAttribute(): ?string
    {
        return $this->attributes['loai_san_pham'] ?? null;
    }

    public function getShortDescriptionAttribute(): ?string
    {
        return $this->attributes['mo_ta_ngan'] ?? null;
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->attributes['noi_dung_chi_tiet'] ?? null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->attributes['anh_dai_dien'] ?? null;
    }

    public function getMinPriceAttribute(): mixed
    {
        return $this->attributes['gia_thap_nhat'] ?? 0;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function getIsFeaturedAttribute(): bool
    {
        return (bool)($this->attributes['noi_bat'] ?? false);
    }

    public function danhMuc(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_id');
    }

    public function category(): BelongsTo
    {
        return $this->danhMuc();
    }

    public function bienThes(): HasMany
    {
        return $this->hasMany(BienTheSanPham::class, 'san_pham_id');
    }

    public function variants(): HasMany
    {
        return $this->bienThes();
    }

    public function hinhAnhs(): HasMany
    {
        return $this->hasMany(HinhAnhSanPham::class, 'san_pham_id')->orderBy('thu_tu_hien_thi');
    }

    public function images(): HasMany
    {
        return $this->hinhAnhs();
    }

    public function anhChinh(): HasOne
    {
        return $this->hasOne(HinhAnhSanPham::class, 'san_pham_id')->where('anh_chinh', true);
    }

    public function primaryImage(): HasOne
    {
        return $this->anhChinh();
    }

    public function chinhSachBaoHanh(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function warrantyPolicy(): BelongsTo
    {
        return $this->chinhSachBaoHanh();
    }

    public function taiKhoanGames(): HasMany
    {
        return $this->hasMany(TaiKhoanGame::class, 'san_pham_id');
    }

    public function gameAccounts(): HasMany
    {
        return $this->taiKhoanGames();
    }

    public function danhGias(): HasMany
    {
        return $this->hasMany(DanhGia::class, 'san_pham_id');
    }

    public function reviews(): HasMany
    {
        return $this->danhGias();
    }
}
