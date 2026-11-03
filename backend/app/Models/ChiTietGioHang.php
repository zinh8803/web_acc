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

    public function gioHang(): BelongsTo
    {
        return $this->belongsTo(GioHang::class, 'gio_hang_id');
    }

    public function cart(): BelongsTo
    {
        return $this->gioHang();
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
}

