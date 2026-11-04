<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\TaiKhoanGame;
use App\Models\DonHang;
use App\Models\SanPham;
use App\Models\NguoiDung;
use App\Models\YeuCauBaoHanh;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuanTriBaoCaoController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        return $this->revenue($request);
    }

    public function revenue(Request $request): JsonResponse
    {
        $paidStatuses = ['PAID', 'COMPLETED', 'paid', 'completed'];

        $todayRevenue = DonHang::whereIn('trang_thai', $paidStatuses)
            ->whereDate('ngay_tao', today())
            ->sum('tong_tien');

        $totalRevenue = DonHang::whereIn('trang_thai', $paidStatuses)
            ->sum('tong_tien');

        $totalOrders = DonHang::count();
        $pendingOrders = DonHang::whereIn('trang_thai', ['PENDING_PAYMENT', 'pending_payment'])->count();
        $completedOrders = DonHang::whereIn('trang_thai', $paidStatuses)->count();
        $totalUsers = NguoiDung::count();
        $totalProducts = SanPham::count();
        $availableAccounts = TaiKhoanGame::whereIn('trang_thai', ['AVAILABLE', 'available'])->count();

        $recentOrders = DonHang::with('nguoiDung:id,ho_ten,email')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                $arr = $order->toArray();
                $arr['order_code'] = $order->ma_don_hang;
                $arr['total'] = $order->tong_tien;
                $arr['status'] = $order->trang_thai;
                $arr['created_at'] = $order->ngay_tao;
                $arr['user'] = $order->nguoiDung;
                return $arr;
            });

        $categoryRevenue = DB::table('danh_muc')
            ->leftJoin('san_pham', 'danh_muc.id', '=', 'san_pham.danh_muc_id')
            ->leftJoin('chi_tiet_don_hang', 'san_pham.id', '=', 'chi_tiet_don_hang.san_pham_id')
            ->leftJoin('don_hang', function ($join) use ($paidStatuses) {
                $join->on('chi_tiet_don_hang.don_hang_id', '=', 'don_hang.id')
                    ->whereIn('don_hang.trang_thai', $paidStatuses);
            })
            ->select('danh_muc.id', 'danh_muc.ten_danh_muc as name', DB::raw('COALESCE(SUM(chi_tiet_don_hang.thanh_tien), 0) as total_amount'))
            ->groupBy('danh_muc.id', 'danh_muc.ten_danh_muc')
            ->orderByDesc('total_amount')
            ->get();

        $dbYears = DonHang::selectRaw('YEAR(ngay_tao) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->map(fn($y) => (int)$y)
            ->all();

        $currentYear = (int) now()->year;
        $rangeYears = range($currentYear - 6, $currentYear + 3);
        $availableYears = array_values(array_unique(array_merge($dbYears, $rangeYears)));
        rsort($availableYears);

        $chartType = $request->input('period', 'year');
        $selectedYear = (int) $request->input('year', $currentYear);
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedDate = $request->input('date', now()->format('Y-m-d'));

        $chartLabels = [];
        $chartData = [];
        $periodTitle = '';

        if ($chartType === 'month') {
            $dateObj = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
            $daysInMonth = $dateObj->daysInMonth;
            $periodTitle = "Tháng {$selectedMonth}/{$selectedYear}";

            $dailyOrders = DonHang::whereIn('trang_thai', $paidStatuses)
                ->whereYear('ngay_tao', $selectedYear)
                ->whereMonth('ngay_tao', $selectedMonth)
                ->selectRaw('DAY(ngay_tao) as day, sum(tong_tien) as total')
                ->groupBy('day')
                ->pluck('total', 'day')
                ->all();

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $chartLabels[] = "Ngày " . str_pad($d, 2, '0', STR_PAD_LEFT);
                $chartData[] = (float) ($dailyOrders[$d] ?? 0);
            }
        } elseif ($chartType === 'week') {
            try {
                $baseDate = Carbon::parse($selectedDate);
            } catch (\Exception $e) {
                $baseDate = now();
            }
            $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $startDate->copy()->endOfWeek(Carbon::SUNDAY);
            $periodTitle = "Tuần từ " . $startDate->format('d/m/Y') . " đến " . $endDate->format('d/m/Y');

            $weekOrders = DonHang::whereIn('trang_thai', $paidStatuses)
                ->whereBetween('ngay_tao', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->selectRaw('DATE(ngay_tao) as date, sum(tong_tien) as total')
                ->groupBy('date')
                ->pluck('total', 'date')
                ->all();

            $dayNames = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ Nhật'];
            for ($i = 0; $i < 7; $i++) {
                $currentDay = $startDate->copy()->addDays($i);
                $dateKey = $currentDay->format('Y-m-d');
                $chartLabels[] = $dayNames[$i] . ' (' . $currentDay->format('d/m') . ')';
                $chartData[] = (float) ($weekOrders[$dateKey] ?? 0);
            }
        } else {
            $chartType = 'year';
            $periodTitle = "Năm {$selectedYear}";

            $monthlyOrders = DonHang::whereIn('trang_thai', $paidStatuses)
                ->whereYear('ngay_tao', $selectedYear)
                ->selectRaw('MONTH(ngay_tao) as month, sum(tong_tien) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            for ($m = 1; $m <= 12; $m++) {
                $chartLabels[] = "Tháng {$m}";
                $chartData[] = (float) ($monthlyOrders[$m] ?? 0);
            }
        }

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => [
                'revenue' => (float) $totalRevenue,
                'orders_count' => $totalOrders,
                'today_revenue' => (float) $todayRevenue,
                'total_revenue' => (float) $totalRevenue,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'available_accounts' => $availableAccounts,
                'recent_orders' => $recentOrders,
                'game_revenue' => $categoryRevenue,
                'available_years' => $availableYears,
                'revenue_chart' => [
                    'period' => $chartType,
                    'year' => $selectedYear,
                    'month' => $selectedMonth,
                    'date' => $selectedDate,
                    'period_title' => $periodTitle,
                    'labels' => $chartLabels,
                    'data' => $chartData,
                    'total' => (float) array_sum($chartData),
                ],
            ],
            'data' => [
                'revenue' => (float) $totalRevenue,
                'orders_count' => $totalOrders,
                'today_revenue' => (float) $todayRevenue,
                'total_revenue' => (float) $totalRevenue,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'completed_orders' => $completedOrders,
                'total_users' => $totalUsers,
                'total_products' => $totalProducts,
                'available_accounts' => $availableAccounts,
                'recent_orders' => $recentOrders,
                'game_revenue' => $categoryRevenue,
                'available_years' => $availableYears,
                'revenue_chart' => [
                    'period' => $chartType,
                    'year' => $selectedYear,
                    'month' => $selectedMonth,
                    'date' => $selectedDate,
                    'period_title' => $periodTitle,
                    'labels' => $chartLabels,
                    'data' => $chartData,
                    'total' => (float) array_sum($chartData),
                ],
            ]
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $topProducts = SanPham::with('danhMuc:id,ten_danh_muc')
            ->orderByDesc('so_luong_da_ban')
            ->limit(10)
            ->get();

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $topProducts,
            'data' => $topProducts,
        ]);
    }

    public function warranty(Request $request): JsonResponse
    {
        $totalClaims = YeuCauBaoHanh::count();
        $pendingClaims = YeuCauBaoHanh::whereIn('trang_thai', ['SUBMITTED', 'IN_REVIEW', 'NEED_INFO'])->count();
        $approvedClaims = YeuCauBaoHanh::where('trang_thai', 'APPROVED')->count();
        $rejectedClaims = YeuCauBaoHanh::where('trang_thai', 'REJECTED')->count();
        $resolvedClaims = YeuCauBaoHanh::where('trang_thai', 'RESOLVED')->count();

        $res = [
            'total_claims' => $totalClaims,
            'pending_claims' => $pendingClaims,
            'approved_claims' => $approvedClaims,
            'rejected_claims' => $rejectedClaims,
            'resolved_claims' => $resolvedClaims,
        ];

        return response()->json([
            'thanh_cong' => true,
            'success' => true,
            'du_lieu' => $res,
            'data' => $res
        ]);
    }
}

