<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiTietGioHang extends Model
{
    protected $table = 'chi_tiet_gio_hang';

    public $timestamps = false;

    protected $fillable = [
        'gio_hang_id',
        'bien_the_id',
        'tai_khoan_game_id',
        'so_luong',
        'ngay_them',
    ];

    protected $casts = [
        'so_luong' => 'integer',
        'ngay_them' => 'datetime',
    ];

    protected $appends = [
        'variant_id',
        'game_account_id',
        'quantity',
    ];

    public function getVariantIdAttribute()
    {
        return $this->bien_the_id;
    }

    public function getGameAccountIdAttribute()
    {
        return $this->tai_khoan_game_id;
    }

    public function getQuantityAttribute()
    {
        return $this->so_luong;
    }

    public function gioHang(): BelongsTo
    {
        return $this->belongsTo(GioHang::class, 'gio_hang_id');
    }

    public function bienThe(): BelongsTo
    {
        return $this->belongsTo(BienTheSanPham::class, 'bien_the_id');
    }

    public function taiKhoanGame(): BelongsTo
    {
        return $this->belongsTo(TaiKhoanGame::class, 'tai_khoan_game_id');
    }
}
