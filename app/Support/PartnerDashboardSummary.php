<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformNotification;
use App\Models\PlatformRecord;
use App\Models\StoreOnboardingStep;
use App\Models\StoreSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerDashboardSummary
{
    public static function forPartner(array $partner, array $user, ?Request $request = null): array
    {
        self::ensureStoreData($partner);

        $storeId = (string) $partner['store_id'];
        $periodDays = self::periodDays($request);
        $orders = self::filterByPeriod(self::records('orders', $storeId), $periodDays);
        $visits = self::filterByPeriod(self::records('visits', $storeId), $periodDays);
        $products = self::records('products', $storeId);
        $customers = self::records('customers', $storeId);
        $newCustomers = self::filterByPeriod($customers, $periodDays);
        $notifications = self::notifications($storeId);
        $activities = self::activities($storeId, 6);
        $setup = self::setupSteps($partner);
        $todayOrders = $orders->filter(fn (array $order) => self::isToday($order));
        $lowStock = $products->filter(fn (array $product) => self::stock($product) <= self::lowStockThreshold($product))->values();
        $pendingOrders = $orders->filter(fn (array $order) => self::isPendingOrder($order));
        $awaitingShipping = $orders->filter(fn (array $order) => self::isAwaitingShipping($order));
        $salesGoal = max(120, $orders->count() + 56);
        $salesGoalProgress = min(100, (int) round(($orders->count() / max(1, $salesGoal)) * 100));
        $kpis = [
            self::kpi('طلبات اليوم', (string) $todayOrders->count(), 'من قاعدة بيانات الطلبات', 'orders_today'),
            self::kpi('مبيعات اليوم', self::formatMoney($todayOrders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0))), 'طلبات اليوم المدفوعة', 'sales_today'),
            self::kpi('الزوار', (string) $visits->sum(fn (array $visit) => (int) ($visit['visitors'] ?? $visit['value'] ?? 0)), 'زيارات الفترة المحددة', 'visitors_total'),
            self::kpi('إجمالي المنتجات', (string) $products->count(), 'منتجات المتجر', 'products_total'),
            self::kpi('إجمالي العملاء', (string) $customers->count(), 'عملاء المتجر', 'customers_total'),
            self::kpi('العملاء الجدد', (string) $newCustomers->count(), 'عملاء الفترة المحددة', 'new_customers'),
            self::kpi('الطلبات المعلقة', (string) $pendingOrders->count(), 'تحتاج متابعة', 'pending_orders'),
            self::kpi('بانتظار الشحن', (string) $awaitingShipping->count(), 'طلبات تحتاج تسليم للناقل', 'awaiting_shipping'),
            self::kpi('منتجات منخفضة المخزون', (string) $lowStock->count(), 'أقل من حد التنبيه', 'low_stock'),
        ];
        $alerts = self::alerts($partner, $notifications, $pendingOrders, $lowStock);

        return [
            'apiUrl' => route('api.partner.dashboard.summary', ['period' => $periodDays]),
            'period' => [
                'days' => $periodDays,
                'options' => [7, 30, 90],
            ],
            'store' => [
                'id' => $storeId,
                'name' => $partner['name'] ?? '',
                'domain' => $partner['domain'] ?? '',
                'status' => $partner['status'] ?? 'active',
            ],
            'kpis' => $kpis,
            'featuredKpis' => collect($kpis)
                ->whereIn('key', ['orders_today', 'sales_today', 'products_total', 'customers_total', 'pending_orders', 'low_stock'])
                ->values()
                ->all(),
            'latestOrders' => $orders
                ->sortByDesc(fn (array $order) => self::dateValue($order)?->timestamp ?? 0)
                ->take(6)
                ->values()
                ->all(),
            'activities' => $activities,
            'alerts' => $alerts,
            'importantAlerts' => array_slice($alerts, 0, 2),
            'subscription' => self::subscription($partner),
            'setup' => $setup,
            'setupProgress' => self::setupProgress($setup),
            'storeHealth' => self::storeHealth($partner, $setup, $lowStock, $pendingOrders),
            'quickActions' => self::quickActions($partner, $user),
            'lowStock' => $lowStock->take(6)->values()->all(),
            'charts' => [
                'orders' => self::dailySeries($orders, $periodDays, 'count'),
                'sales' => self::dailySeries($orders, $periodDays, 'sales'),
            ],
            'goal' => [
                'label' => 'هدف الطلبات خلال الفترة',
                'current' => $orders->count(),
                'target' => $salesGoal,
                'progress' => $salesGoalProgress,
                'remaining' => max(0, $salesGoal - $orders->count()),
            ],
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'source_tables' => ['platform_records', 'store_settings', 'store_onboarding_steps', 'platform_notifications', 'platform_activity_logs'],
                'store_scoped' => true,
            ],
        ];
    }

    public static function activitiesForPartner(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $query = trim((string) $request->query('q', ''));
        $storeId = (string) $partner['store_id'];
        $activities = self::activities($storeId, 50);

        if ($query !== '') {
            $activities = $activities
                ->filter(fn (array $row) => Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), Str::lower($query)))
                ->values();
        }

        return [
            'title' => 'آخر النشاطات',
            'apiUrl' => route('partner.api.activities', $request->query()),
            'filters' => ['q' => $query],
            'rows' => $activities->values()->all(),
            'summary' => [
                'total' => $activities->count(),
                'store_id' => $storeId,
            ],
            'breadcrumbs' => [
                ['label' => 'لوحة التحكم', 'url' => route('partner.dashboard')],
                ['label' => 'آخر النشاطات', 'url' => null],
            ],
        ];
    }

    public static function notificationsForPartner(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $query = trim((string) $request->query('q', ''));
        $severity = trim((string) $request->query('severity', 'all'));
        $storeId = (string) $partner['store_id'];
        $notifications = self::notifications($storeId, 50);

        if ($query !== '') {
            $notifications = $notifications
                ->filter(fn (array $row) => Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), Str::lower($query)))
                ->values();
        }

        if ($severity !== '' && $severity !== 'all') {
            $notifications = $notifications
                ->filter(fn (array $row) => ($row['severity'] ?? 'info') === $severity)
                ->values();
        }

        return [
            'title' => 'الإشعارات',
            'apiUrl' => route('partner.api.notifications', $request->query()),
            'filters' => ['q' => $query, 'severity' => $severity],
            'severityOptions' => ['all' => 'كل التنبيهات', 'info' => 'معلومات', 'warning' => 'تحذير', 'danger' => 'مهم'],
            'rows' => $notifications->values()->all(),
            'summary' => [
                'total' => $notifications->count(),
                'unread' => $notifications->where('read', false)->count(),
                'store_id' => $storeId,
            ],
            'breadcrumbs' => [
                ['label' => 'لوحة التحكم', 'url' => route('partner.dashboard')],
                ['label' => 'الإشعارات', 'url' => null],
            ],
        ];
    }

    public static function ensureStoreData(array $partner): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        $storeId = (string) $partner['store_id'];

        self::ensureRecords($partner, 'orders', $partner['orders'] ?? [], fn (array $row, int $index) => [
            'record_id' => $row['id'] ?? 'order-' . $storeId . '-' . ($index + 1),
            'status' => $row['status'] ?? null,
            'payload' => [
                'order_number' => $row['id'] ?? 'ORD-' . ($index + 1),
                'customer' => $row['customer'] ?? '',
                'status' => $row['status'] ?? 'قيد المعالجة',
                'total' => $row['amount'] ?? $row['total'] ?? '0 SAR',
                'amount' => $row['amount'] ?? $row['total'] ?? '0 SAR',
                'created_at' => $row['date'] ?? now()->toDateString(),
                'date' => $row['date'] ?? now()->toDateString(),
                'payment_status' => $row['payment_status'] ?? 'مدفوع',
                'shipping_status' => $row['shipping_status'] ?? 'قيد التجهيز',
            ],
        ]);

        self::ensureRecords($partner, 'products', $partner['products'] ?? [], fn (array $row, int $index) => [
            'record_id' => $row['sku'] ?? $row['id'] ?? 'product-' . $storeId . '-' . ($index + 1),
            'status' => $row['status'] ?? null,
            'payload' => [
                'sku' => $row['sku'] ?? 'SKU-' . ($index + 1),
                'product' => $row['name'] ?? $row['product'] ?? '',
                'name' => $row['name'] ?? $row['product'] ?? '',
                'stock' => (string) ($row['stock'] ?? 0),
                'low_stock_threshold' => $row['low_stock_threshold'] ?? 12,
                'price' => $row['price'] ?? '0 SAR',
                'status' => $row['status'] ?? 'منشور',
            ],
        ]);

        self::ensureRecords($partner, 'customers', $partner['customers'] ?? [], fn (array $row, int $index) => [
            'record_id' => $row['email'] ?? $row['id'] ?? 'customer-' . $storeId . '-' . ($index + 1),
            'status' => $row['status'] ?? null,
            'payload' => [
                'customer' => $row['name'] ?? $row['customer'] ?? '',
                'name' => $row['name'] ?? $row['customer'] ?? '',
                'email' => $row['email'] ?? '',
                'orders' => (string) ($row['orders'] ?? 0),
                'total_spent' => $row['spent'] ?? $row['total_spent'] ?? '0 SAR',
                'status' => $row['status'] ?? 'نشط',
                'created_at' => $row['created_at'] ?? $row['date'] ?? now()->subDays($index)->toDateString(),
                'date' => $row['date'] ?? $row['created_at'] ?? now()->subDays($index)->toDateString(),
            ],
        ]);

        $visitors = (int) self::numericMetric($partner['metrics']['visitors'] ?? $partner['metrics']['customers'] ?? 0);
        $visitors = max($visitors, count($partner['customers'] ?? []) * 12);
        self::ensureRecords($partner, 'visits', collect(range(0, 13))->map(fn (int $index) => [
            'id' => 'visit-' . $storeId . '-' . $index,
            'date' => now()->subDays($index)->toDateString(),
            'visitors' => max(1, (int) floor($visitors / 14) + (($index % 4) * 3)),
        ])->all(), fn (array $row) => [
            'record_id' => $row['id'],
            'status' => 'tracked',
            'payload' => [
                'date' => $row['date'],
                'created_at' => $row['date'],
                'visitors' => $row['visitors'],
                'value' => $row['visitors'],
                'source' => 'storefront',
            ],
        ]);

        self::ensureRecords($partner, 'payments', $partner['payments'] ?? [], fn (array $row, int $index) => [
            'record_id' => $row['id'] ?? 'payment-' . $storeId . '-' . ($index + 1),
            'status' => $row['status'] ?? null,
            'payload' => [
                'gateway' => $row['gateway'] ?? '',
                'status' => $row['status'] ?? '',
                'amount' => $row['amount'] ?? '0 SAR',
                'settlement' => $row['settlement'] ?? '',
            ],
        ]);

        self::ensureStoreSettings($partner);
        self::ensureOnboarding($partner);
        self::ensureNotifications($partner);
        self::ensureActivities($partner);
    }

    private static function ensureRecords(array $partner, string $section, array $rows, callable $mapper): void
    {
        $storeId = (string) $partner['store_id'];

        if (PlatformRecord::query()->where('section', $section)->where('store_id', $storeId)->exists()) {
            return;
        }

        foreach (array_values($rows) as $index => $row) {
            $mapped = $mapper($row, $index);
            $recordId = (string) $mapped['record_id'];

            PlatformRecord::query()->updateOrCreate(
                ['section' => $section, 'record_id' => $recordId],
                [
                    'store_id' => $storeId,
                    'partner_id' => $partner['id'] ?? null,
                    'status' => $mapped['status'] ?? null,
                    'payload' => array_merge($mapped['payload'], [
                        'id' => $recordId,
                        'store_id' => $storeId,
                        'store' => $partner['name'] ?? '',
                    ]),
                ],
            );
        }
    }

    private static function ensureStoreSettings(array $partner): void
    {
        if (! Schema::hasTable('store_settings')) {
            return;
        }

        StoreSetting::query()->firstOrCreate(
            ['store_id' => $partner['store_id']],
            [
                'identity' => [
                    'name' => $partner['name'] ?? '',
                    'owner' => $partner['owner'] ?? '',
                    'email' => $partner['email'] ?? '',
                    'domain' => $partner['domain'] ?? '',
                ],
                'payments' => ['provider' => $partner['payment_provider'] ?? '', 'status' => $partner['payment_status'] ?? ''],
                'shipping' => ['provider' => $partner['shipping_provider'] ?? ''],
                'branding' => ['logo' => $partner['logo'] ?? 'solve-logo.png'],
            ],
        );
    }

    private static function ensureOnboarding(array $partner): void
    {
        if (! Schema::hasTable('store_onboarding_steps')) {
            return;
        }

        $steps = [
            ['store-profile', 'بيانات المتجر', true],
            ['payments-shipping', 'الدفع والشحن', ! empty($partner['payment_provider']) && ! empty($partner['shipping_provider'])],
            ['domain', 'الدومين', ! empty($partner['domain'])],
            ['first-products', 'إضافة المنتجات', ! empty($partner['products'])],
            ['team-permissions', 'الموظفون والصلاحيات', ! empty($partner['users'])],
        ];

        foreach ($steps as [$key, $title, $done]) {
            StoreOnboardingStep::query()->firstOrCreate(
                ['store_id' => $partner['store_id'], 'step_key' => $key],
                [
                    'title' => $title,
                    'status' => $done ? 'completed' : 'pending',
                    'completed_at' => $done ? now() : null,
                    'payload' => ['source' => 'partner_dashboard_phase_1'],
                ],
            );
        }
    }

    private static function ensureNotifications(array $partner): void
    {
        if (! Schema::hasTable('platform_notifications')) {
            return;
        }

        foreach (($partner['alerts'] ?? []) as $index => $alert) {
            $exists = PlatformNotification::query()
                ->where('store_id', $partner['store_id'])
                ->where('type', 'partner_dashboard')
                ->where('title', $alert['title'] ?? 'تنبيه')
                ->exists();

            if ($exists) {
                continue;
            }

            PlatformNotification::query()->create([
                'type' => 'partner_dashboard',
                'title' => $alert['title'] ?? 'تنبيه',
                'body' => $alert['body'] ?? '',
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'severity' => $alert['tone'] ?? ($index === 0 ? 'info' : 'warning'),
                'payload' => ['source' => 'partner_dashboard_phase_1'],
            ]);
        }
    }

    private static function records(string $section, string $storeId): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $storeId)
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => array_merge($record->payload ?? [], [
                'id' => $record->record_id,
                'store_id' => $record->store_id,
                'status' => $record->status ?? ($record->payload['status'] ?? null),
                'db_created_at' => $record->created_at?->toDateString(),
                'db_updated_at' => $record->updated_at?->toIso8601String(),
            ]));
    }

    private static function notifications(string $storeId, int $limit = 8): Collection
    {
        if (! Schema::hasTable('platform_notifications')) {
            return collect();
        }

        return PlatformNotification::query()
            ->where('store_id', $storeId)
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn (PlatformNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'body' => $notification->body,
                'severity' => $notification->severity,
                'url' => $notification->url,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->diffForHumans(),
                'created_at_iso' => $notification->created_at?->toIso8601String(),
            ]);
    }

    private static function activities(string $storeId, int $limit = 8): Collection
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return collect();
        }

        return PlatformActivityLog::query()
            ->where('store_id', $storeId)
            ->latest()
            ->take($limit)
            ->get()
            ->map(fn (PlatformActivityLog $activity) => [
                'id' => $activity->id,
                'actor' => $activity->actor_name ?: 'النظام',
                'role' => $activity->role ?: 'system',
                'action' => $activity->action,
                'subject_type' => $activity->subject_type,
                'subject_id' => $activity->subject_id,
                'store_id' => $activity->store_id,
                'properties' => $activity->properties ?? [],
                'created_at' => $activity->created_at?->diffForHumans(),
                'created_at_iso' => $activity->created_at?->toIso8601String(),
            ]);
    }

    private static function setupSteps(array $partner): array
    {
        if (! Schema::hasTable('store_onboarding_steps')) {
            return [];
        }

        return StoreOnboardingStep::query()
            ->where('store_id', $partner['store_id'])
            ->orderBy('id')
            ->get()
            ->map(fn (StoreOnboardingStep $step) => [
                'key' => $step->step_key,
                'label' => $step->title,
                'done' => $step->status === 'completed',
                'status' => $step->status,
                'completed_at' => $step->completed_at?->toDateString(),
            ])
            ->all();
    }

    private static function alerts(array $partner, Collection $notifications, Collection $pendingOrders, Collection $lowStock): array
    {
        $alerts = $notifications->values()->all();

        if ($pendingOrders->isNotEmpty()) {
            $alerts[] = [
                'title' => 'طلبات معلقة تحتاج متابعة',
                'body' => 'يوجد ' . $pendingOrders->count() . ' طلب لم يكتمل بعد داخل المتجر.',
                'severity' => 'warning',
                'url' => route('partner.orders'),
            ];
        }

        if ($lowStock->isNotEmpty()) {
            $alerts[] = [
                'title' => 'منتجات منخفضة المخزون',
                'body' => 'يوجد ' . $lowStock->count() . ' منتج وصل إلى حد التنبيه.',
                'severity' => 'danger',
                'url' => route('partner.products'),
            ];
        }

        return array_slice($alerts, 0, 6);
    }

    private static function ensureActivities(array $partner): void
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return;
        }

        $storeId = (string) $partner['store_id'];

        if (PlatformActivityLog::query()->where('store_id', $storeId)->exists()) {
            return;
        }

        foreach (['orders' => 'order_synced', 'products' => 'product_synced', 'customers' => 'customer_synced'] as $section => $action) {
            PlatformRecord::query()
                ->where('section', $section)
                ->where('store_id', $storeId)
                ->latest()
                ->take(3)
                ->get()
                ->each(function (PlatformRecord $record) use ($partner, $action, $section): void {
                    PlatformActivityLog::query()->create([
                        'actor_type' => 'system',
                        'actor_name' => 'Solve',
                        'role' => 'system',
                        'store_id' => $record->store_id,
                        'partner_id' => $partner['id'] ?? null,
                        'action' => $action,
                        'subject_type' => $section,
                        'subject_id' => $record->record_id,
                        'properties' => [
                            'source' => 'dashboard_sync',
                            'status' => $record->status,
                        ],
                    ]);
                });
        }
    }

    private static function subscription(array $partner): array
    {
        return [
            'plan' => $partner['plan'] ?? 'Starter',
            'status' => $partner['payment_status'] ?? 'غير محدد',
            'renewal_at' => $partner['renewal_at'] ?? null,
            'started_at' => $partner['subscription_at'] ?? null,
        ];
    }

    private static function storeHealth(array $partner, array $setup, Collection $lowStock, Collection $pendingOrders): array
    {
        $setupProgress = self::setupProgress($setup);
        $score = $setupProgress;

        if ($lowStock->isNotEmpty()) {
            $score -= min(20, $lowStock->count() * 4);
        }

        if ($pendingOrders->isNotEmpty()) {
            $score -= min(20, $pendingOrders->count() * 3);
        }

        if (Str::contains(Str::lower((string) ($partner['status'] ?? '')), ['suspend', 'pause', 'موقوف'])) {
            $score = min($score, 45);
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'label' => match (true) {
                $score >= 85 => 'ممتاز',
                $score >= 65 => 'جيد',
                $score >= 45 => 'يحتاج متابعة',
                default => 'حرج',
            },
            'checks' => [
                ['label' => 'إعداد المتجر', 'ok' => $setupProgress >= 80],
                ['label' => 'المخزون', 'ok' => $lowStock->isEmpty()],
                ['label' => 'الطلبات', 'ok' => $pendingOrders->isEmpty()],
                ['label' => 'حالة المتجر', 'ok' => ! Str::contains(Str::lower((string) ($partner['status'] ?? '')), ['suspend', 'pause', 'موقوف'])],
            ],
        ];
    }

    private static function quickActions(array $partner, array $user): array
    {
        $actions = [
            ['label' => 'إنشاء طلب يدوي', 'route' => 'partner.orders.manual', 'params' => [], 'ability' => 'view-orders'],
            ['label' => 'إدارة المنتجات', 'route' => 'partner.products', 'params' => [], 'ability' => 'view-products'],
            ['label' => 'إعدادات المتجر', 'route' => 'partner.settings', 'params' => [], 'ability' => 'manage-settings'],
            ['label' => 'عرض العملاء', 'route' => 'partner.customers', 'params' => [], 'ability' => 'view-customers'],
        ];

        return collect($actions)
            ->filter(fn (array $action) => PartnerTenantStore::can($user, $action['ability']))
            ->map(fn (array $action) => [
                'label' => $action['label'],
                'url' => route($action['route'], $action['params']),
            ])
            ->values()
            ->all();
    }

    private static function setupProgress(array $steps): int
    {
        if ($steps === []) {
            return 0;
        }

        return (int) round((collect($steps)->where('done', true)->count() / count($steps)) * 100);
    }

    private static function kpi(string $label, string $value, string $hint, string $key): array
    {
        return compact('label', 'value', 'hint', 'key');
    }

    private static function periodDays(?Request $request): int
    {
        $days = (int) ($request?->query('period', 90) ?? 90);

        return in_array($days, [7, 30, 90], true) ? $days : 90;
    }

    private static function filterByPeriod(Collection $records, int $periodDays): Collection
    {
        $start = now()->subDays($periodDays - 1)->startOfDay();

        return $records
            ->filter(fn (array $record) => ($date = self::dateValue($record)) ? $date->greaterThanOrEqualTo($start) : true)
            ->values();
    }

    private static function dailySeries(Collection $orders, int $periodDays, string $mode): array
    {
        $start = now()->subDays($periodDays - 1)->startOfDay();

        return collect(range(0, $periodDays - 1))
            ->map(function (int $offset) use ($start, $orders, $mode) {
                $date = $start->copy()->addDays($offset);
                $dayOrders = $orders->filter(fn (array $order) => self::dateValue($order)?->isSameDay($date));

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('m/d'),
                    'value' => $mode === 'sales'
                        ? (float) $dayOrders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0))
                        : $dayOrders->count(),
                ];
            })
            ->values()
            ->all();
    }

    private static function isToday(array $record): bool
    {
        foreach (['created_at', 'date', 'db_created_at'] as $key) {
            if (empty($record[$key])) {
                continue;
            }

            try {
                if (Carbon::parse($record[$key])->isSameDay(now())) {
                    return true;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return false;
    }

    private static function dateValue(array $record): ?Carbon
    {
        foreach (['created_at', 'date', 'db_created_at'] as $key) {
            if (empty($record[$key])) {
                continue;
            }

            try {
                return Carbon::parse($record[$key]);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private static function isPendingOrder(array $order): bool
    {
        $status = Str::lower((string) ($order['status'] ?? ''));

        return ! Str::contains($status, ['مكتمل', 'تم التسليم', 'delivered', 'complete', 'completed', 'paid', 'ناجح']);
    }

    private static function isAwaitingShipping(array $order): bool
    {
        $shippingStatus = Str::lower((string) ($order['shipping_status'] ?? $order['status'] ?? ''));

        return Str::contains($shippingStatus, [
            'قيد الشحن',
            'قيد التجهيز',
            'بانتظار',
            'ready',
            'pending',
            'processing',
            'awaiting',
        ]) && ! Str::contains($shippingStatus, ['تم التسليم', 'delivered', 'cancelled', 'canceled']);
    }

    private static function stock(array $product): int
    {
        return (int) preg_replace('/[^\d-]/', '', (string) ($product['stock'] ?? 0));
    }

    private static function lowStockThreshold(array $product): int
    {
        return max(1, (int) ($product['low_stock_threshold'] ?? 12));
    }

    private static function money(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private static function numericMetric(mixed $value): float
    {
        return self::money($value);
    }

    private static function formatMoney(float $amount): string
    {
        return number_format($amount) . ' ر.س';
    }
}
