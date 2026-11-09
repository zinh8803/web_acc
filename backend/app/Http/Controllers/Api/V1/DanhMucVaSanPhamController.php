<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DanhMuc;
use App\Models\SanPham;
use App\Models\TaiKhoanGame;
use App\Models\BannerQuangCao;
use App\Models\CaiDat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DanhMucVaSanPhamController extends Controller
{
    /**
     * Danh sách trò chơi (Legacy).
     */
    public function troChoi(): JsonResponse
    {
        return response()->json(['thanh_cong' => true, 'success' => true, 'du_lieu' => [], 'data' => []]);
    }

    /**
     * Danh sách danh mục.
     */
    public function danhMuc(Request $request): JsonResponse
    {
        $query = DanhMuc::where('trang_thai', 'ACTIVE')
            ->orderBy('thu_tu_hien_thi');

        $loai = $request->loai ?? $request->type;
        if ($loai) {
            $loaiUpper = strtoupper((string) $loai);
            if (in_array($loaiUpper, ['ACCOUNT', 'TAI_KHOAN', 'TAIKHOAN'])) {
                $query->whereIn('loai_danh_muc', ['TAI_KHOAN', 'ACCOUNT']);
            } elseif (in_array($loaiUpper, ['SERVICE', 'DICH_VU', 'DICHVU'])) {
                $query->whereIn('loai_danh_muc', ['DICH_VU', 'SERVICE']);
            } else {
                $query->where('loai_danh_muc', $loaiUpper);
            }
        }

        $items = $query->get()->map(function ($item) {
            $arr = $item->toArray();
            $arr['name'] = $item->ten_danh_muc;
            $arr['slug'] = $item->duong_dan_tinh;
            $arr['type'] = $item->loai_danh_muc;
            $arr['description'] = $item->mo_ta;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $items,
            'data' => $items,
        ]);
    }

    /**
     * Danh sách sản phẩm (có lọc và phân trang).
     */
    public function sanPham(Request $request): JsonResponse
    {
        TaiKhoanGame::giaiPhongGiuChoHetHan();

        $query = SanPham::query()
            ->where('trang_thai', 'ACTIVE')
            ->with([
                'bienThes' => function ($q) {
                    $q->where('trang_thai', 'ACTIVE')->orderBy('thu_tu_hien_thi');
                },
                'danhMuc:id,ten_danh_muc,duong_dan_tinh',
            ])
            ->select([
                'id',
                'ma_san_pham',
                'loai_san_pham',
                'danh_muc_id',
                'ten_san_pham',
                'duong_dan_tinh',
                'mo_ta_ngan',
                'anh_dai_dien',
                'gia_thap_nhat',
                'trang_thai',
                'noi_bat',
                'so_luong_da_ban',
                'luot_xem',
                'diem_danh_gia_tb',
                'so_luot_danh_gia',
                'ngay_tao',
            ]);

        $danhMucId = $request->danh_muc_id ?? $request->category_id;
        $danhMucSlug = $request->danh_muc ?? $request->category;
        $loaiSanPham = $request->loai_san_pham ?? $request->type;
        $giaTu = $request->gia_tu ?? $request->min_price;
        $giaDen = $request->gia_den ?? $request->max_price;
        $noiBat = $request->noi_bat ?? $request->is_featured;
        $tuKhoa = $request->tu_khoa ?? $request->search;
        $sapXep = $request->sap_xep ?? $request->sort;

        if ($danhMucId) {
            $query->where('danh_muc_id', (int) $danhMucId);
        } elseif ($danhMucSlug) {
            $cat = DanhMuc::where('duong_dan_tinh', $danhMucSlug)
                ->orWhere('id', $danhMucSlug)
                ->first();
            if ($cat) {
                $query->where('danh_muc_id', $cat->id);
            }
        }

        if ($loaiSanPham) {
            $loaiUpper = strtoupper((string) $loaiSanPham);
            if (in_array($loaiUpper, ['ACCOUNT', 'TAI_KHOAN', 'TAIKHOAN'])) {
                $query->whereIn('loai_san_pham', ['TAI_KHOAN', 'ACCOUNT']);
            } elseif (in_array($loaiUpper, ['SERVICE', 'DICH_VU', 'DICHVU'])) {
                $query->whereIn('loai_san_pham', ['DICH_VU', 'SERVICE']);
            } else {
                $query->where('loai_san_pham', $loaiUpper);
            }
        }

        if ($giaTu) {
            $query->where('gia_thap_nhat', '>=', $giaTu);
        }

        if ($giaDen) {
            $query->where('gia_thap_nhat', '<=', $giaDen);
        }

        if ($noiBat) {
            $query->where('noi_bat', true);
        }

        if ($tuKhoa) {
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('ten_san_pham', 'like', "%{$tuKhoa}%")
                  ->orWhere('ma_san_pham', 'like', "%{$tuKhoa}%")
                  ->orWhere('mo_ta_ngan', 'like', "%{$tuKhoa}%");
            });
        }

        match ($sapXep) {
            'gia_tang', 'price_asc' => $query->orderBy('gia_thap_nhat', 'asc'),
            'gia_giam', 'price_desc' => $query->orderBy('gia_thap_nhat', 'desc'),
            'da_ban', 'sold' => $query->orderBy('so_luong_da_ban', 'desc'),
            'danh_gia', 'rating' => $query->orderBy('diem_danh_gia_tb', 'desc'),
            default => $query->orderBy('ngay_tao', 'desc'),
        };

        $gioiHan = $request->gioi_han ?? $request->limit ?? 24;
        $results = $query->paginate($gioiHan);

        $danhSach = collect($results->items())->map(function ($item) {
            $arr = $item->toArray();
            $arr['name'] = $item->ten_san_pham;
            $arr['slug'] = $item->duong_dan_tinh;
            $arr['sku'] = $item->ma_san_pham;
            $arr['short_description'] = $item->mo_ta_ngan;
            $arr['thumbnail_url'] = $item->anh_dai_dien;
            $arr['min_price'] = $item->gia_thap_nhat;
            $arr['type'] = in_array($item->loai_san_pham, ['TAI_KHOAN', 'ACCOUNT']) ? 'ACCOUNT' : 'SERVICE';
            $arr['loai_san_pham'] = $item->loai_san_pham;
            $arr['rating_avg'] = $item->diem_danh_gia_tb ?? 5.0;
            $arr['rating_count'] = $item->so_luot_danh_gia ?? 0;
            $arr['category'] = $item->danhMuc ? [
                'id' => $item->danhMuc->id,
                'name' => $item->danhMuc->ten_danh_muc,
                'slug' => $item->danhMuc->duong_dan_tinh,
            ] : null;
            $arr['variants'] = collect($item->bienThes)->map(fn($v) => [
                'id' => $v->id,
                'name' => $v->ten_bien_the,
                'price' => $v->gia_goc,
                'sale_price' => $v->gia_khuyen_mai,
                'stock_quantity' => $v->so_luong_ton_kho,
                'status' => $v->trang_thai,
                ...$v->toArray(),
            ])->toArray();
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $danhSach,
            'data' => $danhSach,
            'phan_trang' => [
                'trang_hien_tai' => $results->currentPage(),
                'so_luong_moi_trang' => $results->perPage(),
                'tong_so' => $results->total(),
                'trang_cuoi' => $results->lastPage(),
            ],
            'meta' => [
                'page' => $results->currentPage(),
                'limit' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * Chi tiết sản phẩm theo slug.
     */
    public function chiTiet(string $slug): JsonResponse
    {
        TaiKhoanGame::giaiPhongGiuChoHetHan();

        $sanPham = SanPham::where('duong_dan_tinh', $slug)
            ->where('trang_thai', 'ACTIVE')
            ->with([
                'danhMuc:id,ten_danh_muc,duong_dan_tinh',
                'bienThes' => fn($q) => $q->where('trang_thai', 'ACTIVE')->orderBy('thu_tu_hien_thi'),
                'chinhSachBaoHanh',
            ])
            ->firstOrFail();

        $sanPham->increment('luot_xem');

        $soLuongKhaDung = 0;
        if (in_array($sanPham->loai_san_pham, ['ACCOUNT', 'TAI_KHOAN'])) {
            $soLuongKhaDung = $sanPham->taiKhoanGames()->where('trang_thai', 'AVAILABLE')->count();
        }

        $data = $sanPham->toArray();
        $data['so_luong_kha_dung'] = $soLuongKhaDung;
        $data['available_count'] = $soLuongKhaDung;

        // Aliases tương thích frontend
        $data['name'] = $sanPham->ten_san_pham;
        $data['sku'] = $sanPham->ma_san_pham;
        $data['slug'] = $sanPham->duong_dan_tinh;
        $data['thumbnail_url'] = $sanPham->anh_dai_dien;
        $data['min_price'] = $sanPham->gia_thap_nhat;
        $data['description'] = $sanPham->noi_dung_chi_tiet;
        $data['short_description'] = $sanPham->mo_ta_ngan;
        $data['type'] = in_array($sanPham->loai_san_pham, ['TAI_KHOAN', 'ACCOUNT']) ? 'ACCOUNT' : 'SERVICE';
        $data['loai_san_pham'] = $sanPham->loai_san_pham;
        $data['category'] = $sanPham->danhMuc ? [
            'id' => $sanPham->danhMuc->id,
            'name' => $sanPham->danhMuc->ten_danh_muc,
            'slug' => $sanPham->danhMuc->duong_dan_tinh,
        ] : null;
        $data['variants'] = collect($sanPham->bienThes)->map(fn($v) => [
            'id' => $v->id,
            'name' => $v->ten_bien_the,
            'price' => $v->gia_goc,
            'sale_price' => $v->gia_khuyen_mai,
            'stock_quantity' => $v->so_luong_ton_kho,
            'status' => $v->trang_thai,
            ...$v->toArray(),
        ])->toArray();
        $data['images'] = [];
        $data['warrantyPolicy'] = $sanPham->chinhSachBaoHanh;

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    /**
     * Danh sách tài khoản game hiển thị lẻ cho khách chọn mua.
     */
    public function danhSachTaiKhoan(Request $request, string $slug): JsonResponse
    {
        TaiKhoanGame::giaiPhongGiuChoHetHan();

        $sanPham = SanPham::where('duong_dan_tinh', $slug)
            ->whereIn('loai_san_pham', ['ACCOUNT', 'TAI_KHOAN'])
            ->where('trang_thai', 'ACTIVE')
            ->firstOrFail();

        $accounts = TaiKhoanGame::query()
            ->with(['bienThe:id,gia_goc,gia_khuyen_mai,trang_thai'])
            ->where('san_pham_id', $sanPham->id)
            ->where('trang_thai', 'AVAILABLE')
            ->whereHas('bienThe', fn ($query) => $query->where('trang_thai', 'ACTIVE'))
            ->orderBy('id')
            ->paginate(min(max((int) ($request->so_luong_moi_trang ?? $request->get('per_page', 24)), 1), 100));

        $accounts->getCollection()->transform(fn (TaiKhoanGame $account) => [
            'id' => $account->id,
            'san_pham_id' => $account->san_pham_id,
            'product_id' => $account->san_pham_id,
            'bien_the_id' => $account->bien_the_id,
            'variant_id' => $account->bien_the_id,
            'anh_minh_hoa' => $account->anh_minh_hoa ?: $sanPham->anh_dai_dien,
            'image_url' => $account->anh_minh_hoa ?: $sanPham->anh_dai_dien,
            'thong_tin_cong_khai' => $account->thong_tin_cong_khai ?? [],
            'public_details' => $account->thong_tin_cong_khai ?? [],
            'gia' => $account->bienThe?->gia_khuyen_mai ?? $account->bienThe?->gia_goc ?? $sanPham->gia_thap_nhat,
            'price' => $account->bienThe?->gia_khuyen_mai ?? $account->bienThe?->gia_goc ?? $sanPham->gia_thap_nhat,
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => [
                'san_pham' => [
                    'id' => $sanPham->id,
                    'ten_san_pham' => $sanPham->ten_san_pham,
                    'name' => $sanPham->ten_san_pham,
                    'duong_dan_tinh' => $sanPham->duong_dan_tinh,
                    'slug' => $sanPham->duong_dan_tinh
                ],
                'tai_khoan' => $accounts,
                'accounts' => $accounts,
            ],
            'data' => [
                'product' => [
                    'id' => $sanPham->id,
                    'name' => $sanPham->ten_san_pham,
                    'slug' => $sanPham->duong_dan_tinh
                ],
                'accounts' => $accounts,
            ],
        ]);
    }

    /**
     * Banners quảng cáo công khai.
     */
    public function banners(): JsonResponse
    {
        $banners = BannerQuangCao::where('trang_thai', 'ACTIVE')
            ->orderBy('thu_tu_hien_thi')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'tieu_de' => $b->tieu_de,
                'title' => $b->tieu_de,
                'duong_dan_anh' => $b->duong_dan_anh,
                'image_url' => $b->duong_dan_anh,
                'duong_dan_lien_ket' => $b->duong_dan_lien_ket,
                'link_url' => $b->duong_dan_lien_ket,
                'thu_tu_hien_thi' => $b->thu_tu_hien_thi,
                'trang_thai' => $b->trang_thai,
            ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $banners,
            'data' => $banners,
        ]);
    }

    /**
     * Cài đặt công khai hệ thống.
     */
    public function caiDatCongKhai(): JsonResponse
    {
        $settings = CaiDat::all();
        $formatted = [];
        foreach ($settings as $setting) {
            $val = $setting->gia_tri_cai_dat;
            while (is_string($val) && (str_starts_with($val, '{') || str_starts_with($val, '[') || str_starts_with($val, '"'))) {
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $decoded;
                } else {
                    break;
                }
            }
            $key = $setting->khoa_cai_dat;
            $formatted[$key] = $val;
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $formatted,
            'data' => $formatted,
        ]);
    }
}

