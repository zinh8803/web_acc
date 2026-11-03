<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HinhAnhSanPham extends Model
{
    protected $table = 'hinh_anh_san_pham';

    public $timestamps = false;

    protected $fillable = [
        'san_pham_id',
        'bien_the_id',
        'duong_dan_anh',
        'duong_dan_anh_nho',
        'chu_thich_anh',
        'thu_tu_hien_thi',
        'anh_chinh',
        'ngay_tao',
    ];

    protected $casts = [
        'anh_chinh' => 'boolean',
        'thu_tu_hien_thi' => 'integer',
    ];

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'san_pham_id');
    }

    public function product(): BelongsTo
    {
        return $this->sanPham();
    }
}

