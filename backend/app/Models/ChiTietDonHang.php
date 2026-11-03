<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChiTietDonHang extends Model
{
    protected $table = 'chi_tiet_don_hang';

    public $timestamps = false;

    protected $fillable = [
        'don_hang_id',
        'loai_muc',
        'san_pham_id',
        'bien_the_id',
        'ten_san_pham_luu_lai',
        'ten_bien_the_luu_lai',
        'anh_san_pham_luu_lai',
        'don_gia',
        'so_luong',
        'thanh_tien',
        'tai_khoan_game_id',
        'chinh_sach_bao_hanh_id',
        'thong_tin_bao_hanh_luu_lai',
        'het_han_bao_hanh_luc',
        'trang_thai_giao_hang',
        'giao_hang_luc',
        'ngay_tao',
    ];

    protected $casts = [
        'don_gia' => 'decimal:2',
        'thanh_tien' => 'decimal:0',
        'so_luong' => 'integer',
        'thong_tin_bao_hanh_luu_lai' => 'array',
        'het_han_bao_hanh_luc' => 'datetime',
        'giao_hang_luc' => 'datetime',
    ];

    protected $appends = [
        'item_type',
        'product_name',
        'variant_name',
        'thumbnail_url',
        'unit_price',
        'quantity',
        'line_total',
        'game_account_id',
        'order_id',
    ];

    public function getItemTypeAttribute(): ?string
    {
        return $this->attributes['loai_muc'] ?? null;
    }

    public function getProductNameAttribute(): ?string
    {
        return $this->attributes['ten_san_pham_luu_lai'] ?? null;
    }

    public function getVariantNameAttribute(): ?string
    {
        return $this->attributes['ten_bien_the_luu_lai'] ?? null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->attributes['anh_san_pham_luu_lai'] ?? null;
    }

    public function getUnitPriceAttribute(): mixed
    {
        return $this->attributes['don_gia'] ?? 0;
    }

    public function getQuantityAttribute(): mixed
    {
        return $this->attributes['so_luong'] ?? 1;
    }

    public function getLineTotalAttribute(): mixed
    {
        return $this->attributes['thanh_tien'] ?? 0;
    }

    public function getGameAccountIdAttribute(): ?int
    {
        return $this->attributes['tai_khoan_game_id'] ?? null;
    }

    public function getOrderIdAttribute(): ?int
    {
        return $this->attributes['don_hang_id'] ?? null;
    }

    public function donHang(): BelongsTo
    {
        return $this->belongsTo(DonHang::class, 'don_hang_id');
    }

    public function order(): BelongsTo
    {
        return $this->donHang();
    }

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function product(): BelongsTo
    {
        return $this->sanPham();
    }

    public function bienThe(): BelongsTo
    {
        return $this->belongsTo(BienTheSanPham::class, 'bien_the_id');
    }

    public function variant(): BelongsTo
    {
        return $this->bienThe();
    }

    public function taiKhoanGame(): BelongsTo
    {
        return $this->belongsTo(TaiKhoanGame::class, 'tai_khoan_game_id');
    }

    public function gameAccount(): BelongsTo
    {
        return $this->taiKhoanGame();
    }

    public function yeuCauDichVu(): HasOne
    {
        return $this->hasOne(YeuCauDichVu::class, 'chi_tiet_don_hang_id');
    }

    public function serviceRequest(): HasOne
    {
        return $this->yeuCauDichVu();
    }

    public function chinhSachBaoHanh(): BelongsTo
    {
        return $this->belongsTo(ChinhSachBaoHanh::class, 'chinh_sach_bao_hanh_id');
    }

    public function warrantyPolicy(): BelongsTo
    {
        return $this->chinhSachBaoHanh();
    }

    public function yeuCauBaoHanhs(): HasMany
    {
        return $this->hasMany(YeuCauBaoHanh::class, 'chi_tiet_don_hang_id');
    }

    public function warrantyClaims(): HasMany
    {
        return $this->yeuCauBaoHanhs();
    }
}

