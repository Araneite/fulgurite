<?php

namespace Plugins\Sandbox\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'service' => 'plugins-sandbox',
            'laravel' => app()->version(),
        ]);
    }
}
