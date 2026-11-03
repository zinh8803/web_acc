<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GioHang extends Model
{
    protected $table = 'gio_hang';

    const CREATED_AT = 'ngay_tao';
    const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = ['nguoi_dung_id'];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id');
    }

    public function user(): BelongsTo
    {
        return $this->nguoiDung();
    }

    public function chiTiets(): HasMany
    {
        return $this->hasMany(ChiTietGioHang::class, 'gio_hang_id');
    }

    public function items(): HasMany
    {
        return $this->chiTiets();
    }
}

