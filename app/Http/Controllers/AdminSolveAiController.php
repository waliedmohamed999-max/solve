<?php

namespace App\Http\Controllers;

use App\Support\PartnerSolveAi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSolveAiController extends Controller
{
    public function index(): View
    {
        return view('admin.solve-ai.index', [
            'activeRoute' => 'admin.solve-ai',
            'pageTitle' => 'ذكاء Solve',
            'usage' => PartnerSolveAi::adminUsage(),
            'tools' => PartnerSolveAi::adminTools(),
            'settings' => PartnerSolveAi::adminSettings(),
        ]);
    }

    public function tools(): View
    {
        return $this->index();
    }

    public function usage(): View
    {
        return $this->index();
    }

    public function settings(): View
    {
        return $this->index();
    }

    public function usageApi(): JsonResponse
    {
        return response()->json(PartnerSolveAi::adminUsage());
    }

    public function toolsApi(): JsonResponse
    {
        return response()->json(['tools' => PartnerSolveAi::adminTools()]);
    }

    public function updateToolApi(Request $request, string $tool): JsonResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        return response()->json(PartnerSolveAi::updateAdminTool($tool, $data));
    }

    public function updateSettingsApi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'free_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'pro_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'enterprise_limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'data_retention_days' => ['nullable', 'integer', 'min:7', 'max:3650'],
        ]);

        return response()->json(PartnerSolveAi::updateAdminSettings($data));
    }
}
