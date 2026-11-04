<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class NguoiDung extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'nguoi_dung';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'email',
        'so_dien_thoai',
        'mat_khau_ma_hoa',
        'ho_ten',
        'vai_tro',
        'trang_thai',
        'thoi_gian_xac_thuc_email',
        'dang_nhap_cuoi_luc',
        'ip_dang_nhap_cuoi',
    ];

    protected $hidden = [
        'mat_khau_ma_hoa',
    ];

    protected $casts = [
        'thoi_gian_xac_thuc_email' => 'datetime',
        'dang_nhap_cuoi_luc' => 'datetime',
    ];

    public function getAuthPassword(): string
    {
        return $this->mat_khau_ma_hoa;
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->thoi_gian_xac_thuc_email);
    }

    public function isBanned(): bool
    {
        return $this->trang_thai === 'BANNED';
    }

    // Quan hệ
    public function donHangs()
    {
        return $this->hasMany(DonHang::class, 'nguoi_dung_id');
    }

    public function gioHang()
    {
        return $this->hasOne(GioHang::class, 'nguoi_dung_id');
    }

    public function yeuCauBaoHanhs()
    {
        return $this->hasMany(YeuCauBaoHanh::class, 'nguoi_dung_id');
    }

    public function danhGias()
    {
        return $this->hasMany(DanhGia::class, 'nguoi_dung_id');
    }
}
