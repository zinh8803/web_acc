<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoanGame;
use App\Models\SanPham;
use App\Models\BienTheSanPham;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuanTriTaiKhoanGameController extends Controller
{
    /**
     * Danh sách tài khoản game trong kho.
     */
    public function index(Request $request): JsonResponse
    {
        $sanPhamId = $request->san_pham_id ?? $request->product_id;
        $trangThai = $request->trang_thai ?? $request->status;
        $search = $request->tu_khoa ?? $request->search;
        $perPage = (int) ($request->so_luong_moi_trang ?? $request->per_page ?? 20);

        $query = TaiKhoanGame::with(['sanPham', 'bienThe'])->orderByDesc('id');

        if (!empty($sanPhamId)) {
            $query->where('san_pham_id', $sanPhamId);
        }

        if (!empty($trangThai)) {
            $query->where('trang_thai', strtoupper($trangThai));
        }

        if (!empty($search)) {
            $query->whereHas('sanPham', fn ($q) => $q->where('ten_san_pham', 'like', "%{$search}%"));
        }

        $accounts = $query->paginate($perPage);

        $accounts->getCollection()->transform(function ($account) {
            $arr = $account->toArray();
            $arr['product'] = $account->sanPham ? [
                'id' => $account->sanPham->id,
                'name' => $account->sanPham->ten_san_pham,
                'slug' => $account->sanPham->duong_dan_tinh,
            ] : null;
            $arr['variant'] = $account->bienThe;
            $arr['image_url'] = $account->anh_minh_hoa;
            $arr['public_details'] = $account->thong_tin_cong_khai ?? [];
            $arr['cost_price'] = $account->gia_nhap;
            $arr['status'] = $account->trang_thai;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $accounts,
            'data' => $accounts,
        ]);
    }

    /**
     * Xem thông tin tài khoản (kèm mật khẩu giải mã).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::with(['sanPham', 'bienThe'])->findOrFail($id);
        $thongTin = $account->giaiMaThongTin();

        $data = array_merge($account->toArray(), [
            'credentials' => [
                'username' => $thongTin['ten_dang_nhap'],
                'password' => $thongTin['mat_khau'],
                'extra' => $thongTin['thong_tin_phu'],
                'ten_dang_nhap' => $thongTin['ten_dang_nhap'],
                'mat_khau' => $thongTin['mat_khau'],
                'thong_tin_phu' => $thongTin['thong_tin_phu'],
            ],
            'thong_tin_dang_nhap' => $thongTin,
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    /**
     * Nhập tài khoản game đơn lẻ vào kho.
     */
    public function store(Request $request): JsonResponse
    {
        $sanPhamId = $request->san_pham_id ?? $request->product_id;
        $tenDangNhap = $request->ten_dang_nhap ?? $request->username;
        $matKhau = $request->mat_khau ?? $request->password;
        $thongTinPhu = $request->thong_tin_phu ?? $request->additional_info;
        $giaNhap = $request->gia_nhap ?? $request->cost_price;

        if (!$sanPhamId || !$tenDangNhap || !$matKhau) {
            return response()->json([
                'thanh_cong' => false,
                'success' => false,
                'thong_bao' => 'Vui lòng điền đầy đủ sản phẩm, tài khoản và mật khẩu.',
                'error' => ['message' => 'Thiếu thông tin bắt buộc.']
            ], 422);
        }

        $publicDetails = $request->thong_tin_cong_khai ?? $request->public_details;
        if (is_string($publicDetails)) {
            $publicDetails = json_decode($publicDetails, true) ?: [];
        }

        $product = SanPham::with('bienThes')->findOrFail($sanPhamId);
        $variant = $product->bienThes->first();
        if (!$variant) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Sản phẩm chưa có biến thể để nhập kho.'], 422);
        }

        $imageUrl = null;
        if ($request->hasFile('image_file') || $request->hasFile('anh_minh_hoa_file')) {
            $file = $request->file('image_file') ?? $request->file('anh_minh_hoa_file');
            $imageUrl = Storage::disk('public')->url($file->store('game-accounts', 'public'));
        }

        $account = DB::transaction(function () use ($product, $variant, $tenDangNhap, $matKhau, $thongTinPhu, $giaNhap, $imageUrl, $publicDetails) {
            $extra = [
                'thong_tin_phu' => $thongTinPhu,
                'gia_nhap' => $giaNhap,
                'cost_price' => $giaNhap,
            ];

            $acc = TaiKhoanGame::taoTaiKhoanMaHoa(
                sanPhamId: $product->id,
                bienTheId: $variant->id,
                tenDangNhap: $tenDangNhap,
                matKhau: $matKhau,
                thongTinPhu: $extra
            );

            $acc->update([
                'anh_minh_hoa' => $imageUrl,
                'thong_tin_cong_khai' => $publicDetails,
                'gia_nhap' => $giaNhap,
            ]);

            $variant->update([
                'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
            ]);

            return $acc;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã nhập tài khoản vào kho thành công.',
            'message' => 'Đã nhập tài khoản vào kho thành công.',
            'du_lieu' => $account,
            'data' => $account,
        ], 201);
    }

    /**
     * Cập nhật thông tin hiển thị của tài khoản game.
     */
    public function updateListing(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        if ($account->trang_thai === 'SOLD') {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Tài khoản đã bán, không thể sửa thông tin.'], 422);
        }

        $publicDetails = $request->thong_tin_cong_khai ?? $request->public_details;
        if (is_string($publicDetails)) {
            $publicDetails = json_decode($publicDetails, true) ?: [];
        }

        if ($publicDetails !== null) {
            $account->thong_tin_cong_khai = $publicDetails;
        }

        if ($request->hasFile('image_file') || $request->hasFile('anh_minh_hoa_file')) {
            $file = $request->file('image_file') ?? $request->file('anh_minh_hoa_file');
            $account->anh_minh_hoa = Storage::disk('public')->url($file->store('game-accounts', 'public'));
        }

        $account->save();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật hiển thị thành công.',
            'du_lieu' => $account,
            'data' => $account
        ]);
    }

    /**
     * Cập nhật toàn bộ thông tin tài khoản.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        if ($account->trang_thai === 'SOLD') {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Tài khoản đã bán, không thể sửa thông tin.'], 422);
        }

        $username = $request->ten_dang_nhap ?? $request->username;
        $password = $request->mat_khau ?? $request->password;
        $costPrice = $request->gia_nhap ?? $request->cost_price;

        if ($username) {
            $account->ten_dang_nhap_ma_hoa = Crypt::encryptString($username);
        }
        if ($password) {
            $account->mat_khau_ma_hoa = Crypt::encryptString($password);
        }
        if ($costPrice !== null) {
            $account->gia_nhap = $costPrice;
        }

        $publicDetails = $request->thong_tin_cong_khai ?? $request->public_details;
        if (is_string($publicDetails)) {
            $publicDetails = json_decode($publicDetails, true) ?: [];
        }
        if ($publicDetails !== null) {
            $account->thong_tin_cong_khai = $publicDetails;
        }

        if ($request->hasFile('image_file') || $request->hasFile('anh_minh_hoa_file')) {
            $file = $request->file('image_file') ?? $request->file('anh_minh_hoa_file');
            $account->anh_minh_hoa = Storage::disk('public')->url($file->store('game-accounts', 'public'));
        }

        $account->save();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã cập nhật thông tin tài khoản.',
            'message' => 'Đã cập nhật thông tin tài khoản.',
            'du_lieu' => $account,
            'data' => $account,
        ]);
    }

    /**
     * Cập nhật trạng thái kho.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        $status = strtoupper($request->trang_thai ?? $request->status ?? 'AVAILABLE');

        $account->update([
            'trang_thai' => $status,
            'ghi_chu_noi_bo' => $request->ghi_chu_noi_bo ?? $request->notes ?? $account->ghi_chu_noi_bo,
        ]);

        if ($account->bien_the_id) {
            BienTheSanPham::whereKey($account->bien_the_id)->update([
                'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $account->bien_the_id)->where('trang_thai', 'AVAILABLE')->count(),
            ]);
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã cập nhật trạng thái kho.',
            'message' => 'Đã cập nhật trạng thái kho.',
            'du_lieu' => $account,
            'data' => $account,
        ]);
    }

    public function viewCredentials(Request $request, int $id): JsonResponse
    {
        return $this->show($request, $id);
    }

    /**
     * Xác minh tài khoản.
     */
    public function verify(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        $account->update([
            'xac_minh_luc' => now(),
            'nguoi_xac_minh_id' => $request->user()?->id,
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xác minh tài khoản thành công.',
            'message' => 'Đã xác minh tài khoản thành công.',
            'du_lieu' => $account,
            'data' => $account,
        ]);
    }

    /**
     * Khóa tài khoản.
     */
    public function lock(Request $request, int $id): JsonResponse
    {
        $account = TaiKhoanGame::findOrFail($id);
        $account->update(['trang_thai' => 'LOCKED']);

        if ($account->bien_the_id) {
            BienTheSanPham::whereKey($account->bien_the_id)->update([
                'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $account->bien_the_id)->where('trang_thai', 'AVAILABLE')->count(),
            ]);
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã khóa tài khoản thành công.',
            'message' => 'Đã khóa tài khoản thành công.',
            'du_lieu' => $account,
            'data' => $account,
        ]);
    }

    /**
     * Nhập hàng loạt tài khoản.
     */
    public function import(Request $request): JsonResponse
    {
        $sanPhamId = $request->san_pham_id ?? $request->product_id;
        $danhSach = $request->tai_khoans ?? $request->accounts ?? [];

        if (!$sanPhamId || empty($danhSach)) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Dữ liệu nhập hàng không hợp lệ.'], 422);
        }

        $product = SanPham::with('bienThes')->findOrFail($sanPhamId);
        $variant = $product->bienThes->first();
        if (!$variant) {
            return response()->json(['thanh_cong' => false, 'success' => false, 'thong_bao' => 'Sản phẩm chưa có biến thể.'], 422);
        }

        $imported = 0;
        foreach ($danhSach as $acc) {
            $u = $acc['ten_dang_nhap'] ?? $acc['username'] ?? null;
            $p = $acc['mat_khau'] ?? $acc['password'] ?? null;
            if (!$u || !$p) continue;

            TaiKhoanGame::taoTaiKhoanMaHoa(
                sanPhamId: $product->id,
                bienTheId: $variant->id,
                tenDangNhap: $u,
                matKhau: $p,
                thongTinPhu: [
                    'gia_nhap' => $acc['gia_nhap'] ?? $acc['cost_price'] ?? null,
                    'thong_tin_phu' => $acc['thong_tin_phu'] ?? $acc['additional_info'] ?? null,
                ]
            );
            $imported++;
        }

        $variant->update([
            'so_luong_ton_kho' => TaiKhoanGame::where('bien_the_id', $variant->id)->where('trang_thai', 'AVAILABLE')->count(),
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => "Đã nhập thành công {$imported} tài khoản vào kho.",
            'message' => "Đã nhập thành công {$imported} tài khoản.",
        ]);
    }
}

