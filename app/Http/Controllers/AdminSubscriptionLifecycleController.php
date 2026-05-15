<?php

namespace App\Http\Controllers;

use App\Models\PartnerStore;
use App\Support\PlatformAudit;
use App\Support\SubscriptionLifecycle;
use App\Support\SubscriptionPlans;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionLifecycleController extends Controller
{
    public function usage(string $store): JsonResponse
    {
        return response()->json([
            'usage' => SubscriptionLifecycle::usage($this->store($store)),
        ]);
    }

    public function renew(Request $request, string $store): JsonResponse
    {
        $data = $request->validate([
            'plan' => ['nullable', 'string', 'in:' . implode(',', SubscriptionPlans::names())],
            'months' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $record = SubscriptionLifecycle::renew($this->store($store), $data['plan'] ?? null, (int) ($data['months'] ?? 1));
        PlatformAudit::activity('subscription_renewed', 'partner_store', $record->store_id, [
            'store_id' => $record->store_id,
            'plan' => $record->plan,
            'renewal_at' => $record->subscription_renews_at?->toDateString(),
        ], $request);

        return response()->json([
            'store' => $record->fresh(),
            'usage' => SubscriptionLifecycle::usage($record),
        ]);
    }

    public function failPayment(Request $request, string $store): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:180'],
        ]);

        $record = SubscriptionLifecycle::markPaymentFailed($this->store($store), $data['reason'] ?? 'payment_failed');
        PlatformAudit::activity('subscription_payment_failed', 'partner_store', $record->store_id, [
            'store_id' => $record->store_id,
            'reason' => $data['reason'] ?? 'payment_failed',
        ], $request);

        return response()->json(['store' => $record->fresh()]);
    }

    public function enforce(Request $request): JsonResponse
    {
        $result = SubscriptionLifecycle::enforceExpirations();
        PlatformAudit::activity('subscription_enforcement_ran', 'subscriptions', null, $result, $request);

        return response()->json($result);
    }

    private function store(string $store): PartnerStore
    {
        return PartnerStore::query()
            ->where('store_id', $store)
            ->orWhere('partner_id', $store)
            ->firstOrFail();
    }
}
