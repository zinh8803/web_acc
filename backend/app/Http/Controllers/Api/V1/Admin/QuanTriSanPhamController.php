<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SanPham;
use App\Models\BienTheSanPham;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class QuanTriSanPhamController extends Controller
{
    /**
     * Danh sách sản phẩm quản trị.
     */
    public function index(Request $request): JsonResponse
    {
        $query = SanPham::with(['danhMuc', 'chinhSachBaoHanh'])
            ->withCount([
                'taiKhoanGames as available_accounts_count' => fn ($q) => $q->where('trang_thai', 'AVAILABLE'),
                'taiKhoanGames as reserved_accounts_count' => fn ($q) => $q->where('trang_thai', 'RESERVED'),
                'taiKhoanGames as sold_accounts_count' => fn ($q) => $q->where('trang_thai', 'SOLD'),
            ])
            ->orderByDesc('id');

        $search = $request->get('tu_khoa') ?? $request->get('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ten_san_pham', 'like', "%{$search}%")
                  ->orWhere('ma_san_pham', 'like', "%{$search}%");
            });
        }

        $status = $request->get('trang_thai') ?? $request->get('status');
        if ($status) {
            $query->where('trang_thai', $status);
        }

        $paginator = $query->paginate(min(max((int) $request->get('per_page', 50), 1), 100));

        $paginator->getCollection()->transform(function ($product) {
            $arr = $product->toArray();
            $arr['name'] = $product->ten_san_pham;
            $arr['sku'] = $product->ma_san_pham;
            $arr['slug'] = $product->duong_dan_tinh;
            $arr['thumbnail_url'] = $product->anh_dai_dien;
            $arr['min_price'] = $product->gia_thap_nhat;
            $arr['type'] = in_array($product->loai_san_pham, ['TAI_KHOAN', 'ACCOUNT']) ? 'ACCOUNT' : 'SERVICE';
            $arr['loai_san_pham'] = $product->loai_san_pham;
            $arr['status'] = $product->trang_thai;
            $arr['rating_avg'] = $product->diem_danh_gia_tb;
            $arr['rating_count'] = $product->so_luot_danh_gia;
            $arr['sold_count'] = $product->so_luong_da_ban;
            $arr['category'] = $product->danhMuc ? [
                'id' => $product->danhMuc->id,
                'name' => $product->danhMuc->ten_danh_muc,
                'slug' => $product->danhMuc->duong_dan_tinh,
            ] : null;
            $arr['warranty_policy'] = $product->chinhSachBaoHanh ? [
                'id' => $product->chinhSachBaoHanh->id,
                'name' => $product->chinhSachBaoHanh->ten_chinh_sach,
            ] : null;
            return $arr;
        });

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $paginator,
            'data' => $paginator,
        ]);
    }

    /**
     * Chi tiết sản phẩm.
     */
    public function show(int $id): JsonResponse
    {
        $product = SanPham::with(['danhMuc', 'bienThes', 'chinhSachBaoHanh'])
            ->findOrFail($id);

        $data = $product->toArray();
        $data['name'] = $product->ten_san_pham;
        $data['sku'] = $product->ma_san_pham;
        $data['slug'] = $product->duong_dan_tinh;
        $data['thumbnail_url'] = $product->anh_dai_dien;
        $data['min_price'] = $product->gia_thap_nhat;
        $data['type'] = in_array($product->loai_san_pham, ['TAI_KHOAN', 'ACCOUNT']) ? 'ACCOUNT' : 'SERVICE';
        $data['loai_san_pham'] = $product->loai_san_pham;
        $data['description'] = $product->noi_dung_chi_tiet;
        $data['short_description'] = $product->mo_ta_ngan;
        $data['category'] = $product->danhMuc;
        $data['variants'] = $product->bienThes;
        $data['warranty_policy'] = $product->chinhSachBaoHanh;
        $data['warranty_policy_id'] = $product->chinh_sach_bao_hanh_id;
        $data['images'] = [];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $data,
            'data' => $data,
        ]);
    }

    /**
     * Tạo sản phẩm mới.
     */
    public function store(Request $request): JsonResponse
    {
        $name = $request->ten_san_pham ?? $request->name ?? 'Sản phẩm mới';
        $catId = $request->danh_muc_id ?? $request->category_id;
        $type = strtoupper((string) ($request->loai_san_pham ?? $request->type ?? 'TAI_KHOAN'));
        if ($type === 'ACCOUNT') $type = 'TAI_KHOAN';
        if ($type === 'SERVICE') $type = 'DICH_VU';

        $price = (float) ($request->gia_goc ?? $request->price ?? 0);
        $salePrice = $request->gia_khuyen_mai ?? $request->sale_price ?? null;
        if ($salePrice !== null) $salePrice = (float) $salePrice;

        $thumbnailUrl = null;
        if ($request->hasFile('thumbnail_file') || $request->hasFile('anh_dai_dien_file')) {
            $file = $request->file('thumbnail_file') ?? $request->file('anh_dai_dien_file');
            $thumbnailUrl = Storage::disk('public')->url($file->store('products', 'public'));
        }

        $warrantyPolicyId = $request->chinh_sach_bao_hanh_id ?? $request->warranty_policy_id ?? null;

        $product = SanPham::create([
            'ma_san_pham' => 'PROD-' . strtoupper(Str::random(6)),
            'duong_dan_tinh' => Str::slug($name) . '-' . strtolower(Str::random(4)),
            'ten_san_pham' => $name,
            'danh_muc_id' => $catId,
            'loai_san_pham' => $type,
            'chinh_sach_bao_hanh_id' => $warrantyPolicyId,
            'gia_thap_nhat' => $salePrice ?? $price,
            'anh_dai_dien' => $thumbnailUrl,
            'mo_ta_ngan' => $request->mo_ta_ngan ?? $request->short_description ?? null,
            'noi_dung_chi_tiet' => $request->noi_dung_chi_tiet ?? $request->description ?? null,
            'trang_thai' => $request->trang_thai ?? $request->status ?? 'ACTIVE',
            'noi_bat' => (bool) ($request->noi_bat ?? $request->is_featured ?? false),
        ]);

        $product->bienThes()->create([
            'ma_bien_the' => $product->ma_san_pham . '-DEFAULT',
            'ten_bien_the' => 'Mặc định',
            'gia_goc' => $price,
            'gia_khuyen_mai' => $salePrice,
            'so_luong_ton_kho' => 0,
            'trang_thai' => 'ACTIVE',
        ]);

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Tạo sản phẩm thành công.',
            'message' => 'Tạo sản phẩm thành công.',
            'du_lieu' => $product,
            'data' => $product,
        ], 201);
    }

    /**
     * Cập nhật sản phẩm.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = SanPham::findOrFail($id);
        $updateData = [];

        $name = $request->ten_san_pham ?? $request->name;
        if ($name) {
            $updateData['ten_san_pham'] = $name;
        }

        $catId = $request->danh_muc_id ?? $request->category_id;
        if ($catId) {
            $updateData['danh_muc_id'] = $catId;
        }

        if ($request->has('loai_san_pham') || $request->has('type')) {
            $type = strtoupper((string) ($request->loai_san_pham ?? $request->type));
            if ($type === 'ACCOUNT') $type = 'TAI_KHOAN';
            if ($type === 'SERVICE') $type = 'DICH_VU';
            $updateData['loai_san_pham'] = $type;
        }

        if ($request->has('chinh_sach_bao_hanh_id') || $request->has('warranty_policy_id')) {
            $updateData['chinh_sach_bao_hanh_id'] = $request->chinh_sach_bao_hanh_id ?? $request->warranty_policy_id;
        }

        if ($request->has('mo_ta_ngan') || $request->has('short_description')) {
            $updateData['mo_ta_ngan'] = $request->mo_ta_ngan ?? $request->short_description;
        }

        if ($request->has('noi_dung_chi_tiet') || $request->has('description')) {
            $updateData['noi_dung_chi_tiet'] = $request->noi_dung_chi_tiet ?? $request->description;
        }

        if ($request->has('trang_thai') || $request->has('status')) {
            $updateData['trang_thai'] = $request->trang_thai ?? $request->status;
        }

        if ($request->has('noi_bat') || $request->has('is_featured')) {
            $updateData['noi_bat'] = (bool) ($request->noi_bat ?? $request->is_featured);
        }

        if ($request->hasFile('thumbnail_file') || $request->hasFile('anh_dai_dien_file')) {
            $file = $request->file('thumbnail_file') ?? $request->file('anh_dai_dien_file');
            $updateData['anh_dai_dien'] = Storage::disk('public')->url($file->store('products', 'public'));
        }

        $price = $request->gia_goc ?? $request->price ?? $product->bienThes()->orderBy('thu_tu_hien_thi')->value('gia_goc');
        $salePrice = $request->gia_khuyen_mai ?? $request->sale_price ?? $product->bienThes()->orderBy('thu_tu_hien_thi')->value('gia_khuyen_mai');

        if ($request->has('gia_goc') || $request->has('price') || $request->has('gia_khuyen_mai') || $request->has('sale_price')) {
            $updateData['gia_thap_nhat'] = $salePrice ?: $price;
        }

        $product->update($updateData);

        if ($request->has('gia_goc') || $request->has('price')) {
            $variant = $product->bienThes()->orderBy('thu_tu_hien_thi')->first();
            if ($variant) {
                $variant->update(['gia_goc' => $price, 'gia_khuyen_mai' => $salePrice]);
            }
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Cập nhật sản phẩm thành công.',
            'message' => 'Cập nhật sản phẩm thành công.',
            'du_lieu' => $product,
            'data' => $product,
        ]);
    }

    /**
     * Xóa sản phẩm.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = SanPham::findOrFail($id);

        if ($product->taiKhoanGames()->exists()) {
            $product->update(['trang_thai' => 'ARCHIVED']);
            return response()->json([
                'thanh_cong' => true,
                'success' => true,
                'thong_bao' => 'Sản phẩm đã có tài khoản trong kho, đã chuyển sang trạng thái Lưu trữ.',
                'message' => 'Sản phẩm đã có tài khoản trong kho, đã chuyển trạng thái sang Lưu trữ/Ẩn.',
            ]);
        }

        $product->delete();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'thong_bao' => 'Đã xóa sản phẩm thành công.',
            'message' => 'Đã xoá sản phẩm thành công.',
        ]);
    }
}
