<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\YeuCauBaoHanh;
use App\Models\ChinhSachBaoHanh;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminWarrantyController extends Controller
{
    // ==================== WARRANTY POLICIES CRUD ====================
    public function index(): JsonResponse
    {
        $policies = ChinhSachBaoHanh::orderByDesc('id')->get();
        return response()->json(['success' => true, 'data' => $policies]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'product_type' => 'required|in:ACCOUNT,SERVICE',
            'duration_days' => 'nullable|integer|min:1',
            'is_lifetime' => 'boolean',
            'coverage' => 'nullable|array',
            'exclusions' => 'nullable|string',
            'claim_sla_hours' => 'integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:ACTIVE,DISABLED',
        ]);

        $code = 'WAR-' . strtoupper(Str::random(6));
        $policy = ChinhSachBaoHanh::create([
            'ma_chinh_sach' => $code,
            'ten_chinh_sach' => $validated['name'],
            'loai_san_pham' => $validated['product_type'],
            'so_ngay_bao_hanh' => $validated['duration_days'] ?? null,
            'bao_hanh_tron_doi' => $validated['is_lifetime'] ?? false,
            'pham_vi_bao_hanh' => $validated['coverage'] ?? null,
            'dieu_khoan_loai_tru' => $validated['exclusions'] ?? null,
            'thoi_gian_xu_ly_gio' => $validated['claim_sla_hours'] ?? 24,
            'mo_ta' => $validated['description'] ?? null,
            'trang_thai' => $validated['status'] ?? 'ACTIVE',
            'nguoi_tao_id' => $request->user()?->id,
        ]);

        return response()->json(['success' => true, 'data' => $policy], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $policy = ChinhSachBaoHanh::findOrFail($id);
        $validated = $request->validate([
            'name' => 'string|max:150',
            'product_type' => 'in:ACCOUNT,SERVICE',
            'duration_days' => 'nullable|integer',
            'is_lifetime' => 'boolean',
            'coverage' => 'nullable|array',
            'exclusions' => 'nullable|string',
            'claim_sla_hours' => 'integer|min:1',
            'description' => 'nullable|string',
            'status' => 'in:ACTIVE,DISABLED',
        ]);

        $updateData = [];
        if (isset($validated['name'])) $updateData['ten_chinh_sach'] = $validated['name'];
        if (isset($validated['product_type'])) $updateData['loai_san_pham'] = $validated['product_type'];
        if (array_key_exists('duration_days', $validated)) $updateData['so_ngay_bao_hanh'] = $validated['duration_days'];
        if (isset($validated['is_lifetime'])) $updateData['bao_hanh_tron_doi'] = $validated['is_lifetime'];
        if (array_key_exists('coverage', $validated)) $updateData['pham_vi_bao_hanh'] = $validated['coverage'];
        if (array_key_exists('exclusions', $validated)) $updateData['dieu_khoan_loai_tru'] = $validated['exclusions'];
        if (isset($validated['claim_sla_hours'])) $updateData['thoi_gian_xu_ly_gio'] = $validated['claim_sla_hours'];
        if (array_key_exists('description', $validated)) $updateData['mo_ta'] = $validated['description'];
        if (isset($validated['status'])) $updateData['trang_thai'] = $validated['status'];

        $policy->update($updateData);
        return response()->json(['success' => true, 'data' => $policy]);
    }

    public function destroy(int $id): JsonResponse
    {
        $policy = ChinhSachBaoHanh::findOrFail($id);
        $policy->delete();
        return response()->json(['success' => true, 'message' => 'Đã xoá chính sách bảo hành.']);
    }

    // ==================== WARRANTY CLAIMS MANAGEMENT ====================
    public function claims(Request $request): JsonResponse
    {
        $query = YeuCauBaoHanh::with([
            'user',
            'orderItem.order',
            'orderItem.product',
            'policy',
            'assignedStaff',
            'reviewedBy'
        ])->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('trang_thai', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ma_yeu_cau_bao_hanh', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhere('ho_ten', 'like', "%{$search}%");
                  });
            });
        }

        $claims = $query->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $claims]);
    }

    public function showClaim(string $claimId): JsonResponse
    {
        $claim = YeuCauBaoHanh::where('id', $claimId)
            ->orWhere('ma_yeu_cau_bao_hanh', $claimId)
            ->with([
                'user',
                'orderItem.order',
                'orderItem.product',
                'orderItem.gameAccount',
                'policy',
                'assignedStaff',
                'reviewedBy'
            ])
            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $claim]);
    }

    public function decide(Request $request, string $claimId): JsonResponse
    {
        $claim = YeuCauBaoHanh::where('id', $claimId)
            ->orWhere('ma_yeu_cau_bao_hanh', $claimId)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:SUBMITTED,IN_REVIEW,NEED_INFO,APPROVED,REJECTED,RESOLVED,CANCELLED',
            'resolution_type' => 'nullable|string',
            'resolution_note' => 'nullable|string|max:1000',
            'reject_reason' => 'nullable|string|max:1000',
            'assigned_to' => 'nullable|exists:quan_tri_vien,id',
        ]);

        $updateData = [
            'trang_thai' => $validated['status'],
            'hinh_thuc_giai_quyet' => $validated['resolution_type'] ?? $claim->hinh_thuc_giai_quyet,
            'ghi_chu_giai_quyet' => $validated['resolution_note'] ?? $claim->ghi_chu_giai_quyet,
            'ly_do_tu_choi' => $validated['reject_reason'] ?? $claim->ly_do_tu_choi,
            'nguoi_duyet_id' => $request->user()?->id,
        ];

        if (isset($validated['assigned_to'])) {
            $updateData['nhan_vien_phu_trach_id'] = $validated['assigned_to'];
        }

        if (in_array($validated['status'], ['APPROVED', 'RESOLVED', 'REJECTED', 'CANCELLED'], true)) {
            $updateData['ngay_giai_quyet'] = now();
        }

        $claim->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật xử lý khiếu nại bảo hành thành công!',
            'data' => $claim->fresh(['user', 'orderItem.order', 'orderItem.product', 'policy', 'assignedStaff', 'reviewedBy']),
        ]);
    }
}
