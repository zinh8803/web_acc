<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminReportController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        return $this->revenue($request);
    }

    public function revenue(Request $request): JsonResponse
    {
        $paidStatuses = ['PAID', 'COMPLETED', 'paid', 'completed'];

        $todayRevenue = Order::whereIn('status', $paidStatuses)
            ->whereDate('created_at', today())
            ->sum('total');

        $totalRevenue = Order::whereIn('status', $paidStatuses)
            ->sum('total');

        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('status', ['PENDING_PAYMENT', 'pending_payment'])->count();
        $completedOrders = Order::whereIn('status', $paidStatuses)->count();
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $availableAccounts = GameAccount::whereIn('status', ['AVAILABLE', 'available'])->count();

        // Recent orders from DB
        $recentOrders = Order::with('user:id,full_name,email')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Revenue by Category from DB (exclude soft-deleted categories)
        $categoryRevenue = DB::table('categories')
            ->whereNull('categories.deleted_at')
            ->leftJoin('products', function ($join) {
                $join->on('categories.id', '=', 'products.category_id')
                    ->whereNull('products.deleted_at');
            })
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) use ($paidStatuses) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereIn('orders.status', $paidStatuses);
            })
            ->select('categories.id', 'categories.name', DB::raw('COALESCE(SUM(order_items.line_total), 0) as total_amount'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('total_amount')
            ->get();

        // Available years for dropdown (include DB years and a generous range around current year)
        $dbYears = Order::selectRaw('YEAR(created_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->map(fn($y) => (int)$y)
            ->all();

        $currentYear = (int) now()->year;
        $rangeYears = range($currentYear - 6, $currentYear + 3); // e.g. 2020 .. 2029
        $availableYears = array_values(array_unique(array_merge($dbYears, $rangeYears)));
        rsort($availableYears);

        // ==================== REVENUE CHART BY PERIOD ====================
        $chartType = $request->input('period', 'year'); // 'year', 'month', 'week'
        $selectedYear = (int) $request->input('year', $currentYear);
        $selectedMonth = (int) $request->input('month', now()->month);
        $selectedDate = $request->input('date', now()->format('Y-m-d'));

        $chartLabels = [];
        $chartData = [];
        $periodTitle = '';

        if ($chartType === 'month') {
            // Theo ngày trong tháng (Ngày 1 -> Ngày 30/31)
            $dateObj = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
            $daysInMonth = $dateObj->daysInMonth;
            $periodTitle = "Tháng {$selectedMonth}/{$selectedYear}";

            $dailyOrders = Order::whereIn('status', $paidStatuses)
                ->whereYear('created_at', $selectedYear)
                ->whereMonth('created_at', $selectedMonth)
                ->selectRaw('DAY(created_at) as day, sum(total) as total')
                ->groupBy('day')
                ->pluck('total', 'day')
                ->all();

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $chartLabels[] = "Ngày " . str_pad($d, 2, '0', STR_PAD_LEFT);
                $chartData[] = (float) ($dailyOrders[$d] ?? 0);
            }
        } elseif ($chartType === 'week') {
            // Theo các ngày trong tuần (Thứ 2 -> Chủ Nhật)
            try {
                $baseDate = Carbon::parse($selectedDate);
            } catch (\Exception $e) {
                $baseDate = now();
            }
            $startDate = $baseDate->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $startDate->copy()->endOfWeek(Carbon::SUNDAY);
            $periodTitle = "Tuần từ " . $startDate->format('d/m/Y') . " đến " . $endDate->format('d/m/Y');

            $weekOrders = Order::whereIn('status', $paidStatuses)
                ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
                ->selectRaw('DATE(created_at) as date, sum(total) as total')
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
            // Mặc định: Theo năm (Tháng 1 -> Tháng 12)
            $chartType = 'year';
            $periodTitle = "Năm {$selectedYear}";

            $monthlyOrders = Order::whereIn('status', $paidStatuses)
                ->whereYear('created_at', $selectedYear)
                ->selectRaw('MONTH(created_at) as month, sum(total) as total')
                ->groupBy('month')
                ->pluck('total', 'month')
                ->all();

            for ($m = 1; $m <= 12; $m++) {
                $chartLabels[] = "Tháng {$m}";
                $chartData[] = (float) ($monthlyOrders[$m] ?? 0);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
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
        $topProducts = Product::with('category:id,name')
            ->orderByDesc('sold_count')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $topProducts,
        ]);
    }

    public function warranty(Request $request): JsonResponse
    {
        $totalClaims = \App\Models\WarrantyClaim::count();
        $pendingClaims = \App\Models\WarrantyClaim::whereIn('status', ['SUBMITTED', 'IN_REVIEW', 'NEED_INFO'])->count();
        $approvedClaims = \App\Models\WarrantyClaim::where('status', 'APPROVED')->count();
        $rejectedClaims = \App\Models\WarrantyClaim::where('status', 'REJECTED')->count();
        $resolvedClaims = \App\Models\WarrantyClaim::where('status', 'RESOLVED')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_claims' => $totalClaims,
                'pending_claims' => $pendingClaims,
                'approved_claims' => $approvedClaims,
                'rejected_claims' => $rejectedClaims,
                'resolved_claims' => $resolvedClaims,
            ]
        ]);
    }
}
