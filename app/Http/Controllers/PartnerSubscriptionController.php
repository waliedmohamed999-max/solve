<?php

namespace App\Http\Controllers;

use App\Support\PartnerTenantStore;
use App\Support\PartnerWorkspace;
use App\Support\SubscriptionManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerSubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        return $this->view($request, 'partner.subscription.index', 'overview');
    }

    public function plans(Request $request): View
    {
        return $this->view($request, 'partner.subscription.plans', 'plans');
    }

    public function billing(Request $request): View
    {
        return $this->view($request, 'partner.subscription.billing', 'billing');
    }

    public function invoices(Request $request): View
    {
        return $this->view($request, 'partner.subscription.invoices', 'invoices');
    }

    public function paymentMethods(Request $request): View
    {
        return $this->view($request, 'partner.subscription.payment-methods', 'payment-methods');
    }

    public function checkout(Request $request, string $planId): View
    {
        $payload = $this->viewData($request, 'checkout');
        $payload['checkoutPlan'] = SubscriptionManager::plan($planId);

        return view('partner.subscription.checkout', $payload);
    }

    public function summaryApi(Request $request): JsonResponse
    {
        return response()->json(SubscriptionManager::partnerSummary($this->partner($request)));
    }

    public function plansApi(Request $request): JsonResponse
    {
        return response()->json(['plans' => SubscriptionManager::plans(false), 'current' => SubscriptionManager::partnerSummary($this->partner($request))['subscription']]);
    }

    public function upgradeApi(Request $request): JsonResponse
    {
        $validated = $request->validate(['plan' => ['required', 'string', 'max:120'], 'cycle' => ['nullable', 'in:monthly,yearly']]);

        return response()->json(SubscriptionManager::changePlan($this->partner($request), $validated['plan'], $validated['cycle'] ?? 'monthly', 'upgraded', $request));
    }

    public function downgradeApi(Request $request): JsonResponse
    {
        $validated = $request->validate(['plan' => ['required', 'string', 'max:120'], 'cycle' => ['nullable', 'in:monthly,yearly']]);

        return response()->json(SubscriptionManager::changePlan($this->partner($request), $validated['plan'], $validated['cycle'] ?? 'monthly', 'downgraded', $request));
    }

    public function cancelApi(Request $request): JsonResponse
    {
        return response()->json(SubscriptionManager::cancel($this->partner($request), $request));
    }

    public function renewApi(Request $request): JsonResponse
    {
        return response()->json(SubscriptionManager::renewPartner($this->partner($request), $request));
    }

    public function checkoutApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan' => ['required', 'string', 'max:120'],
            'cycle' => ['nullable', 'in:monthly,yearly'],
            'coupon' => ['nullable', 'string', 'max:80'],
        ]);

        return response()->json(SubscriptionManager::changePlan($this->partner($request), $validated['plan'], $validated['cycle'] ?? 'monthly', 'checkout', $request));
    }

    public function applyCouponApi(Request $request): JsonResponse
    {
        $validated = $request->validate(['coupon' => ['required', 'string', 'max:80'], 'plan' => ['nullable', 'string', 'max:120']]);
        $coupon = collect(SubscriptionManager::coupons())->first(fn ($row) => ($row['code'] ?? '') === strtoupper($validated['coupon']));

        return response()->json(['valid' => (bool) $coupon, 'coupon' => $coupon]);
    }

    public function featuresApi(Request $request): JsonResponse
    {
        $partner = $this->partner($request);
        $summary = SubscriptionManager::partnerSummary($partner);

        return response()->json(['features' => $summary['features'], 'subscription' => $summary['subscription']]);
    }

    public function checkFeatureApi(Request $request): JsonResponse
    {
        $data = $request->validate(['feature_key' => ['required', 'string', 'max:120']]);
        $partner = $this->partner($request);

        return response()->json([
            'feature_key' => $data['feature_key'],
            'allowed' => SubscriptionManager::featureAllowed($partner, $data['feature_key']),
            'subscription' => SubscriptionManager::partnerSummary($partner)['subscription'],
        ]);
    }

    public function usageApi(Request $request): JsonResponse
    {
        return response()->json(['usage' => SubscriptionManager::partnerSummary($this->partner($request))['subscription']['raw_usage']]);
    }

    public function checkUsageApi(Request $request): JsonResponse
    {
        $data = $request->validate(['resource' => ['required', 'string', 'max:80']]);
        $partner = $this->partner($request);

        return response()->json(['resource' => $data['resource'], 'limit_reached' => SubscriptionManager::limitReached($partner, $data['resource'])]);
    }

    public function retryInvoiceApi(Request $request, string $invoice): JsonResponse
    {
        $partner = $this->partner($request);
        $payload = SubscriptionManager::retryInvoice($invoice, $request, (string) $partner['store_id']);

        return response()->json($payload);
    }

    public function sendInvoiceApi(Request $request, string $invoice): JsonResponse
    {
        $partner = $this->partner($request);
        $payload = SubscriptionManager::sendInvoice($invoice, $request, (string) $partner['store_id']);

        return response()->json($payload);
    }

    public function invoicePdf(Request $request, string $invoice)
    {
        $partner = $this->partner($request);

        return response(SubscriptionManager::invoicePdf($invoice, (string) $partner['store_id']), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $invoice . '.pdf"',
        ]);
    }

    public function invoicesApi(Request $request): JsonResponse
    {
        return response()->json(['invoices' => SubscriptionManager::partnerSummary($this->partner($request))['invoices']]);
    }

    public function paymentMethodsApi(Request $request): JsonResponse
    {
        return response()->json(['payment_methods' => SubscriptionManager::partnerSummary($this->partner($request))['payment_methods']]);
    }

    public function storePaymentMethodApi(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'brand' => ['nullable', 'string', 'max:40'],
            'number' => ['required', 'string', 'max:40'],
            'holder' => ['nullable', 'string', 'max:160'],
        ]);

        return response()->json(SubscriptionManager::addPaymentMethod($this->partner($request), $validated, $request), 201);
    }

    private function view(Request $request, string $view, string $page): View
    {
        return view($view, $this->viewData($request, $page));
    }

    private function viewData(Request $request, string $page): array
    {
        $partner = $this->partner($request);
        $user = PartnerTenantStore::currentUser($request);

        return [
            'activeRoute' => 'partner.subscription',
            'activeSection' => 'subscription',
            'activePage' => $page,
            'partner' => $partner,
            'partnerUser' => $user,
            'roleLabel' => PartnerTenantStore::roleLabel((string) ($user['role'] ?? 'staff')),
            'partnerSections' => PartnerWorkspace::visibleSections($user, $partner),
            'recentPages' => $request->session()->get('partner_recent_pages', []),
            'subscriptionSuite' => SubscriptionManager::partnerSummary($partner),
        ];
    }

    private function partner(Request $request): array
    {
        $user = PartnerTenantStore::currentUser($request);
        $partner = PartnerTenantStore::currentPartner($request);

        abort_unless($user && $partner && ($user['store_id'] ?? null) === ($partner['store_id'] ?? null), 403);

        return $partner;
    }
}
