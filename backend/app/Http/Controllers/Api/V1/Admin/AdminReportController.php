<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\GameAccount;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReportController extends Controller
{
    public function dashboard(Request $request): JsonResponse
    {
        return $this->revenue($request);
    }

    public function revenue(Request $request): JsonResponse
    {
        $todayRevenue = Order::whereIn('status', ['paid', 'completed'])
            ->whereDate('created_at', today())
            ->sum('total_amount');

        $totalRevenue = Order::whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending_payment')->count();
        $completedOrders = Order::whereIn('status', ['paid', 'completed'])->count();
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $availableAccounts = GameAccount::where('status', 'available')->count();

        // Recent orders from DB
        $recentOrders = Order::with('user:id,full_name,email')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        // Daily chart data for the last 7 days from DB
        $daily = Order::whereIn('status', ['paid', 'completed'])
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, sum(total_amount) as total, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Revenue by Game from DB
        $gameRevenue = \Illuminate\Support\Facades\DB::table('games')
            ->leftJoin('products', 'games.id', '=', 'products.game_id')
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('orders', function ($join) {
                $join->on('order_items.order_id', '=', 'orders.id')
                    ->whereIn('orders.status', ['paid', 'completed']);
            })
            ->select('games.id', 'games.name', \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(order_items.price * order_items.quantity), 0) as total_amount'))
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
        $pendingClaims = \App\Models\WarrantyClaim::where('status', 'PENDING')->count();
        $approvedClaims = \App\Models\WarrantyClaim::where('status', 'APPROVED')->count();

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
