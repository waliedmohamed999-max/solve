<?php

namespace App\Http\Controllers;

use App\Support\PlatformHealth;
use Illuminate\Http\JsonResponse;

class PlatformHealthController extends Controller
{
    public function public(): JsonResponse
    {
        $health = PlatformHealth::summary(false);

        return response()->json([
            'status' => $health['status'],
            'timestamp' => $health['timestamp'],
        ], $health['status'] === 'ok' ? 200 : 503);
    }

    public function admin(): JsonResponse
    {
        $health = PlatformHealth::summary(true);

        return response()->json($health, $health['status'] === 'ok' ? 200 : 503);
    }
}
