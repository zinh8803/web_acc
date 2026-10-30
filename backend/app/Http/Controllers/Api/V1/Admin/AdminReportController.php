<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
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

        // Daily chart data for the last 7 days from DB
        $daily = Order::whereIn('status', $paidStatuses)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, sum(total) as total, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue by Game from DB
        $gameRevenue = DB::table('games')
            ->leftJoin('products', 'games.id', '=', 'products.game_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) use ($paidStatuses) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereIn('orders.status', $paidStatuses);
            })
            ->select('games.id', 'games.name', DB::raw('COALESCE(SUM(order_items.line_total), 0) as total_amount'))
            ->groupBy('games.id', 'games.name')
            ->orderByDesc('total_amount')
            ->get();

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
                'daily_trend' => $daily,
                'game_revenue' => $gameRevenue,
            ]
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $topProducts = Product::with('game:id,name', 'category:id,name')
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
        $approvedClaims = \App\Models\WarrantyClaim::whereIn('status', ['APPROVED', 'RESOLVED'])->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_claims' => $totalClaims,
                'pending_claims' => $pendingClaims,
                'approved_claims' => $approvedClaims,
            ]
        ]);
    }
}
