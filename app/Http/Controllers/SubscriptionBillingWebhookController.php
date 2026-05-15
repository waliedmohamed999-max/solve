<?php

namespace App\Http\Controllers;

use App\Support\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionBillingWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->getContent();
        $signature = (string) $request->header('X-Solve-Signature', '');

        return response()->json(SubscriptionManager::handleBillingWebhook($request->all(), $signature, $raw, $request));
    }
}
