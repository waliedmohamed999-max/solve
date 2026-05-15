<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerApps
{
    public const STATUSES = [
        'installed' => 'مثبت',
        'not_installed' => 'غير مثبت',
        'needs_setup' => 'يحتاج إعداد',
        'disabled' => 'معطل',
        'admin_paused' => 'موقوف من الأدمن',
    ];

    public const CATEGORIES = [
        'payment' => 'الدفع',
        'shipping' => 'الشحن',
        'marketing' => 'التسويق',
        'analytics' => 'التحليلات',
        'accounting' => 'المحاسبة',
        'support' => 'الدعم',
        'ai' => 'الذكاء الاصطناعي',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerOrders::ensureStoreData($partner);
        PartnerProducts::ensureStoreData($partner);
        PartnerCustomers::ensureStoreData($partner);
        PartnerMarketing::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureCatalog($partner);
        self::ensureAutomations($partner);
        self::ensureAi($partner);
    }

    public static function summary(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);
        $apps = self::filtered(self::records($partner, 'partner_apps'), $request);
        $installed = $apps->whereIn('status_key', ['installed', 'needs_setup', 'disabled']);

        return [
            'store_id' => $partner['store_id'],
            'plan' => $partner['plan'] ?? 'Starter',
            'apps' => $apps->values()->all(),
            'installed' => $installed->values()->all(),
            'suggested' => $apps->where('status_key', 'not_installed')->take(6)->values()->all(),
            'counts' => [
                'total' => $apps->count(),
                'installed' => $installed->count(),
                'needs_setup' => $apps->where('status_key', 'needs_setup')->count(),
                'admin_paused' => $apps->where('status_key', 'admin_paused')->count(),
            ],
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'category' => trim((string) $request->query('category', 'all')),
                'status' => trim((string) $request->query('status', 'all')),
            ],
            'categories' => ['all' => 'كل التصنيفات'] + self::CATEGORIES,
            'statusOptions' => ['all' => 'كل الحالات'] + self::STATUSES,
        ];
    }

    public static function marketplace(array $partner, Request $request): array
    {
        $payload = self::summary($partner, $request);
        $payload['apps'] = collect($payload['apps'])->groupBy('category_key')->map(fn (Collection $items, string $key) => [
            'key' => $key,
            'label' => self::CATEGORIES[$key] ?? $key,
            'items' => $items->values()->all(),
        ])->values()->all();

        return $payload;
    }

    public static function installed(array $partner, Request $request): array
    {
        $payload = self::summary($partner, $request);
        $payload['apps'] = collect($payload['apps'])
            ->whereIn('status_key', ['installed', 'needs_setup', 'disabled'])
            ->values()
            ->all();

        return $payload;
    }

    public static function app(array $partner, string $id): array
    {
        self::ensureStoreData($partner);
        $app = self::normalize(self::recordForStore($partner, 'partner_apps', $id));

        return [
            'store_id' => $partner['store_id'],
            'app' => $app,
            'settings' => self::settings($partner, $id)['settings'],
            'logs' => self::logs($partner, $id)['logs'],
        ];
    }

    public static function install(array $partner, string $id, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'partner_apps', $id);
        $payload = $record->payload ?? [];
        abort_if(($payload['status_key'] ?? '') === 'admin_paused', 403, 'App is paused by admin.');
        abort_if(! self::isPlanAvailable($partner, $payload['plan'] ?? 'Starter'), 403, 'App is not available for this plan.');
        if (! in_array($payload['status_key'] ?? 'not_installed', ['installed', 'needs_setup'], true) && SubscriptionManager::limitReached($partner, 'apps')) {
            SubscriptionManager::recordUsageDenied($partner, $actor, 'apps');
            abort(402, 'App limit reached for the current subscription plan.');
        }
        $payload['status_key'] = empty($payload['requires_setup']) ? 'installed' : 'needs_setup';
        $payload['status'] = self::STATUSES[$payload['status_key']];
        $payload['installed_at'] = now()->toDateTimeString();
        $payload['last_sync_at'] = now()->toDateTimeString();
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::ensureAppSettings($partner, $id, $payload);
        self::createLog($partner, $id, ['name' => 'تثبيت التطبيق', 'status' => 'ناجح', 'message' => 'تم تثبيت التطبيق.', 'created_at' => now()->toDateTimeString()]);
        self::logActivity($partner, $actor, 'app_installed', 'partner_apps', $id);

        return self::app($partner, $id);
    }

    public static function uninstall(array $partner, string $id, ?array $actor = null): void
    {
        $record = self::recordForStore($partner, 'partner_apps', $id);
        abort_if(($record->payload['status_key'] ?? '') === 'admin_paused', 403, 'App is paused by admin.');
        $payload = $record->payload ?? [];
        $payload['status_key'] = 'not_installed';
        $payload['status'] = self::STATUSES['not_installed'];
        $payload['installed_at'] = null;
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::createLog($partner, $id, ['name' => 'إزالة التطبيق', 'status' => 'ناجح', 'message' => 'تمت إزالة التطبيق.', 'created_at' => now()->toDateTimeString()]);
        self::logActivity($partner, $actor, 'app_uninstalled', 'partner_apps', $id);
    }

    public static function updateStatus(array $partner, string $id, string $status, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'partner_apps', $id);
        abort_if(($record->payload['status_key'] ?? '') === 'admin_paused' && $status !== 'admin_paused', 403, 'App is paused by admin.');
        $payload = $record->payload ?? [];
        $payload['status_key'] = $status;
        $payload['status'] = self::STATUSES[$status] ?? $status;
        $payload['updated_at_human'] = 'الآن';
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'app_status_updated', 'partner_apps', $id, ['status' => $status]);

        return self::app($partner, $id);
    }

    public static function settings(array $partner, string $id): array
    {
        self::ensureStoreData($partner);
        $app = self::normalize(self::recordForStore($partner, 'partner_apps', $id));
        self::ensureAppSettings($partner, $id, $app);

        return [
            'store_id' => $partner['store_id'],
            'app_id' => $id,
            'settings' => self::records($partner, 'partner_app_settings')->firstWhere('app_id', $id),
        ];
    }

    public static function updateSettings(array $partner, string $id, array $data, ?array $actor = null): array
    {
        $app = self::normalize(self::recordForStore($partner, 'partner_apps', $id));
        abort_if(($app['status_key'] ?? '') === 'admin_paused', 403, 'App is paused by admin.');
        self::ensureAppSettings($partner, $id, $app);
        $record = self::recordForStore($partner, 'partner_app_settings', 'settings-' . $id);
        $payload = array_merge($record->payload ?? [], [
            'api_key_masked' => self::maskSecret($data['api_key'] ?? $record->payload['api_key_masked'] ?? null),
            'permissions' => $data['permissions'] ?? ($record->payload['permissions'] ?? $app['permissions'] ?? []),
            'events' => $data['events'] ?? ($record->payload['events'] ?? []),
            'webhook_url' => $data['webhook_url'] ?? ($record->payload['webhook_url'] ?? null),
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['payload' => self::maskPayload($payload)]);
        self::updateStatus($partner, $id, 'installed', $actor);
        self::createLog($partner, $id, ['name' => 'حفظ الإعدادات', 'status' => 'ناجح', 'message' => 'تم حفظ إعدادات التطبيق.', 'created_at' => now()->toDateTimeString()]);

        return self::settings($partner, $id);
    }

    public static function test(array $partner, string $id, ?array $actor = null): array
    {
        $app = self::normalize(self::recordForStore($partner, 'partner_apps', $id));
        $settings = self::settings($partner, $id)['settings'];
        $success = ($app['status_key'] ?? '') !== 'admin_paused' && in_array($app['status_key'] ?? '', ['installed', 'needs_setup'], true);
        $log = self::createLog($partner, $id, [
            'name' => 'اختبار الاتصال',
            'status' => $success ? 'ناجح' : 'فشل',
            'message' => $success ? 'الاتصال يعمل بشكل صحيح.' : 'التطبيق غير متاح أو موقوف.',
            'api_key_masked' => $settings['api_key_masked'] ?? null,
            'created_at' => now()->toDateTimeString(),
        ]);
        self::logActivity($partner, $actor, 'app_connection_tested', 'partner_apps', $id, ['success' => $success]);

        return ['store_id' => $partner['store_id'], 'success' => $success, 'message' => $log['message'], 'log' => $log];
    }

    public static function logs(array $partner, string $id): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'app_id' => $id,
            'logs' => self::records($partner, 'partner_app_logs')->where('app_id', $id)->take(50)->values()->all(),
        ];
    }

    public static function automations(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'rules' => self::filtered(self::records($partner, 'partner_automations'), $request)->values()->all(),
            'triggers' => ['new_order' => 'طلب جديد', 'payment_paid' => 'دفع ناجح', 'low_stock' => 'انخفاض المخزون', 'abandoned_cart' => 'سلة متروكة', 'new_customer' => 'عميل جديد'],
            'actions' => ['send_whatsapp' => 'إرسال واتساب', 'send_email' => 'إرسال بريد', 'create_coupon' => 'إنشاء كوبون', 'send_notification' => 'إرسال إشعار', 'update_status' => 'تحديث حالة'],
        ];
    }

    public static function createAutomation(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        abort_if(! SubscriptionManager::featureAllowed($partner, 'automation'), 402, 'Automation is not available for the current subscription plan.');
        if (SubscriptionManager::limitReached($partner, 'automations')) {
            SubscriptionManager::recordUsageDenied($partner, $actor, 'automations');
            abort(402, 'Automation limit reached for the current subscription plan.');
        }
        $status = $data['status'] ?? 'installed';
        $record = PlatformRecord::query()->create([
            'section' => 'partner_automations',
            'record_id' => self::recordId($partner, 'automation-' . Str::lower(Str::random(8))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => self::STATUSES[$status] ?? self::STATUSES['installed'],
            'payload' => [
                'id' => Str::after(self::recordId($partner, 'automation-' . Str::lower(Str::random(8))), $partner['store_id'] . '-'),
                'name' => $data['name'],
                'trigger' => $data['trigger'],
                'action' => $data['action'],
                'conditions' => $data['conditions'] ?? null,
                'status_key' => $status,
                'status' => self::STATUSES[$status] ?? self::STATUSES['installed'],
                'runs' => 0,
                'store_id' => $partner['store_id'],
                'created_at' => now()->toDateTimeString(),
            ],
        ]);
        self::automationLog($partner, $record->payload['id'], 'تم إنشاء قاعدة الأتمتة.');
        self::logActivity($partner, $actor, 'automation_created', 'partner_automations', $record->payload['id']);

        return self::normalize($record);
    }

    public static function updateAutomation(array $partner, string $id, array $data, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'partner_automations', $id);
        $status = $data['status'] ?? ($record->payload['status_key'] ?? 'installed');
        $payload = array_merge($record->payload ?? [], array_filter([
            'name' => $data['name'] ?? null,
            'trigger' => $data['trigger'] ?? null,
            'action' => $data['action'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'status_key' => $status,
            'status' => self::STATUSES[$status] ?? $status,
            'updated_at_human' => 'الآن',
        ], fn ($value) => $value !== null));
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::automationLog($partner, $id, 'تم تحديث قاعدة الأتمتة.');
        self::logActivity($partner, $actor, 'automation_updated', 'partner_automations', $id);

        return self::normalize($record->refresh());
    }

    public static function deleteAutomation(array $partner, string $id, ?array $actor = null): void
    {
        $record = self::recordForStore($partner, 'partner_automations', $id);
        $record->delete();
        self::automationLog($partner, $id, 'تم حذف قاعدة الأتمتة.');
        self::logActivity($partner, $actor, 'automation_deleted', 'partner_automations', $id);
    }

    public static function updateAutomationStatus(array $partner, string $id, string $status, ?array $actor = null): array
    {
        return self::updateAutomation($partner, $id, ['status' => $status], $actor);
    }

    public static function automationLogs(array $partner, string $id): array
    {
        return [
            'store_id' => $partner['store_id'],
            'automation_id' => $id,
            'logs' => self::records($partner, 'partner_automation_logs')->where('automation_id', $id)->values()->all(),
        ];
    }

    public static function aiTools(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'limit' => self::aiLimit($partner),
            'tools' => self::records($partner, 'partner_ai_tools')->values()->all(),
        ];
    }

    public static function aiGenerate(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        abort_if(! SubscriptionManager::featureAllowed($partner, 'ai'), 402, 'AI tools are not available for the current subscription plan.');
        $usage = self::aiUsage($partner);
        if ($usage['limit'] !== 'unlimited' && $usage['used'] >= $usage['limit']) {
            SubscriptionManager::recordUsageDenied($partner, $actor, 'ai_requests');
            abort(402, 'AI usage limit reached for the current subscription plan.');
        }
        $prompt = trim((string) ($data['prompt'] ?? ''));
        $tool = (string) $data['tool'];
        $output = self::aiOutput($tool, $prompt);
        $record = PlatformRecord::query()->create([
            'section' => 'partner_ai_usage',
            'record_id' => self::recordId($partner, 'ai-' . Str::lower(Str::random(8))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'ناجح',
            'payload' => [
                'tool' => $tool,
                'prompt' => $prompt,
                'output' => $output,
                'tokens' => max(20, mb_strlen($prompt) + mb_strlen($output)),
                'created_at' => now()->toDateTimeString(),
                'store_id' => $partner['store_id'],
            ],
        ]);
        self::logActivity($partner, $actor, 'ai_generated', 'partner_ai_usage', $record->record_id, ['tool' => $tool]);

        return ['store_id' => $partner['store_id'], 'tool' => $tool, 'output' => $output, 'usage' => self::aiUsage($partner)];
    }

    public static function aiUsage(array $partner): array
    {
        $limit = self::aiLimit($partner);
        $records = self::records($partner, 'partner_ai_usage');
        $used = $records->count();

        return [
            'store_id' => $partner['store_id'],
            'limit' => $limit,
            'used' => $used,
            'remaining' => $limit === 'unlimited' ? 'unlimited' : max(0, (int) $limit - $used),
            'logs' => $records->take(20)->values()->all(),
        ];
    }

    public static function aiRecommendations(array $partner): array
    {
        self::ensureStoreData($partner);
        $lowStock = self::records($partner, 'products')->filter(fn (array $product) => (int) ($product['stock'] ?? 0) <= (int) ($product['low_stock_threshold'] ?? 12))->count();

        return [
            'store_id' => $partner['store_id'],
            'recommendations' => [
                ['title' => 'حسّن وصف المنتجات الأكثر زيارة', 'source' => 'products', 'priority' => 'متوسطة'],
                ['title' => 'أطلق حملة للسلات المتروكة', 'source' => 'abandoned_carts', 'priority' => 'عالية'],
                ['title' => 'راجع المنتجات منخفضة المخزون: ' . $lowStock, 'source' => 'inventory', 'priority' => $lowStock ? 'عالية' : 'منخفضة'],
            ],
        ];
    }

    private static function ensureCatalog(array $partner): void
    {
        self::ensureSection($partner, 'partner_apps', [
            ['id' => 'mada-pay', 'name' => 'Mada Pay', 'category_key' => 'payment', 'provider' => 'Mada', 'plan' => 'Starter', 'price' => 'مجاني', 'status_key' => 'not_installed', 'permissions' => ['orders:read', 'payments:write'], 'features' => ['مدفوعات مدى', 'ربط الفواتير'], 'requires_setup' => true],
            ['id' => 'aramex-ship', 'name' => 'Aramex Shipping', 'category_key' => 'shipping', 'provider' => 'Aramex', 'plan' => 'Starter', 'price' => 'حسب الاستخدام', 'status_key' => 'not_installed', 'permissions' => ['orders:read', 'shipments:write'], 'features' => ['بوالص شحن', 'تتبع الشحنة'], 'requires_setup' => true],
            ['id' => 'mailchimp', 'name' => 'Mailchimp Campaigns', 'category_key' => 'marketing', 'provider' => 'Mailchimp', 'plan' => 'Growth', 'price' => '49 ر.س/شهر', 'status_key' => 'not_installed', 'permissions' => ['customers:read', 'campaigns:write'], 'features' => ['حملات بريدية', 'شرائح العملاء'], 'requires_setup' => true],
            ['id' => 'ga4', 'name' => 'Google Analytics 4', 'category_key' => 'analytics', 'provider' => 'Google', 'plan' => 'Starter', 'price' => 'مجاني', 'status_key' => 'not_installed', 'permissions' => ['analytics:read'], 'features' => ['مصادر الزيارات', 'تتبع التحويل'], 'requires_setup' => true],
            ['id' => 'quickbooks', 'name' => 'QuickBooks', 'category_key' => 'accounting', 'provider' => 'Intuit', 'plan' => 'Enterprise', 'price' => 'حسب الباقة', 'status_key' => 'not_installed', 'permissions' => ['finance:read', 'invoices:write'], 'features' => ['ترحيل الفواتير', 'قيود محاسبية'], 'requires_setup' => true],
            ['id' => 'zendesk', 'name' => 'Zendesk Support', 'category_key' => 'support', 'provider' => 'Zendesk', 'plan' => 'Growth', 'price' => 'حسب الاستخدام', 'status_key' => 'not_installed', 'permissions' => ['customers:read', 'orders:read'], 'features' => ['تذاكر دعم', 'ملف العميل'], 'requires_setup' => true],
            ['id' => 'solve-ai', 'name' => 'Solve AI Assistant', 'category_key' => 'ai', 'provider' => 'Solve', 'plan' => 'Enterprise', 'price' => 'ضمن الباقة', 'status_key' => 'needs_setup', 'permissions' => ['products:write', 'analytics:read'], 'features' => ['وصف منتجات', 'SEO', 'اقتراح حملات'], 'requires_setup' => false],
        ], true);
    }

    private static function ensureAutomations(array $partner): void
    {
        self::ensureSection($partner, 'partner_automations', [[
            'id' => 'low-stock-whatsapp',
            'name' => 'تنبيه انخفاض المخزون',
            'trigger' => 'low_stock',
            'action' => 'send_notification',
            'conditions' => 'عندما يصل المنتج إلى حد التنبيه',
            'status_key' => 'installed',
            'runs' => 0,
        ]], true);
    }

    private static function ensureAi(array $partner): void
    {
        self::ensureSection($partner, 'partner_ai_tools', [
            ['id' => 'product-description', 'name' => 'كتابة وصف منتج', 'category_key' => 'ai', 'status_key' => 'installed'],
            ['id' => 'product-title', 'name' => 'اقتراح عنوان منتج', 'category_key' => 'ai', 'status_key' => 'installed'],
            ['id' => 'seo-keywords', 'name' => 'اقتراح كلمات SEO', 'category_key' => 'ai', 'status_key' => 'installed'],
            ['id' => 'store-analysis', 'name' => 'تحليل أداء المتجر', 'category_key' => 'ai', 'status_key' => 'installed'],
            ['id' => 'campaign-ideas', 'name' => 'اقتراح حملات', 'category_key' => 'ai', 'status_key' => 'installed'],
            ['id' => 'product-improvements', 'name' => 'منتجات تحتاج تحسين', 'category_key' => 'ai', 'status_key' => 'installed'],
        ], true);
    }

    private static function ensureAppSettings(array $partner, string $id, array $app): void
    {
        if (PlatformRecord::query()->where('section', 'partner_app_settings')->where('store_id', $partner['store_id'])->where('payload->app_id', $id)->exists()) {
            return;
        }

        self::ensureSection($partner, 'partner_app_settings', [[
            'id' => 'settings-' . $id,
            'app_id' => $id,
            'name' => 'إعدادات ' . ($app['name'] ?? $id),
            'api_key_masked' => null,
            'permissions' => $app['permissions'] ?? [],
            'events' => ['order.created', 'payment.paid'],
            'webhook_url' => null,
            'status_key' => 'needs_setup',
        ]], true);
    }

    private static function ensureSection(array $partner, string $section, array $rows, bool $useGivenId = false): void
    {
        foreach ($rows as $index => $row) {
            $id = $useGivenId ? (string) $row['id'] : $section . '-' . ($index + 1);
            if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->where('payload->id', $id)->exists()) {
                continue;
            }
            $statusKey = $row['status_key'] ?? 'installed';
            $available = self::isPlanAvailable($partner, $row['plan'] ?? 'Starter');
            $payload = $row + [
                'id' => $id,
                'status_key' => $available ? $statusKey : 'not_installed',
                'status' => self::STATUSES[$available ? $statusKey : 'not_installed'],
                'available' => $available,
                'store_id' => $partner['store_id'],
                'category' => self::CATEGORIES[$row['category_key'] ?? 'support'] ?? ($row['category_key'] ?? 'support'),
            ];
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => self::recordId($partner, $id),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'],
                'payload' => $payload,
            ]);
        }
    }

    private static function records(array $partner, string $section): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::normalize($record));
    }

    private static function filtered(Collection $rows, Request $request): Collection
    {
        $query = Str::lower(trim((string) $request->query('q', '')));
        $category = trim((string) $request->query('category', 'all'));
        $status = trim((string) $request->query('status', 'all'));

        return $rows
            ->filter(fn (array $row) => $query === '' || Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $row) => $category === 'all' || ($row['category_key'] ?? '') === $category)
            ->filter(fn (array $row) => $status === 'all' || ($row['status_key'] ?? '') === $status)
            ->values();
    }

    private static function normalize(PlatformRecord $record): array
    {
        $payload = self::maskPayload($record->payload ?? []);
        $statusKey = $payload['status_key'] ?? 'installed';

        return array_merge($payload, [
            'id' => $payload['id'] ?? $record->record_id,
            'store_id' => $record->store_id,
            'name' => $payload['name'] ?? $record->record_id,
            'status_key' => $statusKey,
            'status' => $payload['status'] ?? self::STATUSES[$statusKey] ?? $record->status,
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function recordForStore(array $partner, string $section, string $id): PlatformRecord
    {
        self::ensureStoreData($partner);
        $record = PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->where(function ($query) use ($id) {
                $query->where('record_id', self::recordId(['store_id' => ''], $id))->orWhere('payload->id', $id);
            })
            ->first();
        abort_unless($record, 404);

        return $record;
    }

    private static function createLog(array $partner, string $appId, array $payload): array
    {
        $record = PlatformRecord::query()->create([
            'section' => 'partner_app_logs',
            'record_id' => self::recordId($partner, 'app-log-' . Str::lower(Str::random(8))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'] ?? null,
            'payload' => $payload + ['app_id' => $appId, 'store_id' => $partner['store_id']],
        ]);

        return self::normalize($record);
    }

    private static function automationLog(array $partner, string $automationId, string $message): void
    {
        PlatformRecord::query()->create([
            'section' => 'partner_automation_logs',
            'record_id' => self::recordId($partner, 'automation-log-' . Str::lower(Str::random(8))),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => 'ناجح',
            'payload' => [
                'automation_id' => $automationId,
                'name' => 'تشغيل الأتمتة',
                'message' => $message,
                'created_at' => now()->toDateTimeString(),
                'store_id' => $partner['store_id'],
            ],
        ]);
    }

    private static function recordId(array $partner, string $id): string
    {
        $storeId = $partner['store_id'] ?? '';
        $prefix = $storeId !== '' ? $storeId . '-' : '';

        return Str::startsWith($id, $prefix) ? $id : $prefix . $id;
    }

    private static function isPlanAvailable(array $partner, string $required): bool
    {
        $rank = ['Starter' => 1, 'Growth' => 2, 'Enterprise' => 3];

        return ($rank[$partner['plan'] ?? 'Starter'] ?? 1) >= ($rank[$required] ?? 1);
    }

    private static function aiLimit(array $partner): int|string
    {
        $plan = SubscriptionManager::plan((string) ($partner['plan'] ?? 'Starter'));

        return $plan['limits']['ai_requests'] ?? 0;
    }

    private static function aiOutput(string $tool, string $prompt): string
    {
        return match ($tool) {
            'product-description' => 'وصف احترافي يبرز قيمة المنتج: ' . $prompt,
            'product-title' => 'عنوان مقترح: ' . Str::limit($prompt, 60, ''),
            'seo-keywords' => 'كلمات SEO: متجر، جودة، شحن سريع، ' . $prompt,
            'store-analysis' => 'تحليل مختصر: ركز على المنتجات الأعلى مبيعاً وراجع السلات المتروكة.',
            'campaign-ideas' => 'حملة مقترحة: خصم محدود مع تذكير واتساب للعملاء المهتمين.',
            default => 'اقتراح تحسين: راجع المحتوى والصور والمخزون لهذا العنصر.',
        };
    }

    private static function maskPayload(array $payload): array
    {
        unset($payload['api_key'], $payload['access_token'], $payload['secret']);

        return $payload;
    }

    private static function maskSecret(?string $secret): ?string
    {
        $secret = trim((string) $secret);
        if ($secret === '') {
            return null;
        }

        return str_repeat('*', max(6, strlen($secret) - 4)) . substr($secret, -4);
    }

    private static function logActivity(array $partner, ?array $actor, string $action, string $subjectType, string $subjectId, array $properties = []): void
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return;
        }

        PlatformActivityLog::query()->create([
            'actor_type' => 'partner',
            'actor_id' => $actor['username'] ?? $actor['email'] ?? null,
            'actor_name' => $actor['name'] ?? null,
            'role' => $actor['role'] ?? null,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
