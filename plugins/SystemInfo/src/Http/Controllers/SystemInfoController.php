<?php

namespace Plugins\SystemInfo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use App\Http\Controller;

class SystemInfoController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'plugin' => 'system-info',
            'status' => 'public',
            'checked_at' => now()->toIso8601String(),
        ]);
    }
    
    public function privateStatus(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'plugin' => 'system-info',
            'status' => 'private',
            'user_id' => request()->header('X-Fulgurite-User-Id'),
            'checked_at' => now()->toIso8601String(),
        ]);
    }
}
