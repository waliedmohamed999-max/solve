<?php

namespace App\Http\Controllers;

use App\Support\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlansBillingController extends Controller
{
    public function plans(): View
    {
        return view('admin.subscriptions.plans', [
            'activeRoute' => 'admin.plans',
            'plans' => SubscriptionManager::plans(),
        ]);
    }

    public function newPlan(): View
    {
        return view('admin.subscriptions.plan-form', [
            'activeRoute' => 'admin.plans',
            'plan' => null,
        ]);
    }

    public function editPlan(string $plan): View
    {
        return view('admin.subscriptions.plan-form', [
            'activeRoute' => 'admin.plans',
            'plan' => SubscriptionManager::plan($plan),
        ]);
    }

    public function subscriptions(Request $request): View
    {
        return view('admin.subscriptions.index', [
            'activeRoute' => 'admin.subscriptions',
            'dashboard' => SubscriptionManager::subscriptions($request),
        ]);
    }

    public function showSubscription(string $subscription): View
    {
        $storeId = str_starts_with($subscription, 'subscription-') ? substr($subscription, 13) : $subscription;

        return view('admin.subscriptions.show', [
            'activeRoute' => 'admin.subscriptions',
            'subscription' => SubscriptionManager::subscriptionByStoreId($storeId),
            'storeId' => $storeId,
        ]);
    }

    public function billing(): View
    {
        return view('admin.subscriptions.billing', [
            'activeRoute' => 'admin.billing',
            'billing' => SubscriptionManager::billing(),
        ]);
    }

    public function coupons(): View
    {
        return view('admin.subscriptions.coupons', [
            'activeRoute' => 'admin.coupons',
            'plans' => SubscriptionManager::plans(false),
            'coupons' => SubscriptionManager::coupons(),
        ]);
    }

    public function plansApi(): JsonResponse
    {
        return response()->json(['plans' => SubscriptionManager::plans()]);
    }

    public function storePlanApi(Request $request): JsonResponse
    {
        return response()->json(SubscriptionManager::savePlan($this->planPayload($request), null, $request), 201);
    }

    public function updatePlanApi(Request $request, string $plan): JsonResponse
    {
        return response()->json(SubscriptionManager::savePlan($this->planPayload($request), $plan, $request));
    }

    public function deletePlanApi(Request $request, string $plan): JsonResponse
    {
        return response()->json(SubscriptionManager::deletePlan($plan, $request));
    }

    public function subscriptionsApi(Request $request): JsonResponse
    {
        return response()->json(SubscriptionManager::subscriptions($request));
    }

    public function showSubscriptionApi(string $subscription): JsonResponse
    {
        $storeId = str_starts_with($subscription, 'subscription-') ? substr($subscription, 13) : $subscription;

        return response()->json(SubscriptionManager::subscriptionByStoreId($storeId));
    }

    public function updateSubscriptionStatusApi(Request $request, string $subscription): JsonResponse
    {
        $validated = $request->validate(['status' => ['required', 'string', 'max:60']]);
        $storeId = str_starts_with($subscription, 'subscription-') ? substr($subscription, 13) : $subscription;

        return response()->json(SubscriptionManager::updateStoreStatus($storeId, $validated['status'], $request));
    }

    public function updateSubscriptionPlanApi(Request $request, string $subscription): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:120'],
            'cycle' => ['nullable', 'in:monthly,yearly'],
        ]);
        $storeId = str_starts_with($subscription, 'subscription-') ? substr($subscription, 13) : $subscription;

        return response()->json(SubscriptionManager::updateStorePlan($storeId, $validated['plan'], $validated['cycle'] ?? 'monthly', $request));
    }

    public function billingApi(): JsonResponse
    {
        return response()->json(SubscriptionManager::billing());
    }

    public function invoicesApi(): JsonResponse
    {
        return response()->json(['invoices' => SubscriptionManager::billing()['invoices']]);
    }

    public function retryInvoiceApi(Request $request, string $invoice): JsonResponse
    {
        return response()->json(SubscriptionManager::retryInvoice($invoice, $request));
    }

    public function refundInvoiceApi(Request $request, string $invoice): JsonResponse
    {
        return response()->json(SubscriptionManager::refundInvoice($invoice, $request));
    }

    public function sendInvoiceApi(Request $request, string $invoice): JsonResponse
    {
        return response()->json(SubscriptionManager::sendInvoice($invoice, $request));
    }

    public function invoicePdf(string $invoice)
    {
        return response(SubscriptionManager::invoicePdf($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice . '.pdf"',
        ]);
    }

    public function couponsApi(): JsonResponse
    {
        return response()->json(['coupons' => SubscriptionManager::coupons()]);
    }

    public function storeCouponApi(Request $request): JsonResponse
    {
        return response()->json(SubscriptionManager::saveCoupon($this->couponPayload($request), null, $request), 201);
    }

    public function updateCouponApi(Request $request, string $coupon): JsonResponse
    {
        return response()->json(SubscriptionManager::saveCoupon($this->couponPayload($request), $coupon, $request));
    }

    private function planPayload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'yearly_price' => ['nullable', 'numeric', 'min:0'],
            'cycle' => ['nullable', 'in:monthly,annual,yearly'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'status' => ['nullable', 'string', 'max:60'],
            'recommended' => ['nullable', 'boolean'],
            'enterprise' => ['nullable', 'boolean'],
            'free' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable'],
            'limit_products' => ['nullable'],
            'limit_orders' => ['nullable'],
            'limit_staff' => ['nullable'],
            'limit_branches' => ['nullable'],
            'limit_apps' => ['nullable'],
            'limit_channels' => ['nullable'],
            'limit_ai_requests' => ['nullable'],
            'limit_automations' => ['nullable'],
            'pos' => ['nullable', 'boolean'],
            'apps' => ['nullable', 'boolean'],
            'apps_marketplace' => ['nullable', 'boolean'],
            'ai' => ['nullable', 'boolean'],
            'advanced_reports' => ['nullable', 'boolean'],
            'custom_domain' => ['nullable', 'boolean'],
            'real_payment_gateways' => ['nullable', 'boolean'],
            'shipping_integrations' => ['nullable', 'boolean'],
            'staff' => ['nullable', 'boolean'],
            'api_access' => ['nullable', 'boolean'],
            'automation' => ['nullable', 'boolean'],
        ]);
    }

    private function couponPayload(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:80'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'duration' => ['nullable', 'string', 'max:80'],
            'uses_limit' => ['nullable', 'integer', 'min:1'],
            'plan' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:60'],
        ]);
    }
}
