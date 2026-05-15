<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformRecord;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdminExecutiveExperience
{
    public static function dashboard(): array
    {
        $stores = collect(PartnerTenantStore::allPartners());
        $orders = self::records('orders');
        $subscriptions = self::records('subscriptions')->merge(self::records('subscription'));
        $invoices = self::records('subscription_invoices');
        $payments = self::records('subscription_payments')->merge(self::records('payments'));
        $aiUsage = self::records('solve_ai_usage');
        $activities = self::activities();
        $alerts = self::alerts($stores, $payments, $aiUsage, $activities);
        $healthStores = self::storeHealth($stores, $orders, $payments, $aiUsage);
        $revenueToday = self::sumMoney($orders, ['total', 'grand_total']) + self::sumMoney($payments, ['amount', 'paid_amount']);
        $failedPayments = $payments->filter(fn (array $row) => self::isFailed($row))->count()
            + $invoices->filter(fn (array $row) => self::isFailed($row))->count();

        return [
            'kpis' => [
                ['key' => 'revenue_today', 'label' => 'Revenue Today', 'value' => number_format($revenueToday) . ' ر.س', 'hint' => 'من الطلبات والمدفوعات المسجلة', 'tone' => 'emerald'],
                ['key' => 'active_merchants', 'label' => 'Active Merchants', 'value' => (string) $stores->filter(fn (array $store) => self::isActiveStore($store))->count(), 'hint' => 'متاجر جاهزة للعمل', 'tone' => 'blue'],
                ['key' => 'subscription_growth', 'label' => 'Subscription Growth', 'value' => self::subscriptionGrowth($stores, $subscriptions), 'hint' => 'Paid vs Free/Trial', 'tone' => 'violet'],
                ['key' => 'ai_usage', 'label' => 'AI Usage', 'value' => (string) $aiUsage->count(), 'hint' => $aiUsage->sum(fn (array $row) => (int) ($row['tokens'] ?? 0)) . ' tokens', 'tone' => 'cyan'],
                ['key' => 'failed_payments', 'label' => 'Failed Payments', 'value' => (string) $failedPayments, 'hint' => 'تحتاج متابعة Billing', 'tone' => $failedPayments ? 'rose' : 'slate'],
                ['key' => 'critical_alerts', 'label' => 'Critical Alerts', 'value' => (string) collect($alerts)->where('priority', 'critical')->count(), 'hint' => 'أولوية عالية', 'tone' => 'amber'],
                ['key' => 'system_health', 'label' => 'System Health Score', 'value' => self::systemHealth($alerts, $healthStores) . '%', 'hint' => 'أداء المنصة اليوم', 'tone' => 'slate'],
            ],
            'alerts' => $alerts,
            'feed' => $activities->take(18)->values()->all(),
            'insights' => self::insights($stores, $orders, $payments, $aiUsage, $healthStores),
            'health_stores' => $healthStores->take(10)->values()->all(),
            'commands' => self::commands($stores),
            'search_examples' => ['store-atlas', 'merchant@atlas.sa', 'ORD', 'subscription', 'invoice', 'AI'],
        ];
    }

    public static function search(string $query): array
    {
        $needle = Str::lower(trim($query));
        if ($needle === '') {
            return ['query' => $query, 'results' => []];
        }

        $stores = collect(PartnerTenantStore::allPartners())->map(fn (array $store) => [
            'type' => 'store',
            'title' => $store['name'] ?? $store['store_id'],
            'subtitle' => ($store['owner'] ?? '') . ' · ' . ($store['plan'] ?? ''),
            'url' => route('admin.partners.show', ['partner' => $store['store_id'] ?? $store['id']]),
            'payload' => $store,
        ]);

        $records = collect(['orders', 'subscriptions', 'subscription_invoices', 'subscription_payments', 'apps', 'api_keys'])
            ->flatMap(fn (string $section) => self::records($section)->map(fn (array $record) => [
                'type' => $section,
                'title' => (string) ($record['id'] ?? $record['record_id'] ?? $record['invoice_number'] ?? $record['order_number'] ?? $section),
                'subtitle' => (string) (($record['store_id'] ?? '') . ' · ' . ($record['status'] ?? $record['invoice_status'] ?? '')),
                'url' => self::urlForSearchResult($section, $record),
                'payload' => $record,
            ]));

        $results = $stores->merge($records)
            ->filter(function (array $row) use ($needle) {
                $haystack = Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE));

                return Str::contains($haystack, $needle);
            })
            ->take(20)
            ->values()
            ->all();

        return ['query' => $query, 'results' => $results];
    }

    public static function updateAlert(string $alertId, string $action, ?string $assignee = null): array
    {
        abort_unless(in_array($action, ['resolve', 'ignore', 'assign'], true), 422, 'Invalid alert action.');

        if (Schema::hasTable('platform_notifications')) {
            $notification = PlatformNotification::query()
                ->where(function ($query) use ($alertId) {
                    $query->where('id', $alertId)->orWhere('payload->alert_id', $alertId);
                })
                ->first();

            if ($notification) {
                $payload = $notification->payload ?? [];
                $payload['ops_status'] = $action;
                $payload['assigned_to'] = $assignee;
                $payload['handled_at'] = now()->toDateTimeString();
                $notification->update([
                    'read_at' => $action === 'resolve' || $action === 'ignore' ? now() : $notification->read_at,
                    'payload' => $payload,
                ]);
            }
        }

        PlatformAudit::activity('executive.alert.' . $action, 'smart_alert', $alertId, [
            'assigned_to' => $assignee,
        ], request());

        return ['alert_id' => $alertId, 'action' => $action, 'assigned_to' => $assignee, 'updated' => true];
    }

    public static function executeCommand(string $command, array $payload = []): array
    {
        $storeId = (string) ($payload['store_id'] ?? '');
        $result = ['command' => $command, 'store_id' => $storeId, 'executed' => true];

        if ($command === 'suspend_store' || $command === 'activate_store') {
            abort_if($storeId === '', 422, 'store_id is required.');
            $status = $command === 'suspend_store' ? 'موقوف' : 'نشط';

            if (Schema::hasTable('partner_stores')) {
                PartnerStore::query()->where('store_id', $storeId)->update(['status' => $status]);
            }

            self::updateAdminStoreRecord($storeId, ['status' => $status]);
            $result['status'] = $status;
        } elseif ($command === 'upgrade_plan') {
            abort_if($storeId === '', 422, 'store_id is required.');
            $plan = (string) ($payload['plan'] ?? 'Enterprise');

            if (Schema::hasTable('partner_stores')) {
                PartnerStore::query()->where('store_id', $storeId)->update(['plan' => $plan]);
            }

            self::updateAdminStoreRecord($storeId, ['plan' => $plan]);
            $result['plan'] = $plan;
        } elseif ($command === 'resend_invoice') {
            abort_if((string) ($payload['invoice_id'] ?? '') === '', 422, 'invoice_id is required.');
            $result['invoice_id'] = $payload['invoice_id'];
        } elseif ($command === 'enable_feature') {
            abort_if($storeId === '' || (string) ($payload['feature'] ?? '') === '', 422, 'store_id and feature are required.');
            $result['feature'] = $payload['feature'];
        } elseif ($command === 'open_logs') {
            $result['url'] = route('admin.activity');
        } else {
            abort(422, 'Unknown command.');
        }

        PlatformAudit::activity('executive.command.' . $command, 'command_center', $storeId ?: null, $result, request());

        return $result;
    }

    public static function feed(int $limit = 30): array
    {
        return self::activities()->take($limit)->values()->all();
    }

    private static function alerts(Collection $stores, Collection $payments, Collection $aiUsage, Collection $activities): array
    {
        $alerts = [];

        $suspended = $stores->filter(fn (array $store) => ! self::isActiveStore($store));
        foreach ($suspended->take(4) as $store) {
            $alerts[] = self::alert('store_suspended_' . ($store['store_id'] ?? Str::slug($store['name'] ?? 'store')), 'critical', 'متجر متوقف', ($store['name'] ?? $store['store_id']) . ' يحتاج مراجعة حالة التشغيل.', $store['store_id'] ?? null, 'admin.partners.show');
        }

        $failedPayments = $payments->filter(fn (array $row) => self::isFailed($row));
        if ($failedPayments->count() >= 2) {
            $alerts[] = self::alert('payment_failure_spike', 'critical', 'فشل دفع جماعي', $failedPayments->count() . ' عمليات دفع أو فواتير فاشلة تحتاج Retry.', null, 'admin.billing');
        }

        $recentErrors = $activities->filter(fn (array $row) => Str::contains(Str::lower((string) ($row['action'] ?? '')), ['error', 'failed', 'denied']));
        if ($recentErrors->count() >= 3) {
            $alerts[] = self::alert('error_rate_high', 'high', 'ارتفاع أخطاء النظام', $recentErrors->count() . ' أحداث خطأ/رفض وصول مسجلة مؤخراً.', null, 'admin.activity');
        }

        $aiByStore = $aiUsage->groupBy('store_id')->map->count()->sortDesc();
        $topAi = $aiByStore->first();
        if ($topAi && $topAi >= 10) {
            $alerts[] = self::alert('ai_usage_spike_' . $aiByStore->keys()->first(), 'medium', 'استهلاك AI مرتفع', 'المتجر ' . $aiByStore->keys()->first() . ' استخدم ' . $topAi . ' طلب AI هذا الشهر.', $aiByStore->keys()->first(), 'admin.solve-ai.usage');
        }

        if (Schema::hasTable('platform_notifications')) {
            foreach (PlatformNotification::query()->whereNull('read_at')->latest()->take(6)->get() as $notification) {
                $alerts[] = [
                    'id' => (string) ($notification->payload['alert_id'] ?? $notification->id),
                    'priority' => self::priority($notification->severity ?? 'medium'),
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'store_id' => $notification->store_id,
                    'url' => $notification->url ?: route('admin.notifications'),
                    'status' => $notification->payload['ops_status'] ?? 'open',
                ];
            }
        }

        return collect($alerts)->unique('id')->sortBy(fn (array $alert) => ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3][$alert['priority']] ?? 9)->values()->all();
    }

    private static function storeHealth(Collection $stores, Collection $orders, Collection $payments, Collection $aiUsage): Collection
    {
        return $stores->map(function (array $store) use ($orders, $payments, $aiUsage) {
            $storeId = $store['store_id'] ?? '';
            $storeOrders = $orders->where('store_id', $storeId)->count() ?: count($store['orders'] ?? []);
            $storePayments = $payments->where('store_id', $storeId);
            $failedPayments = $storePayments->filter(fn (array $row) => self::isFailed($row))->count();
            $aiCount = $aiUsage->where('store_id', $storeId)->count();
            $score = 45;
            $score += self::isActiveStore($store) ? 20 : -20;
            $score += min(20, $storeOrders * 4);
            $score += min(10, $aiCount);
            $score -= min(20, $failedPayments * 10);
            $score += in_array($store['plan'] ?? '', ['Growth', 'Pro', 'Enterprise', 'Enterprise Plus'], true) ? 10 : 0;
            $score = max(0, min(100, $score));

            return [
                'store_id' => $storeId,
                'name' => $store['name'] ?? $storeId,
                'plan' => $store['plan'] ?? '-',
                'status' => $store['status'] ?? '-',
                'score' => $score,
                'orders' => $storeOrders,
                'ai_usage' => $aiCount,
                'failed_payments' => $failedPayments,
                'recommendation' => self::healthRecommendation($score, $failedPayments, $aiCount),
                'url' => route('admin.partners.show', ['partner' => $storeId ?: ($store['id'] ?? '')]),
            ];
        })->sortBy('score');
    }

    private static function insights(Collection $stores, Collection $orders, Collection $payments, Collection $aiUsage, Collection $healthStores): array
    {
        $paidStores = $stores->filter(fn (array $store) => ! in_array($store['plan'] ?? 'Free', ['Free', 'Trial'], true))->count();
        $freeStores = max(0, $stores->count() - $paidStores);
        $aiStores = $aiUsage->pluck('store_id')->filter()->unique()->count();
        $ordersStores = $orders->pluck('store_id')->filter()->unique()->count();
        $lowHealth = $healthStores->where('score', '<', 60)->count();

        return [
            ['title' => 'AI vs Sales', 'body' => $aiStores . ' متاجر تستخدم AI، و' . $ordersStores . ' متاجر لديها طلبات. راقب المتاجر التي تستخدم AI بدون نمو في الطلبات.', 'action' => 'راجع توصيات ذكاء Solve', 'url' => route('admin.solve-ai.usage')],
            ['title' => 'Free to Paid', 'body' => $freeStores . ' حساب Free/Trial مقابل ' . $paidStores . ' حساب مدفوع. أفضل قرار: حملة Upgrade للمتاجر التي أضافت منتجات ولم ترق بعد.', 'action' => 'افتح الاشتراكات', 'url' => route('admin.subscriptions')],
            ['title' => 'Merchant Health', 'body' => $lowHealth . ' متاجر تحت Score 60. تحتاج تدخل Customer Success قبل Churn.', 'action' => 'راجع Health Score', 'url' => route('admin.partners')],
            ['title' => 'Billing Risk', 'body' => $payments->filter(fn (array $row) => self::isFailed($row))->count() . ' عمليات دفع فاشلة. نفذ Retry أو تواصل مع التاجر.', 'action' => 'مركز الفوترة', 'url' => route('admin.billing')],
        ];
    }

    private static function commands(Collection $stores): array
    {
        $firstStore = $stores->first();
        $storeId = $firstStore['store_id'] ?? '';

        return [
            ['key' => 'suspend_store', 'label' => 'إيقاف متجر', 'payload' => ['store_id' => $storeId], 'tone' => 'danger'],
            ['key' => 'activate_store', 'label' => 'تفعيل متجر', 'payload' => ['store_id' => $storeId], 'tone' => 'success'],
            ['key' => 'upgrade_plan', 'label' => 'ترقية باقة', 'payload' => ['store_id' => $storeId, 'plan' => 'Enterprise'], 'tone' => 'primary'],
            ['key' => 'resend_invoice', 'label' => 'إعادة إرسال فاتورة', 'payload' => ['invoice_id' => 'latest'], 'tone' => 'neutral'],
            ['key' => 'enable_feature', 'label' => 'تفعيل Feature', 'payload' => ['store_id' => $storeId, 'feature' => 'advanced_analytics'], 'tone' => 'primary'],
            ['key' => 'open_logs', 'label' => 'مراقبة Logs', 'payload' => [], 'tone' => 'neutral'],
        ];
    }

    private static function activities(): Collection
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return collect();
        }

        $logs = PlatformActivityLog::query()->latest()->limit(80)->get();

        return collect($logs->map(fn (PlatformActivityLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'store_id' => $log->store_id,
            'actor' => $log->actor_name ?: $log->actor_id ?: $log->actor_type,
            'created_at' => $log->created_at?->diffForHumans(),
            'priority' => Str::contains($log->action, ['failed', 'denied', 'error']) ? 'high' : 'normal',
        ])->all());
    }

    private static function records(string $section): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        $records = PlatformRecord::query()
            ->where('section', $section)
            ->latest()
            ->get();

        return collect($records->map(fn (PlatformRecord $record) => array_merge($record->payload ?? [], [
                'id' => $record->record_id,
                'record_id' => $record->record_id,
                'store_id' => $record->store_id ?? ($record->payload['store_id'] ?? null),
                'status' => $record->status ?? ($record->payload['status'] ?? null),
            ]))->all());
    }

    private static function alert(string $id, string $priority, string $title, string $body, ?string $storeId, string $route): array
    {
        return [
            'id' => $id,
            'priority' => $priority,
            'title' => $title,
            'body' => $body,
            'store_id' => $storeId,
            'url' => Route($route, $route === 'admin.partners.show' && $storeId ? ['partner' => $storeId] : []),
            'status' => 'open',
        ];
    }

    private static function sumMoney(Collection $rows, array $keys): float
    {
        return $rows->sum(function (array $row) use ($keys) {
            foreach ($keys as $key) {
                if (isset($row[$key])) {
                    return (float) filter_var((string) $row[$key], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }
            }

            return 0;
        });
    }

    private static function isActiveStore(array $store): bool
    {
        return in_array($store['status'] ?? '', ['نشط', 'active', 'Active'], true);
    }

    private static function isFailed(array $row): bool
    {
        $status = Str::lower((string) ($row['status'] ?? $row['invoice_status'] ?? $row['payment_status'] ?? ''));

        return Str::contains($status, ['fail', 'failed', 'past_due', 'متأخرة', 'فاشلة', 'مرفوض']);
    }

    private static function subscriptionGrowth(Collection $stores, Collection $subscriptions): string
    {
        $paid = $stores->filter(fn (array $store) => ! in_array($store['plan'] ?? 'Free', ['Free', 'Trial'], true))->count();
        $activeSubscriptions = $subscriptions->filter(fn (array $row) => in_array($row['status'] ?? '', ['active', 'Active', 'نشط'], true))->count();

        return '+' . max($paid, $activeSubscriptions);
    }

    private static function systemHealth(array $alerts, Collection $healthStores): int
    {
        $critical = collect($alerts)->where('priority', 'critical')->count();
        $high = collect($alerts)->where('priority', 'high')->count();
        $avgStore = $healthStores->count() ? (int) round($healthStores->avg('score')) : 85;

        return max(0, min(100, $avgStore - ($critical * 10) - ($high * 5)));
    }

    private static function priority(string $severity): string
    {
        return match (Str::lower($severity)) {
            'critical', 'danger', 'error' => 'critical',
            'high', 'warning' => 'high',
            'low', 'info' => 'low',
            default => 'medium',
        };
    }

    private static function healthRecommendation(int $score, int $failedPayments, int $aiCount): string
    {
        if ($failedPayments > 0) {
            return 'ابدأ بفحص الفوترة وإعادة محاولة الدفع.';
        }

        if ($score < 60) {
            return 'خطط لتواصل Customer Success وتحسين التفعيل.';
        }

        if ($aiCount === 0) {
            return 'اقترح تفعيل ذكاء Solve لتحسين المنتجات والحملات.';
        }

        return 'المتجر مستقر. راقب فرص الترقية والنمو.';
    }

    private static function urlForSearchResult(string $section, array $record): string
    {
        return match ($section) {
            'orders' => route('admin.orders'),
            'subscriptions' => route('admin.subscriptions'),
            'subscription_invoices', 'subscription_payments' => route('admin.billing'),
            'apps' => route('admin.apps'),
            default => route('admin.dashboard'),
        };
    }

    private static function updateAdminStoreRecord(string $storeId, array $patch): void
    {
        $stores = AdminSectionStore::get('stores', []);
        foreach ($stores as &$store) {
            if (($store['store_id'] ?? $store['id'] ?? null) === $storeId) {
                $store = array_merge($store, $patch, ['updated_at_human' => 'Just now']);
            }
        }
        unset($store);

        if ($stores !== []) {
            AdminSectionStore::put('stores', $stores);
        }
    }
}
