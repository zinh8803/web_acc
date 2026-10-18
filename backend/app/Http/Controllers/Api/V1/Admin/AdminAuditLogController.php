<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AdminAuditLogController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = AuditLog::with('adminUser:id,username,full_name')->orderByDesc('id')->paginate(30);
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
