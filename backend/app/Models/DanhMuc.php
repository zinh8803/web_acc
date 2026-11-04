<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanhMuc extends Model
{
    protected $table = 'danh_muc';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'danh_muc_cha_id',
        'loai_danh_muc',
        'ten_danh_muc',
        'duong_dan_tinh',
        'mo_ta',
        'cap_do',
        'thu_tu_hien_thi',
        'trang_thai',
    ];

    protected $casts = [
        'cap_do' => 'integer',
        'thu_tu_hien_thi' => 'integer',
    ];

    public function danhMucCha(): BelongsTo
    {
        return $this->belongsTo(DanhMuc::class, 'danh_muc_cha_id');
    }

    public function danhMucCon(): HasMany
    {
        return $this->hasMany(DanhMuc::class, 'danh_muc_cha_id')->orderBy('thu_tu_hien_thi');
    }

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'danh_muc_id');
    }
}
