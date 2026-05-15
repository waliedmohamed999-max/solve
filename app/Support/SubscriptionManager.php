<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SubscriptionManager
{
    public static function plans(bool $includeInactive = true): array
    {
        $defaults = collect(SubscriptionPlans::defaults())->mapWithKeys(fn (array $plan, string $key) => [$key => self::normalizePlan($plan, $key)]);

        if (! Schema::hasTable('platform_records')) {
            return $defaults->values()->all();
        }

        $stored = PlatformRecord::query()
            ->where('section', 'subscription_plans')
            ->orderBy('payload->sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function (PlatformRecord $record) {
                $payload = self::normalizePlan($record->payload ?? [], $record->record_id);

                return [$payload['name'] => $payload];
            });

        $plans = $defaults->merge($stored)
            ->when(! $includeInactive, fn ($items) => $items->filter(fn (array $plan) => ($plan['status'] ?? 'active') === 'active'))
            ->sortBy(fn (array $plan) => (int) ($plan['sort_order'] ?? 99))
            ->values()
            ->all();

        return $plans;
    }

    public static function plan(string $name): array
    {
        $normalized = Str::lower($name);

        return collect(self::plans())
            ->first(fn (array $plan) => Str::lower($plan['name']) === $normalized || Str::lower($plan['slug']) === $normalized || Str::lower($plan['id']) === $normalized)
            ?? self::normalizePlan(SubscriptionPlans::find($name), $name);
    }

    public static function savePlan(array $data, ?string $id = null, ?Request $request = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $name = trim((string) ($data['name'] ?? $id ?? 'Plan'));
        $recordId = $id ?: 'plan-' . Str::slug($name);
        $plan = self::normalizePlan([
            'name' => $name,
            'slug' => Str::slug($data['slug'] ?? $name),
            'price' => self::nullableNumber($data['price'] ?? null),
            'yearly_price' => self::nullableNumber($data['yearly_price'] ?? null),
            'cycle' => $data['cycle'] ?? 'monthly',
            'trial_days' => (int) ($data['trial_days'] ?? 14),
            'status' => $data['status'] ?? 'active',
            'recommended' => (bool) ($data['recommended'] ?? false),
            'enterprise' => (bool) ($data['enterprise'] ?? false),
            'free' => (bool) ($data['free'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 50),
            'features' => self::lines($data['features'] ?? []),
            'feature_flags' => self::featureFlags($data),
            'limits' => self::limits($data),
        ], $recordId);

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'subscription_plans', 'record_id' => $recordId],
            [
                'store_id' => null,
                'partner_id' => null,
                'status' => $plan['status'],
                'payload' => $plan,
            ],
        );

        PlatformAudit::activity('plan.saved', 'subscription_plan', $recordId, ['plan' => $plan['name']], $request);

        return $plan;
    }

    public static function deletePlan(string $id, ?Request $request = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $plan = self::plan($id);
        $inUse = PartnerStore::query()->where('plan', $plan['name'])->exists();
        $record = PlatformRecord::query()
            ->where('section', 'subscription_plans')
            ->where(function ($query) use ($id, $plan) {
                $query->where('record_id', $id)->orWhere('payload->name', $plan['name']);
            })
            ->first();

        if ($inUse) {
            $payload = $record?->payload ?? $plan;
            $payload['status'] = 'inactive';
            PlatformRecord::query()->updateOrCreate(
                ['section' => 'subscription_plans', 'record_id' => $record?->record_id ?: 'plan-' . Str::slug($plan['name'])],
                ['status' => 'inactive', 'payload' => $payload],
            );
        } elseif ($record) {
            $record->delete();
        }

        PlatformAudit::activity('plan.disabled', 'subscription_plan', $id, ['in_use' => $inUse], $request);

        return ['deleted' => ! $inUse, 'disabled' => $inUse, 'plan' => $plan['name']];
    }

    public static function subscriptions(Request $request): array
    {
        PartnerTenantStore::partners();

        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $stores = PartnerStore::query()
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('store_id', 'like', "%{$q}%")
                    ->orWhere('owner_email', 'like', "%{$q}%");
            }))
            ->when($status !== 'all' && $status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('subscription_renews_at')
            ->paginate(max(1, min(50, (int) $request->query('per_page', 20))));

        return [
            'subscriptions' => $stores->getCollection()->map(fn (PartnerStore $store) => self::storeSubscription($store))->values()->all(),
            'summary' => self::adminSummary(),
            'pagination' => [
                'page' => $stores->currentPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
                'last_page' => $stores->lastPage(),
            ],
        ];
    }

    public static function storeSubscription(PartnerStore $store): array
    {
        $plan = self::plan($store->plan ?: 'Starter');
        $usage = SubscriptionLifecycle::usage($store);
        $renewal = $store->subscription_renews_at;
        $daysLeft = $renewal ? now()->startOfDay()->diffInDays($renewal, false) : null;

        return [
            'id' => 'subscription-' . $store->store_id,
            'store_id' => $store->store_id,
            'store' => $store->name,
            'owner' => $store->owner_name,
            'owner_email' => $store->owner_email,
            'status' => self::subscriptionStatus($store),
            'plan' => $plan,
            'plan_name' => $plan['name'],
            'payment_status' => $store->payment_status ?: 'pending',
            'started_at' => $store->subscription_started_at?->toDateString(),
            'renews_at' => $store->subscription_renews_at?->toDateString(),
            'days_left' => $daysLeft,
            'trial' => self::subscriptionStatus($store) === 'trial',
            'usage' => self::usageBars($usage),
            'raw_usage' => $usage,
            'auto_renew' => (bool) data_get($store->metadata, 'auto_renew', true),
            'last_payment' => data_get($store->metadata, 'last_payment_at'),
        ];
    }

    public static function subscriptionByStoreId(string $storeId): array
    {
        return self::storeSubscription(self::storeById($storeId));
    }

    public static function partnerSummary(array $partner): array
    {
        if (! Schema::hasTable('partner_stores')) {
            $plan = self::plan((string) ($partner['plan'] ?? 'Starter'));
            $counts = [
                'products' => count($partner['products'] ?? []),
                'orders' => count($partner['orders'] ?? []),
                'staff' => count($partner['users'] ?? []),
                'apps' => 0,
                'channels' => 1,
                'ai_requests' => 0,
                'automations' => 0,
            ];
            $usage = [
                'store_id' => $partner['store_id'],
                'plan' => $plan['name'],
                'limits' => $plan['limits'],
                'counts' => $counts,
                'exceeded' => [],
            ];
            $subscription = [
                'id' => 'subscription-' . $partner['store_id'],
                'store_id' => $partner['store_id'],
                'store' => $partner['name'],
                'owner' => $partner['owner'] ?? null,
                'owner_email' => $partner['email'] ?? null,
                'status' => 'active',
                'plan' => $plan,
                'plan_name' => $plan['name'],
                'payment_status' => $partner['payment_status'] ?? 'paid',
                'started_at' => $partner['subscription_at'] ?? null,
                'renews_at' => $partner['renewal_at'] ?? null,
                'days_left' => null,
                'trial' => false,
                'usage' => self::usageBars($usage),
                'raw_usage' => $usage,
                'auto_renew' => true,
                'last_payment' => null,
            ];

            return [
                'subscription' => $subscription,
                'plans' => self::plans(false),
                'invoices' => [],
                'payment_methods' => [],
                'alerts' => self::alerts($subscription),
                'features' => self::featuresFor($plan),
            ];
        }

        $store = self::storeForPartner($partner);
        $subscription = self::storeSubscription($store);

        return [
            'subscription' => $subscription,
            'plans' => self::plans(false),
            'invoices' => self::invoices($store->store_id),
            'payment_methods' => self::paymentMethods($store->store_id),
            'alerts' => self::alerts($subscription),
            'features' => self::featuresFor($subscription['plan']),
        ];
    }

    public static function changePlan(array $partner, string $plan, string $cycle, string $action, ?Request $request = null): array
    {
        $store = self::storeForPartner($partner);
        $target = self::plan($plan);
        $months = $cycle === 'yearly' ? 12 : 1;

        $store = SubscriptionLifecycle::renew($store, $target['name'], $months);
        $price = $cycle === 'yearly' ? ($target['yearly_price'] ?? null) : ($target['price'] ?? null);
        $invoice = self::createInvoice($store, $target, $price, $action, $price > 0 ? 'pending' : 'paid');
        if ($price > 0) {
            self::createPaymentAttempt($store, $invoice, $request);
        }
        PlatformAudit::activity('subscription.' . $action, 'subscription', 'subscription-' . $store->store_id, [
            'store_id' => $store->store_id,
            'plan' => $target['name'],
            'cycle' => $cycle,
        ], $request);

        return self::partnerSummary(PartnerTenantStore::findPartner($store->store_id) ?? $partner);
    }

    public static function updateStoreStatus(string $storeId, string $status, ?Request $request = null): array
    {
        $store = self::storeById($storeId);
        $store->forceFill([
            'status' => $status,
            'metadata' => array_merge($store->metadata ?? [], ['subscription_status_updated_at' => now()->toIso8601String()]),
        ])->save();

        SubscriptionLifecycle::syncSubscriptionRecord($store->fresh(), $status);
        PlatformAudit::activity('subscription.status_updated', 'subscription', 'subscription-' . $storeId, ['status' => $status], $request);

        return self::storeSubscription($store->fresh());
    }

    public static function updateStorePlan(string $storeId, string $plan, string $cycle = 'monthly', ?Request $request = null): array
    {
        $store = self::storeById($storeId);
        $target = self::plan($plan);
        $months = $cycle === 'yearly' ? 12 : 1;
        $store = SubscriptionLifecycle::renew($store, $target['name'], $months);
        $price = $cycle === 'yearly' ? ($target['yearly_price'] ?? null) : ($target['price'] ?? null);
        $invoice = self::createInvoice($store, $target, $price, 'admin_plan_change', $price > 0 ? 'pending' : 'paid');
        if ($price > 0) {
            self::createPaymentAttempt($store, $invoice, $request);
        }
        PlatformAudit::activity('subscription.plan_changed', 'subscription', 'subscription-' . $storeId, ['store_id' => $storeId, 'plan' => $target['name']], $request);

        return self::storeSubscription($store);
    }

    public static function cancel(array $partner, ?Request $request = null): array
    {
        $store = self::storeForPartner($partner);
        $store->forceFill([
            'status' => 'cancelled',
            'metadata' => array_merge($store->metadata ?? [], ['cancelled_at' => now()->toIso8601String(), 'auto_renew' => false]),
        ])->save();
        SubscriptionLifecycle::syncSubscriptionRecord($store->fresh(), 'cancelled');
        PlatformAudit::activity('subscription.cancelled', 'subscription', 'subscription-' . $store->store_id, ['store_id' => $store->store_id], $request);

        return self::partnerSummary(PartnerTenantStore::findPartner($store->store_id) ?? $partner);
    }

    public static function renewPartner(array $partner, ?Request $request = null): array
    {
        return self::changePlan($partner, (string) ($partner['plan'] ?? 'Starter'), 'monthly', 'renewed', $request);
    }

    public static function billing(): array
    {
        $invoices = self::allInvoices();
        $payments = self::allBillingPayments();

        return [
            'summary' => [
                'invoices' => count($invoices),
                'paid' => collect($invoices)->where('status', 'paid')->sum('amount'),
                'pending' => collect($invoices)->where('status', 'pending')->sum('amount'),
                'failed' => collect($invoices)->where('status', 'failed')->sum('amount'),
                'payments' => count($payments),
                'refunded' => collect($invoices)->where('status', 'refunded')->sum('amount'),
            ],
            'invoices' => $invoices,
            'payments' => $payments,
        ];
    }

    public static function coupons(): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'subscription_coupons')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PlatformRecord $record) => array_merge(['id' => $record->record_id, 'status' => $record->status], $record->payload ?? []))
            ->values()
            ->all();
    }

    public static function saveCoupon(array $data, ?string $id = null, ?Request $request = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $recordId = $id ?: 'coupon-' . Str::upper(Str::random(6));
        $payload = [
            'id' => $recordId,
            'code' => Str::upper($data['code'] ?? Str::replace('coupon-', '', $recordId)),
            'type' => $data['type'] ?? 'percent',
            'value' => (float) ($data['value'] ?? 0),
            'duration' => $data['duration'] ?? 'once',
            'uses_limit' => (int) ($data['uses_limit'] ?? 100),
            'used' => (int) ($data['used'] ?? 0),
            'plan' => $data['plan'] ?? 'all',
            'status' => $data['status'] ?? 'active',
        ];

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'subscription_coupons', 'record_id' => $recordId],
            ['status' => $payload['status'], 'payload' => $payload],
        );
        PlatformAudit::activity('subscription_coupon.saved', 'subscription_coupon', $recordId, ['code' => $payload['code']], $request);

        return $payload;
    }

    public static function retryInvoice(string $invoiceId, ?Request $request = null, ?string $storeId = null): array
    {
        $invoice = self::invoiceRecord($invoiceId, $storeId);
        $store = self::storeById((string) $invoice->store_id);
        $payload = $invoice->payload ?? [];
        $payload['status'] = 'pending';
        $payload['retry_count'] = (int) ($payload['retry_count'] ?? 0) + 1;
        $payload['retried_at'] = now()->toIso8601String();
        $invoice->update(['status' => 'pending', 'payload' => $payload]);
        $payment = self::createPaymentAttempt($store, $payload, $request, 'retry');
        PlatformAudit::activity('billing.payment_retry', 'subscription_invoice', $invoiceId, ['store_id' => $store->store_id, 'payment_id' => $payment['id']], $request);

        return $payment;
    }

    public static function refundInvoice(string $invoiceId, ?Request $request = null): array
    {
        $invoice = self::invoiceRecord($invoiceId);
        $payload = $invoice->payload ?? [];
        $payload['status'] = 'refunded';
        $payload['refunded_at'] = now()->toIso8601String();
        $invoice->update(['status' => 'refunded', 'payload' => $payload]);
        self::markBillingPayments($invoiceId, 'refunded');
        PlatformAudit::activity('billing.refund_created', 'subscription_invoice', $invoiceId, ['store_id' => $invoice->store_id, 'amount' => $payload['amount'] ?? 0], $request);
        PlatformAudit::notify('billing_refund', 'Subscription invoice refunded', 'A subscription invoice was refunded.', ['store_id' => $invoice->store_id, 'severity' => 'warning']);

        return $payload + ['id' => $invoice->record_id];
    }

    public static function sendInvoice(string $invoiceId, ?Request $request = null, ?string $storeId = null): array
    {
        $invoice = self::invoiceRecord($invoiceId, $storeId);
        $payload = $invoice->payload ?? [];
        $payload['sent_at'] = now()->toIso8601String();
        $invoice->update(['payload' => $payload]);
        PlatformAudit::activity('billing.invoice_sent', 'subscription_invoice', $invoiceId, ['store_id' => $invoice->store_id, 'email' => $payload['owner_email'] ?? null], $request);

        return $payload + ['id' => $invoice->record_id, 'sent' => true];
    }

    public static function invoicePdf(string $invoiceId, ?string $storeId = null): string
    {
        $invoice = self::invoiceRecord($invoiceId, $storeId);
        $payload = $invoice->payload ?? [];

        return implode("\n", [
            'SOLVE SUBSCRIPTION INVOICE',
            'Invoice: ' . ($payload['invoice_number'] ?? $invoice->record_id),
            'Store: ' . ($payload['store'] ?? $invoice->store_id),
            'Plan: ' . ($payload['plan'] ?? '-'),
            'Amount: ' . ($payload['amount'] ?? 0) . ' ' . ($payload['currency'] ?? 'SAR'),
            'Status: ' . ($payload['status'] ?? $invoice->status),
            'Issued: ' . ($payload['issued_at'] ?? '-'),
        ]);
    }

    public static function handleBillingWebhook(array $event, string $signature, string $rawBody, ?Request $request = null): array
    {
        self::verifyBillingSignature($signature, $rawBody);

        $type = (string) ($event['type'] ?? '');
        $data = (array) ($event['data'] ?? []);
        $invoiceId = (string) ($data['invoice_id'] ?? $data['id'] ?? '');
        $storeId = (string) ($data['store_id'] ?? '');
        abort_if($type === '' || $invoiceId === '', 422, 'Invalid billing webhook payload.');

        $invoice = self::invoiceRecord($invoiceId, $storeId ?: null);
        $store = self::storeById((string) $invoice->store_id);
        $payload = $invoice->payload ?? [];

        match ($type) {
            'payment_success', 'invoice_paid', 'subscription_renewed' => self::markInvoicePaid($invoice, $store, $data, $type),
            'payment_failed', 'invoice_failed' => self::markInvoiceFailed($invoice, $store, $data, $type),
            'subscription_cancelled' => self::cancelStoreFromWebhook($invoice, $store, $data, $type),
            'refund_created' => self::refundInvoice($invoice->record_id, $request),
            default => abort(422, 'Unsupported billing webhook event.'),
        };

        PlatformRecord::query()->create([
            'section' => 'subscription_webhooks',
            'record_id' => 'billing-webhook-' . Str::lower(Str::random(10)),
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'status' => $type,
            'payload' => ['type' => $type, 'invoice_id' => $invoice->record_id, 'data' => $data, 'received_at' => now()->toIso8601String()],
        ]);
        PlatformAudit::activity('billing.webhook.' . $type, 'subscription_invoice', $invoice->record_id, ['store_id' => $store->store_id, 'payload' => $payload], $request);

        return ['ok' => true, 'type' => $type, 'invoice_id' => $invoice->record_id, 'store_id' => $store->store_id];
    }

    public static function addPaymentMethod(array $partner, array $data, ?Request $request = null): array
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $storeId = (string) $partner['store_id'];
        $recordId = 'payment-method-' . Str::lower(Str::random(8));
        $payload = [
            'id' => $recordId,
            'brand' => $data['brand'] ?? 'Mada',
            'last4' => substr(preg_replace('/\D+/', '', (string) ($data['number'] ?? '0000')), -4) ?: '0000',
            'holder' => $data['holder'] ?? ($partner['owner'] ?? $partner['name']),
            'status' => 'active',
            'created_at' => now()->toDateString(),
        ];

        PlatformRecord::query()->create([
            'section' => 'subscription_payment_methods',
            'record_id' => $recordId,
            'store_id' => $storeId,
            'partner_id' => $partner['id'] ?? null,
            'status' => 'active',
            'payload' => $payload,
        ]);
        PlatformAudit::activity('subscription.payment_method_added', 'payment_method', $recordId, ['store_id' => $storeId], $request);

        return $payload;
    }

    public static function featureAllowed(array $partner, string $feature): bool
    {
        return (bool) data_get(self::plan((string) ($partner['plan'] ?? 'Starter')), 'feature_flags.' . $feature, false);
    }

    public static function accessDecision(array $partner, Request $request, ?string $ability = null): array
    {
        $path = trim($request->path(), '/');
        $method = $request->method();
        $isApi = str_starts_with($path, 'api/partner');
        $isSubscriptionArea = str_contains($path, 'subscription') || str_contains($path, 'payment-methods') || str_starts_with($path, 'api/partner/invoices') || $path === 'api/partner/store/status';
        $isRead = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);
        $status = self::normalizedPartnerStatus($partner);

        if ($isSubscriptionArea) {
            return self::allowDecision();
        }

        if (in_array($status, ['expired', 'cancelled'], true)) {
            return self::denyDecision('subscription_expired', 'اشتراك المتجر منته. يرجى تجديد الاشتراك للمتابعة.', 402);
        }

        if ($status === 'suspended' && ! $isRead) {
            return self::denyDecision('subscription_suspended', 'المتجر موقوف من الإدارة. لا يمكن تنفيذ عمليات على المتجر حالياً.', 423);
        }

        if (in_array($status, ['past_due', 'payment_failed'], true) && (! $isRead || $isApi)) {
            return self::denyDecision('payment_failed', 'توجد مشكلة في دفع الاشتراك. حدّث الفوترة قبل استخدام هذه العملية.', 402);
        }

        $feature = self::featureForRequest($path, $ability);

        if ($feature && ! self::featureAllowed($partner, $feature)) {
            return self::denyDecision('feature_locked', 'هذه الميزة غير متاحة في باقتك الحالية. قم بالترقية لاستخدامها.', 402, $feature);
        }

        return self::allowDecision();
    }

    public static function recordDeniedAccess(array $partner, ?array $user, Request $request, array $decision): void
    {
        PlatformAudit::activity('subscription.access_denied', 'subscription_gate', $decision['feature'] ?? $decision['reason'], [
            'store_id' => $partner['store_id'] ?? null,
            'partner_id' => $partner['id'] ?? null,
            'path' => $request->path(),
            'method' => $request->method(),
            'reason' => $decision['reason'] ?? 'locked',
            'feature' => $decision['feature'] ?? null,
            'role' => $user['role'] ?? null,
        ], $request);
    }

    public static function limitReached(array $partner, string $resource): bool
    {
        if (! Schema::hasTable('partner_stores')) {
            return false;
        }

        $store = self::storeForPartner($partner);
        $usage = SubscriptionLifecycle::usage($store);
        $limit = $usage['limits'][$resource] ?? 'unlimited';

        return $limit !== 'unlimited' && (int) ($usage['counts'][$resource] ?? 0) >= (int) $limit;
    }

    public static function usageDenied(string $resource): array
    {
        return self::denyDecision('usage_limit_reached', 'وصلت للحد المتاح لهذه الميزة في باقتك الحالية. قم بالترقية للمتابعة.', 402, $resource);
    }

    public static function recordUsageDenied(array $partner, ?array $actor, string $resource): void
    {
        PlatformAudit::activity('subscription.usage_denied', 'subscription', (string) ($partner['subscription_id'] ?? $partner['store_id'] ?? ''), [
            'store_id' => $partner['store_id'] ?? null,
            'partner_id' => $partner['id'] ?? null,
            'resource' => $resource,
            'actor' => $actor['username'] ?? $actor['name'] ?? null,
            'role' => $actor['role'] ?? null,
        ]);
    }

    private static function normalizePlan(array $plan, string $fallback): array
    {
        $name = (string) ($plan['name'] ?? Str::headline($fallback));
        $limits = array_merge([
            'products' => 0,
            'orders' => 0,
            'staff' => 0,
            'branches' => 0,
            'apps' => 0,
            'channels' => 0,
            'ai_requests' => 0,
            'automations' => 0,
        ], $plan['limits'] ?? []);

        return [
            'id' => $plan['id'] ?? 'plan-' . Str::slug($name),
            'name' => $name,
            'slug' => $plan['slug'] ?? Str::slug($name),
            'price' => $plan['price'] ?? null,
            'yearly_price' => $plan['yearly_price'] ?? null,
            'cycle' => $plan['cycle'] ?? 'monthly',
            'trial_days' => (int) ($plan['trial_days'] ?? 14),
            'status' => $plan['status'] ?? 'active',
            'recommended' => (bool) ($plan['recommended'] ?? false),
            'enterprise' => (bool) ($plan['enterprise'] ?? false),
            'free' => (bool) ($plan['free'] ?? (($plan['price'] ?? null) === 0)),
            'sort_order' => (int) ($plan['sort_order'] ?? self::defaultSort($name)),
            'features' => array_values($plan['features'] ?? []),
            'feature_flags' => array_merge([
                'pos' => false,
                'apps' => false,
                'ai' => false,
                'advanced_reports' => false,
                'custom_domain' => false,
                'real_payment_gateways' => false,
                'shipping_integrations' => false,
                'apps_marketplace' => false,
                'staff' => false,
                'api_access' => false,
                'automation' => false,
            ], $plan['feature_flags'] ?? []),
            'limits' => $limits,
            'price_label' => ($plan['price'] ?? null) === null ? 'Custom' : number_format((float) $plan['price']) . ' SAR',
            'yearly_price_label' => ($plan['yearly_price'] ?? null) === null ? 'Custom' : number_format((float) $plan['yearly_price']) . ' SAR',
        ];
    }

    private static function defaultSort(string $name): int
    {
        return match ($name) {
            'Free' => 0,
            'Starter' => 10,
            'Basic' => 10,
            'Growth' => 20,
            'Pro' => 20,
            'Enterprise' => 30,
            default => 50,
        };
    }

    private static function lines(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $value))));
    }

    private static function featureFlags(array $data): array
    {
        $flags = Arr::only($data, ['pos', 'apps', 'apps_marketplace', 'ai', 'advanced_reports', 'custom_domain', 'real_payment_gateways', 'shipping_integrations', 'staff', 'api_access', 'automation']);

        return collect($flags)->map(fn ($value) => filter_var($value, FILTER_VALIDATE_BOOL))->all();
    }

    private static function limits(array $data): array
    {
        $limits = [];

        foreach (['products', 'orders', 'staff', 'branches', 'apps', 'channels', 'ai_requests', 'automations'] as $key) {
            $value = $data['limit_' . $key] ?? data_get($data, 'limits.' . $key, 0);
            $limits[$key] = $value === 'unlimited' ? 'unlimited' : max(0, (int) $value);
        }

        return $limits;
    }

    private static function nullableNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? $value + 0 : null;
    }

    private static function storeForPartner(array $partner): PartnerStore
    {
        return self::storeById((string) $partner['store_id']);
    }

    private static function storeById(string $storeId): PartnerStore
    {
        $store = PartnerStore::query()->where('store_id', $storeId)->first();
        abort_unless($store, 404);

        return $store;
    }

    private static function subscriptionStatus(PartnerStore $store): string
    {
        $status = Str::lower((string) $store->status);

        if (str_contains($status, 'trial')) {
            return 'trial';
        }

        if ($status === 'free' || $store->plan === 'Free') {
            return 'free';
        }

        if (in_array($status, ['suspended', 'expired', 'cancelled', 'past_due'], true)) {
            return $status;
        }

        if ($store->subscription_renews_at && $store->subscription_renews_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    private static function normalizedPartnerStatus(array $partner): string
    {
        $status = Str::lower((string) ($partner['status'] ?? 'active'));
        $payment = Str::lower((string) ($partner['payment_status'] ?? 'paid'));
        $renewal = $partner['renewal_at'] ?? null;

        if (str_contains($status, 'suspended') || str_contains($status, 'موقوف')) {
            return 'suspended';
        }

        if ($status === 'free' || ($partner['plan'] ?? null) === 'Free') {
            return 'free';
        }

        if (str_contains($status, 'cancelled') || str_contains($status, 'canceled')) {
            return 'cancelled';
        }

        if (in_array($status, ['past_due', 'payment_failed'], true) || in_array($payment, ['failed', 'past_due'], true)) {
            return 'past_due';
        }

        if ($renewal && now()->startOfDay()->greaterThan(\Illuminate\Support\Carbon::parse($renewal)->startOfDay())) {
            return 'expired';
        }

        return 'active';
    }

    private static function featureForRequest(string $path, ?string $ability): ?string
    {
        if (str_contains($path, '/ai') || str_ends_with($path, 'ai') || str_contains($path, 'ai/')) {
            return 'ai';
        }

        if (str_contains($path, 'automations') || str_contains($path, 'automation')) {
            return 'automation';
        }

        if (str_contains($path, 'channels/pos')) {
            return 'pos';
        }

        if (str_contains($path, 'services/payment-gateways')) {
            return 'real_payment_gateways';
        }

        if (str_contains($path, 'services/logistics')) {
            return 'shipping_integrations';
        }

        if (str_contains($path, 'storefront/domain') || str_contains($path, 'settings/domain') || str_contains($path, '/domain') || str_ends_with($path, 'domain')) {
            return 'custom_domain';
        }

        if ($ability === 'manage-apps') {
            return 'apps_marketplace';
        }

        if (str_contains($path, 'api-access') || str_contains($path, 'settings/api') || str_contains($path, 'api/settings')) {
            return 'api_access';
        }

        if ($ability === 'view-analytics' && (str_contains($path, 'analytics/live') || str_contains($path, 'analytics/finance') || str_contains($path, 'analytics/operations'))) {
            return 'advanced_reports';
        }

        return null;
    }

    private static function allowDecision(): array
    {
        return ['allowed' => true, 'reason' => null, 'code' => 200, 'feature' => null, 'message' => null];
    }

    private static function denyDecision(string $reason, string $message, int $code, ?string $feature = null): array
    {
        return [
            'allowed' => false,
            'reason' => $reason,
            'code' => $code,
            'feature' => $feature,
            'message' => $message,
            'upgrade_prompt' => [
                'title' => 'ترقية الباقة مطلوبة',
                'body' => $message,
                'url' => route('partner.subscription.plans'),
            ],
        ];
    }

    private static function usageBars(array $usage): array
    {
        return collect($usage['counts'])->map(function (int $count, string $key) use ($usage) {
            $limit = $usage['limits'][$key] ?? 'unlimited';
            $percent = $limit === 'unlimited' ? 0 : min(100, (int) round(($count / max(1, (int) $limit)) * 100));

            return [
                'key' => $key,
                'used' => $count,
                'limit' => $limit,
                'remaining' => $limit === 'unlimited' ? 'unlimited' : max(0, (int) $limit - $count),
                'percent' => $percent,
                'blocked' => $limit !== 'unlimited' && $count >= (int) $limit,
            ];
        })->values()->all();
    }

    private static function featuresFor(array $plan): array
    {
        return collect($plan['feature_flags'])->map(fn ($enabled, $key) => [
            'key' => $key,
            'enabled' => (bool) $enabled,
            'label' => Str::headline(str_replace('_', ' ', $key)),
        ])->values()->all();
    }

    private static function alerts(array $subscription): array
    {
        $alerts = [];

        if (($subscription['days_left'] ?? 99) !== null && (int) $subscription['days_left'] <= 7) {
            $alerts[] = ['type' => 'expiry', 'tone' => 'warning', 'message' => 'Subscription renews in ' . $subscription['days_left'] . ' days.'];
        }

        foreach ($subscription['usage'] as $usage) {
            if ($usage['blocked']) {
                $alerts[] = ['type' => 'limit', 'tone' => 'danger', 'message' => 'Usage limit reached for ' . $usage['key'] . '.'];
            }
        }

        return $alerts;
    }

    private static function createInvoice(PartnerStore $store, array $plan, int|float|null $amount, string $action, string $status = 'paid'): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        $recordId = 'sub-invoice-' . $store->store_id . '-' . now()->format('YmdHis');
        $invoiceNumber = self::nextInvoiceNumber();
        $payload = [
            'id' => $recordId,
            'invoice_number' => $invoiceNumber,
            'store_id' => $store->store_id,
            'store' => $store->name,
            'owner_email' => $store->owner_email,
            'plan' => $plan['name'],
            'action' => $action,
            'amount' => $amount ?? 0,
            'currency' => 'SAR',
            'status' => $status,
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(7)->toDateString(),
            'paid_at' => $status === 'paid' ? now()->toDateString() : null,
            'provider' => config('services.billing.provider', 'solve-pay'),
        ];

        PlatformRecord::query()->create([
            'section' => 'subscription_invoices',
            'record_id' => $recordId,
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'status' => $payload['status'],
            'payload' => $payload,
        ]);

        return $payload;
    }

    private static function invoices(string $storeId): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'subscription_invoices')
            ->where('store_id', $storeId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload + ['id' => $record->record_id, 'status' => $record->status])
            ->values()
            ->all();
    }

    private static function allInvoices(): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'subscription_invoices')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload + ['id' => $record->record_id, 'status' => $record->status])
            ->values()
            ->all();
    }

    private static function allBillingPayments(): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'subscription_payments')
            ->orderByDesc('id')
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload + ['id' => $record->record_id, 'status' => $record->status])
            ->values()
            ->all();
    }

    private static function paymentMethods(string $storeId): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'subscription_payment_methods')
            ->where('store_id', $storeId)
            ->orderByDesc('id')
            ->get()
            ->map(fn (PlatformRecord $record) => $record->payload + ['id' => $record->record_id])
            ->values()
            ->all();
    }

    private static function createPaymentAttempt(PartnerStore $store, array $invoice, ?Request $request = null, string $kind = 'charge'): array
    {
        $recordId = 'sub-payment-' . Str::lower(Str::random(10));
        $payload = [
            'id' => $recordId,
            'provider' => config('services.billing.provider', 'solve-pay'),
            'kind' => $kind,
            'store_id' => $store->store_id,
            'invoice_id' => $invoice['id'],
            'invoice_number' => $invoice['invoice_number'] ?? null,
            'amount' => (float) ($invoice['amount'] ?? 0),
            'currency' => $invoice['currency'] ?? 'SAR',
            'status' => 'pending',
            'checkout_url' => url('/partner/subscription/billing?payment=' . $recordId),
            'attempted_at' => now()->toIso8601String(),
        ];

        PlatformRecord::query()->create([
            'section' => 'subscription_payments',
            'record_id' => $recordId,
            'store_id' => $store->store_id,
            'partner_id' => $store->partner_id,
            'status' => 'pending',
            'payload' => $payload,
        ]);
        PlatformAudit::activity('billing.payment_attempt_created', 'subscription_payment', $recordId, ['store_id' => $store->store_id, 'invoice_id' => $invoice['id']], $request);

        return $payload;
    }

    private static function nextInvoiceNumber(): string
    {
        $count = Schema::hasTable('platform_records')
            ? PlatformRecord::query()->where('section', 'subscription_invoices')->count() + 1
            : 1;

        return 'SUB-' . now()->format('Ym') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private static function invoiceRecord(string $invoiceId, ?string $storeId = null): PlatformRecord
    {
        $query = PlatformRecord::query()
            ->where('section', 'subscription_invoices')
            ->where(function ($inner) use ($invoiceId) {
                $inner->where('record_id', $invoiceId)
                    ->orWhere('payload->id', $invoiceId)
                    ->orWhere('payload->invoice_number', $invoiceId);
            });

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        return $query->firstOrFail();
    }

    private static function markBillingPayments(string $invoiceId, string $status): void
    {
        PlatformRecord::query()
            ->where('section', 'subscription_payments')
            ->where('payload->invoice_id', $invoiceId)
            ->get()
            ->each(function (PlatformRecord $payment) use ($status) {
                $payload = $payment->payload ?? [];
                $payload['status'] = $status;
                $payload[$status . '_at'] = now()->toIso8601String();
                $payment->update(['status' => $status, 'payload' => $payload]);
            });
    }

    private static function verifyBillingSignature(string $signature, string $rawBody): void
    {
        $expected = hash_hmac('sha256', $rawBody, (string) config('services.billing.webhook_secret'));

        abort_unless(hash_equals($expected, $signature), 401, 'Invalid billing webhook signature.');
    }

    private static function markInvoicePaid(PlatformRecord $invoice, PartnerStore $store, array $data, string $type): void
    {
        $payload = $invoice->payload ?? [];
        $payload['status'] = 'paid';
        $payload['paid_at'] = now()->toDateString();
        $payload['provider_reference'] = $data['payment_reference'] ?? $data['reference'] ?? null;
        $invoice->update(['status' => 'paid', 'payload' => $payload]);
        self::markBillingPayments($invoice->record_id, 'paid');

        $months = ($payload['action'] ?? '') === 'admin_plan_change' || ($payload['action'] ?? '') === 'upgraded' ? 1 : 1;
        $plan = self::plan((string) ($payload['plan'] ?? $store->plan));
        $store = SubscriptionLifecycle::renew($store, $plan['name'], $months);
        $store->forceFill([
            'payment_status' => 'paid',
            'metadata' => array_merge($store->metadata ?? [], [
                'last_payment_at' => now()->toIso8601String(),
                'last_billing_event' => $type,
            ]),
        ])->save();
        PlatformAudit::notify('billing_success', 'Subscription payment succeeded', 'Your subscription payment was completed.', ['store_id' => $store->store_id, 'severity' => 'success']);
    }

    private static function markInvoiceFailed(PlatformRecord $invoice, PartnerStore $store, array $data, string $type): void
    {
        $payload = $invoice->payload ?? [];
        $payload['status'] = 'failed';
        $payload['failed_at'] = now()->toIso8601String();
        $payload['failure_reason'] = $data['reason'] ?? 'payment_failed';
        $invoice->update(['status' => 'failed', 'payload' => $payload]);
        self::markBillingPayments($invoice->record_id, 'failed');
        $store->forceFill([
            'status' => 'past_due',
            'payment_status' => 'failed',
            'metadata' => array_merge($store->metadata ?? [], ['last_billing_event' => $type, 'billing_failed_at' => now()->toIso8601String()]),
        ])->save();
        SubscriptionLifecycle::syncSubscriptionRecord($store->fresh(), 'past_due');
        PlatformAudit::notify('billing_failed', 'Subscription payment failed', 'Update your payment method to keep the store active.', ['store_id' => $store->store_id, 'severity' => 'danger']);
    }

    private static function cancelStoreFromWebhook(PlatformRecord $invoice, PartnerStore $store, array $data, string $type): void
    {
        $store->forceFill([
            'status' => 'cancelled',
            'metadata' => array_merge($store->metadata ?? [], ['last_billing_event' => $type, 'cancelled_at' => now()->toIso8601String(), 'auto_renew' => false]),
        ])->save();
        SubscriptionLifecycle::syncSubscriptionRecord($store->fresh(), 'cancelled');
        PlatformAudit::notify('subscription_cancelled', 'Subscription cancelled', 'Your subscription was cancelled.', ['store_id' => $store->store_id, 'severity' => 'warning']);
    }

    private static function adminSummary(): array
    {
        PartnerTenantStore::partners();

        $stores = PartnerStore::query()->get();

        return [
            'total' => $stores->count(),
            'active' => $stores->filter(fn (PartnerStore $store) => self::subscriptionStatus($store) === 'active')->count(),
            'trial' => $stores->filter(fn (PartnerStore $store) => self::subscriptionStatus($store) === 'trial')->count(),
            'expired' => $stores->filter(fn (PartnerStore $store) => self::subscriptionStatus($store) === 'expired')->count(),
            'suspended' => $stores->filter(fn (PartnerStore $store) => self::subscriptionStatus($store) === 'suspended')->count(),
        ];
    }
}
