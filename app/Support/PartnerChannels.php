<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerChannels
{
    public const STATUSES = [
        'enabled' => 'مفعلة',
        'disabled' => 'غير مفعلة',
        'needs_setup' => 'تحتاج إعداد',
        'admin_paused' => 'متوقفة من الأدمن',
    ];

    public const CHANNELS = ['storefront', 'marketplaces', 'mobile-app', 'pos'];

    public static function ensureStoreData(array $partner): void
    {
        PartnerStorefront::ensureStoreData($partner);
        PartnerOrders::ensureStoreData($partner);
        PartnerProducts::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureCatalog($partner);
        self::ensureMarketplaces($partner);
        self::ensureMobileApp($partner);
        self::ensurePos($partner);
    }

    public static function summary(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);
        $channels = self::filtered(self::records($partner, 'partner_channels'), $request);

        return [
            'store_id' => $partner['store_id'],
            'plan' => $partner['plan'] ?? 'Starter',
            'channels' => $channels->values()->all(),
            'counts' => [
                'total' => $channels->count(),
                'enabled' => $channels->where('status_key', 'enabled')->count(),
                'needs_setup' => $channels->where('status_key', 'needs_setup')->count(),
                'admin_paused' => $channels->where('status_key', 'admin_paused')->count(),
            ],
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
            ],
            'statusOptions' => ['all' => 'كل الحالات'] + self::STATUSES,
            'quickActions' => [
                ['label' => 'معاينة المتجر', 'route' => 'partner.channels.storefront'],
                ['label' => 'ربط منصة بيع', 'route' => 'partner.channels.marketplaces'],
                ['label' => 'إعداد التطبيق', 'route' => 'partner.channels.mobile-app'],
                ['label' => 'إدارة POS', 'route' => 'partner.channels.pos'],
            ],
            'alerts' => $channels
                ->filter(fn (array $row) => in_array($row['status_key'] ?? '', ['needs_setup', 'admin_paused'], true))
                ->map(fn (array $row) => ['title' => $row['name'], 'body' => $row['status'] . ' · ' . ($row['help_tip'] ?? 'أكمل إعداد القناة')])
                ->values()
                ->all(),
        ];
    }

    public static function channel(array $partner, string $id): array
    {
        self::ensureStoreData($partner);

        return self::normalize(self::recordForStore($partner, 'partner_channels', $id));
    }

    public static function updateStatus(array $partner, string $id, string $status, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'partner_channels', $id);
        $payload = $record->payload ?? [];
        abort_if(($payload['status_key'] ?? null) === 'admin_paused' && $status === 'enabled', 403, 'Channel is paused by admin.');
        abort_if(! self::isPlanAvailable($partner, $payload['plan'] ?? 'Starter') && $status === 'enabled', 403, 'Channel is not available for this plan.');
        if ($status === 'enabled' && ($payload['status_key'] ?? null) !== 'enabled' && SubscriptionManager::limitReached($partner, 'channels')) {
            SubscriptionManager::recordUsageDenied($partner, $actor, 'channels');
            abort(402, 'Channel limit reached for the current subscription plan.');
        }

        $payload['status_key'] = $status;
        $payload['status'] = self::STATUSES[$status] ?? $status;
        $payload['updated_at_human'] = 'الآن';
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'channel_status_updated', 'partner_channels', $id, ['status' => $status]);

        return self::normalize($record->refresh());
    }

    public static function sync(array $partner, string $id, ?array $actor = null, string $type = 'full'): array
    {
        $channel = self::channel($partner, $id);
        abort_if(($channel['status_key'] ?? '') === 'admin_paused', 403, 'Channel is paused by admin.');

        $products = self::countRecords($partner, 'products');
        $orders = self::ordersForChannel($partner, $id);
        $success = ($channel['status_key'] ?? '') !== 'disabled';
        $log = self::createLog($partner, $id, [
            'name' => 'مزامنة ' . ($channel['name'] ?? $id),
            'sync_type' => $type,
            'success' => $success,
            'status' => $success ? 'ناجحة' : 'تحتاج تفعيل',
            'products_synced' => $success ? $products : 0,
            'orders_synced' => $success ? $orders : 0,
            'message' => $success ? 'تمت مزامنة المنتجات والطلبات.' : 'فعّل القناة قبل المزامنة.',
            'created_at' => now()->toDateTimeString(),
        ]);

        self::touchSync($partner, $id, $success, $products, $orders);
        self::logActivity($partner, $actor, 'channel_synced', 'partner_channels', $id, ['success' => $success, 'type' => $type]);

        return [
            'store_id' => $partner['store_id'],
            'channel' => self::channel($partner, $id),
            'success' => $success,
            'sync_status' => self::syncStatus($partner, $id),
            'log' => $log,
        ];
    }

    public static function syncStatus(array $partner, string $id): array
    {
        $channel = self::channel($partner, $id);

        return [
            'store_id' => $partner['store_id'],
            'channel_id' => $id,
            'status' => $channel['status'] ?? null,
            'status_key' => $channel['status_key'] ?? null,
            'last_sync_at' => $channel['last_sync_at'] ?? null,
            'products_synced' => $channel['products_synced'] ?? 0,
            'orders_synced' => $channel['orders_synced'] ?? 0,
        ];
    }

    public static function logs(array $partner, string $id): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'channel_id' => $id,
            'logs' => self::records($partner, 'channel_sync_logs')
                ->where('channel_id', $id)
                ->take(50)
                ->values()
                ->all(),
        ];
    }

    public static function storefront(array $partner): array
    {
        self::ensureStoreData($partner);
        $channel = self::channel($partner, 'storefront');

        return [
            'store_id' => $partner['store_id'],
            'channel' => $channel,
            'storefront' => [
                'url' => $partner['store_url'] ?? ('https://' . $partner['store_id'] . '.solve.test'),
                'domain_status' => $channel['domain_status'] ?? 'متصل',
                'theme_status' => $channel['theme_status'] ?? 'منشور',
                'visibility' => $channel['visibility'] ?? 'عام',
                'preview_url' => route('partner.storefront'),
            ],
        ];
    }

    public static function updateStorefront(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'partner_channels', 'storefront');
        $payload = array_merge($record->payload ?? [], [
            'visibility' => $data['visibility'] ?? ($record->payload['visibility'] ?? 'عام'),
            'domain_status' => $data['domain_status'] ?? ($record->payload['domain_status'] ?? 'متصل'),
            'theme_status' => $data['theme_status'] ?? ($record->payload['theme_status'] ?? 'منشور'),
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'channel_storefront_settings_updated', 'partner_channels', 'storefront');

        return self::storefront($partner);
    }

    public static function marketplaces(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);
        $rows = self::filtered(self::records($partner, 'channel_marketplaces'), $request);

        return [
            'store_id' => $partner['store_id'],
            'rows' => $rows->values()->all(),
            'filters' => ['q' => trim((string) $request->query('q', '')), 'status' => trim((string) $request->query('status', 'all'))],
            'statusOptions' => ['all' => 'كل الحالات'] + self::STATUSES,
        ];
    }

    public static function connectMarketplace(array $partner, string $id, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'channel_marketplaces', $id);
        abort_if(($record->payload['status_key'] ?? null) === 'admin_paused', 403, 'Marketplace is paused by admin.');
        $payload = array_merge($record->payload ?? [], [
            'seller_id' => $data['seller_id'] ?? ($record->payload['seller_id'] ?? null),
            'api_key_masked' => self::maskSecret($data['api_key'] ?? null),
            'status_key' => 'enabled',
            'status' => self::STATUSES['enabled'],
            'last_sync_at' => now()->toDateTimeString(),
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::touchSync($partner, 'marketplaces');
        self::logActivity($partner, $actor, 'marketplace_connected', 'channel_marketplaces', $id);

        return self::normalize($record->refresh());
    }

    public static function updateMarketplace(array $partner, string $id, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'channel_marketplaces', $id);
        abort_if(($record->payload['status_key'] ?? null) === 'admin_paused', 403, 'Marketplace is paused by admin.');
        $status = $data['status'] ?? ($record->payload['status_key'] ?? 'needs_setup');
        $payload = array_merge($record->payload ?? [], [
            'seller_id' => $data['seller_id'] ?? ($record->payload['seller_id'] ?? null),
            'api_key_masked' => self::maskSecret($data['api_key'] ?? $record->payload['api_key_masked'] ?? null),
            'sync_products' => (bool) ($data['sync_products'] ?? true),
            'sync_orders' => (bool) ($data['sync_orders'] ?? true),
            'status_key' => $status,
            'status' => self::STATUSES[$status] ?? $status,
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'marketplace_settings_updated', 'channel_marketplaces', $id);

        return self::normalize($record->refresh());
    }

    public static function syncMarketplace(array $partner, string $id, string $type, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $marketplace = self::recordForStore($partner, 'channel_marketplaces', $id);
        $payload = $marketplace->payload ?? [];
        abort_if(($payload['status_key'] ?? '') === 'admin_paused', 403, 'Marketplace is paused by admin.');
        $success = ($payload['status_key'] ?? '') === 'enabled';
        $products = in_array($type, ['products', 'full'], true) && $success ? self::countRecords($partner, 'products') : 0;
        $orders = in_array($type, ['orders', 'full'], true) && $success ? self::ordersForChannel($partner, $payload['name'] ?? $id) : 0;
        $payload['last_sync_at'] = now()->toDateTimeString();
        $payload['products_synced'] = ($payload['products_synced'] ?? 0) + $products;
        $payload['orders_synced'] = ($payload['orders_synced'] ?? 0) + $orders;
        $marketplace->update(['payload' => $payload]);
        $log = self::createLog($partner, 'marketplaces', [
            'marketplace_id' => $id,
            'name' => 'مزامنة ' . ($payload['name'] ?? $id),
            'sync_type' => $type,
            'success' => $success,
            'status' => $success ? 'ناجحة' : 'تحتاج ربط',
            'products_synced' => $products,
            'orders_synced' => $orders,
            'created_at' => now()->toDateTimeString(),
        ]);
        self::touchSync($partner, 'marketplaces');
        self::logActivity($partner, $actor, 'marketplace_synced', 'channel_marketplaces', $id, ['type' => $type, 'success' => $success]);

        return ['store_id' => $partner['store_id'], 'success' => $success, 'marketplace' => self::normalize($marketplace->refresh()), 'log' => $log];
    }

    public static function mobileApp(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'channel' => self::channel($partner, 'mobile-app'),
            'settings' => self::records($partner, 'channel_mobile_app_settings')->first(),
        ];
    }

    public static function updateMobileApp(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'channel_mobile_app_settings');
        $payload = array_merge($record->payload ?? [], [
            'primary_color' => $data['primary_color'] ?? '#6d28d9',
            'logo_url' => $data['logo_url'] ?? null,
            'push_enabled' => (bool) ($data['push_enabled'] ?? false),
            'publish_status' => $data['publish_status'] ?? 'مسودة',
            'app_store_url' => $data['app_store_url'] ?? null,
            'google_play_url' => $data['google_play_url'] ?? null,
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['payload' => $payload]);
        self::syncCatalogStatus($partner, 'mobile-app', $payload['push_enabled'] ? 'enabled' : 'needs_setup');
        self::logActivity($partner, $actor, 'mobile_app_settings_updated', 'channel_mobile_app_settings', $record->record_id);

        return self::mobileApp($partner);
    }

    public static function pushTest(array $partner, ?array $actor = null): array
    {
        $settings = self::mobileApp($partner)['settings'];
        $success = (bool) ($settings['push_enabled'] ?? false);
        $log = self::createLog($partner, 'mobile-app', [
            'name' => 'اختبار Push Notification',
            'sync_type' => 'push_test',
            'success' => $success,
            'status' => $success ? 'تم الإرسال' : 'تحتاج تفعيل',
            'created_at' => now()->toDateTimeString(),
        ]);
        self::logActivity($partner, $actor, 'mobile_push_tested', 'channel_mobile_app_settings', $settings['id'] ?? 'mobile-app', ['success' => $success]);

        return ['store_id' => $partner['store_id'], 'success' => $success, 'message' => $success ? 'تم إرسال إشعار اختباري.' : 'فعّل Push Notifications أولاً.', 'log' => $log];
    }

    public static function pos(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'channel' => self::channel($partner, 'pos'),
            'settings' => self::records($partner, 'channel_pos_settings')->first(),
            'devices' => self::records($partner, 'channel_pos_devices')->values()->all(),
            'reports' => self::posReports($partner),
        ];
    }

    public static function updatePos(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'channel_pos_settings');
        $payload = array_merge($record->payload ?? [], [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'branch_name' => $data['branch_name'] ?? ($record->payload['branch_name'] ?? null),
            'sync_inventory' => (bool) ($data['sync_inventory'] ?? true),
            'allow_returns' => (bool) ($data['allow_returns'] ?? true),
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['payload' => $payload]);
        self::syncCatalogStatus($partner, 'pos', $payload['enabled'] ? 'enabled' : 'disabled');
        self::logActivity($partner, $actor, 'pos_settings_updated', 'channel_pos_settings', $record->record_id);

        return self::pos($partner);
    }

    public static function createPosDevice(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = PlatformRecord::query()->create([
            'section' => 'channel_pos_devices',
            'record_id' => 'pos-device-' . Str::lower(Str::random(8)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => self::STATUSES[$data['status'] ?? 'enabled'] ?? self::STATUSES['enabled'],
            'payload' => [
                'name' => $data['name'],
                'cashier' => $data['cashier'] ?? null,
                'branch' => $data['branch'] ?? null,
                'status_key' => $data['status'] ?? 'enabled',
                'status' => self::STATUSES[$data['status'] ?? 'enabled'] ?? self::STATUSES['enabled'],
                'last_sync_at' => now()->toDateTimeString(),
                'store_id' => $partner['store_id'],
            ],
        ]);
        self::logActivity($partner, $actor, 'pos_device_created', 'channel_pos_devices', $record->record_id);

        return self::normalize($record);
    }

    public static function updatePosDevice(array $partner, string $id, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::recordForStore($partner, 'channel_pos_devices', $id);
        $status = $data['status'] ?? ($record->payload['status_key'] ?? 'enabled');
        $payload = array_merge($record->payload ?? [], [
            'name' => $data['name'] ?? ($record->payload['name'] ?? null),
            'cashier' => $data['cashier'] ?? ($record->payload['cashier'] ?? null),
            'branch' => $data['branch'] ?? ($record->payload['branch'] ?? null),
            'status_key' => $status,
            'status' => self::STATUSES[$status] ?? $status,
            'updated_at_human' => 'الآن',
        ]);
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'pos_device_updated', 'channel_pos_devices', $id);

        return self::normalize($record->refresh());
    }

    public static function posReports(array $partner): array
    {
        $orders = self::records($partner, 'orders')->filter(fn (array $order) => str_contains(Str::lower((string) ($order['source'] ?? $order['channel'] ?? '')), 'pos'));

        return [
            'store_id' => $partner['store_id'],
            'orders' => $orders->count(),
            'sales' => $orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0)),
            'returns' => self::records($partner, 'returns')->filter(fn (array $row) => str_contains(Str::lower((string) ($row['source'] ?? '')), 'pos'))->count(),
            'devices' => self::records($partner, 'channel_pos_devices')->count(),
        ];
    }

    private static function ensureCatalog(array $partner): void
    {
        self::ensureSection($partner, 'partner_channels', [
            ['id' => 'storefront', 'name' => 'المتجر الإلكتروني', 'plan' => 'Starter', 'status_key' => 'enabled', 'provider' => 'Solve Storefront', 'help_tip' => 'مرتبط بقسم المتجر الإلكتروني والدومين والقالب.', 'domain_status' => 'متصل', 'theme_status' => 'منشور', 'visibility' => 'عام'],
            ['id' => 'marketplaces', 'name' => 'منصات البيع', 'plan' => 'Growth', 'status_key' => 'needs_setup', 'provider' => 'Amazon, Noon, TikTok', 'help_tip' => 'اربط مفاتيح المنصات ثم شغّل مزامنة المنتجات والطلبات.'],
            ['id' => 'mobile-app', 'name' => 'تطبيق الجوال', 'plan' => 'Enterprise', 'status_key' => 'needs_setup', 'provider' => 'iOS / Android', 'help_tip' => 'اضبط الهوية وPush Notifications وروابط المتاجر.'],
            ['id' => 'pos', 'name' => 'نقاط البيع POS', 'plan' => 'Enterprise', 'status_key' => 'disabled', 'provider' => 'Solve POS', 'help_tip' => 'فعّل الأجهزة والكاشير وربط المخزون والمالية.'],
        ], true);
        self::refreshCatalogMetrics($partner);
    }

    private static function ensureMarketplaces(array $partner): void
    {
        self::ensureSection($partner, 'channel_marketplaces', [
            ['id' => 'amazon', 'name' => 'Amazon', 'seller_id' => null, 'api_key_masked' => null, 'status_key' => 'needs_setup', 'products_synced' => 0, 'orders_synced' => 0],
            ['id' => 'noon', 'name' => 'Noon', 'seller_id' => null, 'api_key_masked' => null, 'status_key' => 'needs_setup', 'products_synced' => 0, 'orders_synced' => 0],
            ['id' => 'tiktok-shop', 'name' => 'TikTok Shop', 'seller_id' => null, 'api_key_masked' => null, 'status_key' => 'needs_setup', 'products_synced' => 0, 'orders_synced' => 0],
            ['id' => 'instagram-shop', 'name' => 'Instagram Shop', 'seller_id' => null, 'api_key_masked' => null, 'status_key' => 'needs_setup', 'products_synced' => 0, 'orders_synced' => 0],
            ['id' => 'facebook-shop', 'name' => 'Facebook Shop', 'seller_id' => null, 'api_key_masked' => null, 'status_key' => 'needs_setup', 'products_synced' => 0, 'orders_synced' => 0],
        ], true);
    }

    private static function ensureMobileApp(array $partner): void
    {
        self::ensureSection($partner, 'channel_mobile_app_settings', [[
            'name' => 'إعدادات تطبيق الجوال',
            'primary_color' => '#6d28d9',
            'logo_url' => $partner['logo'] ?? null,
            'push_enabled' => false,
            'publish_status' => 'مسودة',
            'app_store_url' => null,
            'google_play_url' => null,
            'status_key' => 'needs_setup',
        ]]);
    }

    private static function ensurePos(array $partner): void
    {
        self::ensureSection($partner, 'channel_pos_settings', [[
            'name' => 'إعدادات POS',
            'enabled' => false,
            'branch_name' => 'الفرع الرئيسي',
            'sync_inventory' => true,
            'allow_returns' => true,
            'status_key' => 'disabled',
        ]]);
        self::ensureSection($partner, 'channel_pos_devices', [[
            'name' => 'كاشير 1',
            'cashier' => 'فريق المبيعات',
            'branch' => 'الفرع الرئيسي',
            'status_key' => 'disabled',
            'last_sync_at' => null,
        ]]);
    }

    private static function ensureSection(array $partner, string $section, array $rows, bool $useGivenId = false): void
    {
        if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            $statusKey = $row['status_key'] ?? 'enabled';
            $available = self::isPlanAvailable($partner, $row['plan'] ?? 'Starter');
            $payload = $row + [
                'status_key' => $available ? $statusKey : 'disabled',
                'status' => self::STATUSES[$available ? $statusKey : 'disabled'],
                'available' => $available,
                'store_id' => $partner['store_id'],
                'last_sync_at' => $row['last_sync_at'] ?? null,
                'products_synced' => $row['products_synced'] ?? 0,
                'orders_synced' => $row['orders_synced'] ?? 0,
            ];
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $useGivenId ? $partner['store_id'] . '-' . $row['id'] : $section . '-' . $partner['store_id'] . '-' . ($index + 1),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'],
                'payload' => $payload,
            ]);
        }
    }

    private static function refreshCatalogMetrics(array $partner): void
    {
        PlatformRecord::query()
            ->where('section', 'partner_channels')
            ->where('store_id', $partner['store_id'])
            ->get()
            ->each(function (PlatformRecord $record) use ($partner) {
                $payload = $record->payload ?? [];
                $id = $record->record_id;
                $payload['products_synced'] = $payload['products_synced'] ?? self::countRecords($partner, 'products');
                $payload['orders_synced'] = self::ordersForChannel($partner, $id);
                $record->update(['payload' => $payload]);
            });
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
        $status = trim((string) $request->query('status', 'all'));

        return $rows
            ->filter(fn (array $row) => $query === '' || Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $row) => $status === 'all' || ($row['status_key'] ?? '') === $status)
            ->values();
    }

    private static function normalize(PlatformRecord $record): array
    {
        $payload = self::maskPayload($record->payload ?? []);
        $statusKey = $payload['status_key'] ?? 'enabled';

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
        abort_unless(Schema::hasTable('platform_records'), 503);
        $record = PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->where(function ($query) use ($id) {
                $query->where('record_id', $id)->orWhere('payload->id', $id);
            })
            ->first();
        abort_unless($record, 404);

        return $record;
    }

    private static function singleRecord(array $partner, string $section): PlatformRecord
    {
        return PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->firstOrFail();
    }

    private static function syncCatalogStatus(array $partner, string $id, ?string $status): void
    {
        $catalog = PlatformRecord::query()
            ->where('section', 'partner_channels')
            ->where('store_id', $partner['store_id'])
            ->where(function ($query) use ($id) {
                $query->where('record_id', $id)->orWhere('payload->id', $id);
            })
            ->first();
        if (! $catalog || ($catalog->payload['status_key'] ?? null) === 'admin_paused') {
            return;
        }
        $payload = $catalog->payload ?? [];
        $payload['status_key'] = $status ?? 'disabled';
        $payload['status'] = self::STATUSES[$payload['status_key']] ?? $payload['status_key'];
        $catalog->update(['status' => $payload['status'], 'payload' => $payload]);
    }

    private static function touchSync(array $partner, string $id, bool $success = true, ?int $products = null, ?int $orders = null): void
    {
        $record = PlatformRecord::query()
            ->where('section', 'partner_channels')
            ->where('store_id', $partner['store_id'])
            ->where(function ($query) use ($id) {
                $query->where('record_id', $id)->orWhere('payload->id', $id);
            })
            ->first();
        if (! $record) {
            return;
        }
        $payload = $record->payload ?? [];
        $payload['last_sync_at'] = now()->toDateTimeString();
        $payload['last_sync_status'] = $success ? 'ناجحة' : 'تحتاج متابعة';
        $payload['products_synced'] = $products ?? ($payload['products_synced'] ?? self::countRecords($partner, 'products'));
        $payload['orders_synced'] = $orders ?? self::ordersForChannel($partner, $id);
        $record->update(['payload' => $payload]);
    }

    private static function createLog(array $partner, string $channelId, array $payload): array
    {
        $record = PlatformRecord::query()->create([
            'section' => 'channel_sync_logs',
            'record_id' => 'channel-log-' . Str::lower(Str::random(8)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'] ?? null,
            'payload' => array_merge($payload, [
                'channel_id' => $channelId,
                'store_id' => $partner['store_id'],
            ]),
        ]);

        return self::normalize($record);
    }

    private static function countRecords(array $partner, string $section): int
    {
        if (! Schema::hasTable('platform_records')) {
            return 0;
        }

        return PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->count();
    }

    private static function ordersForChannel(array $partner, string $channel): int
    {
        return self::records($partner, 'orders')
            ->filter(function (array $order) use ($channel) {
                $source = Str::lower((string) ($order['source'] ?? $order['channel'] ?? 'storefront'));
                $needle = Str::lower(str_replace('-', ' ', $channel));

                return str_contains($source, $needle) || ($channel === 'storefront' && str_contains($source, 'store'));
            })
            ->count();
    }

    private static function isPlanAvailable(array $partner, string $required): bool
    {
        $rank = ['Starter' => 1, 'Growth' => 2, 'Enterprise' => 3];

        return ($rank[$partner['plan'] ?? 'Starter'] ?? 1) >= ($rank[$required] ?? 1);
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

    private static function money(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
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
