<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BienTheSanPham extends Model
{
    protected $table = 'bien_the_san_pham';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'san_pham_id',
        'ma_bien_the',
        'ten_bien_the',
        'gia_goc',
        'gia_so_sanh',
        'gia_khuyen_mai',
        'ngay_bat_dau_km',
        'ngay_ket_thuc_km',
        'gia_von',
        'so_luong_ton_kho',
        'thoi_gian_uoc_tinh_gio',
        'trang_thai',
        'thu_tu_hien_thi',
    ];

    protected $casts = [
        'gia_goc' => 'decimal:0',
        'gia_so_sanh' => 'decimal:0',
        'gia_khuyen_mai' => 'decimal:0',
        'gia_von' => 'decimal:0',
        'so_luong_ton_kho' => 'integer',
        'thu_tu_hien_thi' => 'integer',
        'thoi_gian_uoc_tinh_gio' => 'integer',
        'ngay_bat_dau_km' => 'datetime',
        'ngay_ket_thuc_km' => 'datetime',
    ];

    protected $appends = [
        'name',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'status',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['ten_bien_the'] ?? null;
    }

    public function getSkuAttribute(): ?string
    {
        return $this->attributes['ma_bien_the'] ?? null;
    }

    public function getPriceAttribute(): mixed
    {
        return $this->attributes['gia_goc'] ?? 0;
    }

    public function getSalePriceAttribute(): mixed
    {
        return $this->attributes['gia_khuyen_mai'] ?? null;
    }

    public function getStockQuantityAttribute(): int
    {
        return (int)($this->attributes['so_luong_ton_kho'] ?? 0);
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function product(): BelongsTo
    {
        return $this->sanPham();
    }

    public function taiKhoanGames(): HasMany
    {
        return $this->hasMany(TaiKhoanGame::class, 'bien_the_id');
    }

    public function gameAccounts(): HasMany
    {
        return $this->taiKhoanGames();
    }
}
