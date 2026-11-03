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
        'ma_bi_mat_2fa',
        'anh_dai_dien',
        'ngay_sinh',
        'trang_thai',
        'thoi_gian_xac_thuc_email',
        'so_lan_dang_nhap_sai',
        'khoa_den_thoi_gian',
        'dang_nhap_cuoi_luc',
        'ip_dang_nhap_cuoi',
    ];

    protected $hidden = [
        'mat_khau_ma_hoa',
        'ma_bi_mat_2fa',
    ];

    protected $casts = [
        'thoi_gian_xac_thuc_email' => 'datetime',
        'khoa_den_thoi_gian' => 'datetime',
        'dang_nhap_cuoi_luc' => 'datetime',
        'ngay_sinh' => 'date',
    ];

    protected $appends = [
        'full_name',
        'phone',
        'role',
        'status',
        'avatar_url',
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

    public function isLocked(): bool
    {
        return $this->khoa_den_thoi_gian && $this->khoa_den_thoi_gian->isFuture();
    }

    // Accessor aliases
    public function getPasswordHashAttribute(): ?string
    {
        return $this->attributes['mat_khau_ma_hoa'] ?? null;
    }

    public function getFailedLoginCountAttribute(): int
    {
        return (int) ($this->attributes['so_lan_dang_nhap_sai'] ?? 0);
    }

    public function getFullNameAttribute(): ?string
    {
        return $this->attributes['ho_ten'] ?? null;
    }

    public function setPasswordHashAttribute($value)
    {
        $this->attributes['mat_khau_ma_hoa'] = $value;
    }

    public function setFailedLoginCountAttribute($value)
    {
        $this->attributes['so_lan_dang_nhap_sai'] = $value;
    }

    public function setLockedUntilAttribute($value)
    {
        $this->attributes['khoa_den_thoi_gian'] = $value;
    }

    public function setLastLoginAtAttribute($value)
    {
        $this->attributes['dang_nhap_cuoi_luc'] = $value;
    }

    public function setLastLoginIpAttribute($value)
    {
        $this->attributes['ip_dang_nhap_cuoi'] = $value;
    }

    public function setFullNameAttribute($value)
    {
        $this->attributes['ho_ten'] = $value;
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['so_dien_thoai'] = $value;
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['trang_thai'] = $value;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->attributes['so_dien_thoai'] ?? null;
    }

    public function getRoleAttribute(): ?string
    {
        return $this->attributes['vai_tro'] ?? null;
    }

    public function getStatusAttribute(): ?string
    {
        return $this->attributes['trang_thai'] ?? null;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->attributes['anh_dai_dien'] ?? null;
    }

    // Quan hệ
    public function donHangs()
    {
        return $this->hasMany(DonHang::class, 'nguoi_dung_id');
    }

    public function orders()
    {
        return $this->donHangs();
    }

    public function gioHang()
    {
        return $this->hasOne(GioHang::class, 'nguoi_dung_id');
    }

    public function cart()
    {
        return $this->gioHang();
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
