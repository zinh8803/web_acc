<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, ['SUPER_ADMIN', 'STAFF_SERVICE', 'STAFF_SUPPORT'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập khu vực quản trị.',
            ], 403);
        }

        return $next($request);
    }
}
