<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class QuanTriVien extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'nguoi_dung';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'email',
        'mat_khau_ma_hoa',
        'ho_ten',
        'vai_tro',
        'trang_thai',
        'ma_bi_mat_2fa',
        'dang_nhap_cuoi_luc',
    ];

    protected $hidden = [
        'mat_khau_ma_hoa',
        'ma_bi_mat_2fa',
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau_ma_hoa;
    }

    public function isSuperAdmin(): bool
    {
        return $this->vai_tro === 'SUPER_ADMIN';
    }
}

