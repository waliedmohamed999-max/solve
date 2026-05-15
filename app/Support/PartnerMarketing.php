<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerMarketing
{
    public const COUPON_TYPES = [
        'percentage' => 'نسبة',
        'fixed' => 'مبلغ ثابت',
        'free_shipping' => 'شحن مجاني',
    ];

    public const STATUSES = [
        'active' => 'نشط',
        'scheduled' => 'مجدول',
        'paused' => 'متوقف',
        'expired' => 'منتهي',
        'draft' => 'مسودة',
    ];

    public const CAMPAIGN_TYPES = [
        'email' => 'بريد إلكتروني',
        'sms' => 'رسائل SMS',
        'whatsapp' => 'واتساب',
        'push' => 'إشعارات',
        'ads' => 'إعلانات',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerDashboardSummary::ensureStoreData($partner);
        PartnerOrders::ensureStoreData($partner);
        PartnerProducts::ensureStoreData($partner);
        PartnerCustomers::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureCoupons($partner);
        self::ensureCampaigns($partner);
        self::ensureBundles($partner);
        self::ensureLoyalty($partner);
        self::ensureAffiliate($partner);
        self::ensureAds($partner);
    }

    public static function summary(array $partner): array
    {
        self::ensureStoreData($partner);

        $coupons = self::records($partner, 'marketing_coupons');
        $campaigns = self::records($partner, 'marketing_campaigns');
        $bundles = self::records($partner, 'marketing_bundles');
        $affiliate = self::records($partner, 'marketing_affiliate_links');
        $ads = self::records($partner, 'marketing_ads_integrations');
        $carts = collect(PartnerOrders::relatedRows($partner, 'abandoned_carts'));
        $campaignSales = $campaigns->sum(fn (array $row) => self::money($row['sales'] ?? 0));
        $usedCoupons = $coupons->sum(fn (array $row) => (int) ($row['used'] ?? 0));
        $recovered = $carts->filter(fn (array $row) => ! empty($row['converted_order_id'] ?? null) || Str::contains((string) ($row['status'] ?? ''), 'تحويل'))->count();
        $bestCampaign = $campaigns->sortByDesc(fn (array $row) => self::money($row['sales'] ?? 0))->first();

        return [
            'store_id' => $partner['store_id'],
            'kpis' => [
                ['label' => 'إجمالي الحملات', 'value' => $campaigns->count(), 'hint' => 'حملات نشطة ومجدولة'],
                ['label' => 'مبيعات الحملات', 'value' => self::formatMoney($campaignSales), 'hint' => 'مرتبطة بطلبات المتجر'],
                ['label' => 'استخدام الكوبونات', 'value' => $usedCoupons, 'hint' => 'مرات استخدام فعلية'],
                ['label' => 'استرجاع السلات', 'value' => $carts->count() ? round(($recovered / $carts->count()) * 100) . '%' : '0%', 'hint' => 'من السلات المتروكة'],
            ],
            'bestCampaign' => $bestCampaign,
            'counts' => [
                'coupons' => $coupons->count(),
                'campaigns' => $campaigns->count(),
                'bundles' => $bundles->count(),
                'affiliate' => $affiliate->count(),
                'ads_connected' => $ads->where('status_key', 'active')->count(),
            ],
            'quickActions' => [
                ['label' => 'إنشاء حملة', 'route' => 'partner.marketing.campaigns'],
                ['label' => 'إنشاء كوبون', 'route' => 'partner.marketing.coupons'],
                ['label' => 'استرجاع السلات', 'route' => 'partner.marketing.abandoned-carts'],
            ],
            'recent' => [
                'campaigns' => $campaigns->take(5)->values()->all(),
                'coupons' => $coupons->take(5)->values()->all(),
            ],
        ];
    }

    public static function list(array $partner, string $section, Request $request): array
    {
        self::ensureStoreData($partner);
        self::assertSection($section);

        $rows = self::records($partner, $section);
        $filtered = self::applyFilters($rows, $request);
        $perPage = max(1, min(50, (int) $request->query('per_page', 12)));
        $page = max(1, (int) $request->query('page', 1));

        return [
            'store_id' => $partner['store_id'],
            'section' => $section,
            'rows' => $filtered->forPage($page, $perPage)->values()->all(),
            'all_rows' => $rows->values()->all(),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
            ],
            'statusOptions' => ['all' => 'كل الحالات'] + self::STATUSES,
            'summary' => [
                'total' => $rows->count(),
                'filtered' => $filtered->count(),
                'active' => $rows->where('status_key', 'active')->count(),
                'paused' => $rows->where('status_key', 'paused')->count(),
            ],
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $filtered->count(),
                'last_page' => max(1, (int) ceil($filtered->count() / $perPage)),
                'from' => $filtered->count() === 0 ? 0 : (($page - 1) * $perPage) + 1,
                'to' => min($filtered->count(), $page * $perPage),
            ],
        ];
    }

    public static function find(array $partner, string $section, string $recordId): array
    {
        self::assertSection($section);

        return self::normalize(self::recordForStore($partner, $section, $recordId));
    }

    public static function create(array $partner, string $section, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        self::assertMutableSection($section);
        $recordId = self::prefix($section) . '-' . Str::lower(Str::random(8));
        $payload = self::payload($section, $data) + ['store_id' => $partner['store_id']];

        $record = PlatformRecord::query()->create([
            'section' => $section,
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'] ?? self::STATUSES['active'],
            'payload' => $payload,
        ]);

        self::logActivity($partner, $actor, $section . '_created', $section, $recordId);

        return self::normalize($record);
    }

    public static function update(array $partner, string $section, string $recordId, array $data, ?array $actor = null): array
    {
        self::assertMutableSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $payload = array_merge($record->payload ?? [], self::payload($section, $data), ['updated_at_human' => 'الآن']);

        $record->update(['status' => $payload['status'] ?? $record->status, 'payload' => $payload]);
        self::logActivity($partner, $actor, $section . '_updated', $section, $recordId);

        return self::normalize($record->refresh());
    }

    public static function delete(array $partner, string $section, string $recordId, ?array $actor = null): void
    {
        self::assertMutableSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $record->delete();
        self::logActivity($partner, $actor, $section . '_deleted', $section, $recordId);
    }

    public static function updateStatus(array $partner, string $section, string $recordId, string $status, ?array $actor = null): array
    {
        self::assertSection($section);
        $record = self::recordForStore($partner, $section, $recordId);
        $payload = $record->payload ?? [];
        $payload['status_key'] = $status;
        $payload['status'] = self::STATUSES[$status] ?? $status;
        $payload['updated_at_human'] = 'الآن';

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, $section . '_status_updated', $section, $recordId, ['status' => $status]);

        return self::normalize($record->refresh());
    }

    public static function couponUsage(array $partner, string $couponId): array
    {
        $coupon = self::find($partner, 'marketing_coupons', $couponId);
        $code = Str::lower((string) ($coupon['code'] ?? $coupon['name'] ?? ''));
        $orders = PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->filter(fn (PlatformRecord $order) => Str::lower((string) ($order->payload['coupon_code'] ?? '')) === $code)
            ->map(fn (PlatformRecord $order) => [
                'order_id' => $order->record_id,
                'customer' => $order->payload['customer'] ?? 'عميل',
                'total' => $order->payload['total'] ?? $order->payload['amount'] ?? '0 ر.س',
                'created_at' => $order->payload['created_at'] ?? $order->payload['date'] ?? $order->created_at?->toDateString(),
            ])
            ->values()
            ->all();

        return ['store_id' => $partner['store_id'], 'coupon' => $coupon, 'orders' => $orders, 'used' => count($orders) ?: (int) ($coupon['used'] ?? 0)];
    }

    public static function campaignAnalytics(array $partner, string $campaignId): array
    {
        $campaign = self::find($partner, 'marketing_campaigns', $campaignId);
        $visits = (int) ($campaign['visits'] ?? 0);
        $orders = (int) ($campaign['orders'] ?? 0);

        return [
            'store_id' => $partner['store_id'],
            'campaign' => $campaign,
            'metrics' => [
                'visits' => $visits,
                'orders' => $orders,
                'sales' => $campaign['sales'] ?? '0 ر.س',
                'conversion_rate' => $visits > 0 ? round(($orders / $visits) * 100, 2) . '%' : '0%',
            ],
        ];
    }

    public static function loyalty(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'settings' => self::records($partner, 'marketing_loyalty_settings')->first(),
            'customers' => self::records($partner, 'marketing_loyalty_customers')->values()->all(),
            'transactions' => self::records($partner, 'marketing_loyalty_transactions')->values()->all(),
        ];
    }

    public static function updateLoyaltySettings(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = PlatformRecord::query()
            ->where('section', 'marketing_loyalty_settings')
            ->where('store_id', $partner['store_id'])
            ->firstOrFail();
        $payload = array_merge($record->payload ?? [], [
            'enabled' => (bool) ($data['enabled'] ?? false),
            'points_per_currency' => (int) ($data['points_per_currency'] ?? 1),
            'point_value' => (float) ($data['point_value'] ?? 0.1),
            'levels' => $data['levels'] ?? ['عادي', 'فضي', 'ذهبي', 'VIP'],
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['enabled'] ? 'نشط' : 'متوقف', 'payload' => $payload]);
        self::logActivity($partner, $actor, 'loyalty_settings_updated', 'marketing_loyalty_settings', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function createAbandonedCartCoupon(array $partner, string $cartId, ?array $actor = null): array
    {
        $cart = self::recordForStore($partner, 'abandoned_carts', $cartId);
        $payload = $cart->payload ?? [];
        $coupon = $payload['recovery_coupon'] ?? ('BACK' . now()->format('md'));
        $payload['recovery_coupon'] = $coupon;
        $payload['recovery_action'] = 'تم إنشاء كوبون استرجاع';

        $cart->update(['payload' => $payload]);

        $created = self::create($partner, 'marketing_coupons', [
            'name' => 'استرجاع سلة ' . $cartId,
            'code' => $coupon,
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'status' => 'active',
            'usage_limit' => 1,
            'conditions' => 'سلة متروكة ' . $cartId,
        ], $actor);

        self::logActivity($partner, $actor, 'abandoned_cart_coupon_created', 'abandoned_carts', $cartId, ['coupon' => $coupon]);

        return $created;
    }

    public static function adsReports(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'integrations' => self::records($partner, 'marketing_ads_integrations')->values()->all(),
            'reports' => self::records($partner, 'marketing_ads_reports')->values()->all(),
        ];
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

    private static function applyFilters(Collection $rows, Request $request): Collection
    {
        $query = Str::lower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', 'all'));

        return $rows
            ->filter(fn (array $row) => $query === '' || Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $row) => $status === 'all' || ($row['status_key'] ?? null) === $status)
            ->values();
    }

    private static function normalize(PlatformRecord $record): array
    {
        $payload = $record->payload ?? [];
        $status = $payload['status'] ?? $record->status ?? self::STATUSES['active'];

        return array_merge($payload, [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'name' => $payload['name'] ?? $payload['title'] ?? $payload['code'] ?? $record->record_id,
            'status' => $status,
            'status_key' => $payload['status_key'] ?? self::statusKey($status),
            'created_at' => $payload['created_at'] ?? $record->created_at?->toDateString(),
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function payload(string $section, array $data): array
    {
        return match ($section) {
            'marketing_coupons' => [
                'name' => $data['name'] ?? ($data['code'] ?? 'كوبون'),
                'code' => Str::upper((string) ($data['code'] ?? Str::random(8))),
                'discount_type' => $data['discount_type'] ?? 'percentage',
                'discount_type_label' => self::COUPON_TYPES[$data['discount_type'] ?? 'percentage'] ?? self::COUPON_TYPES['percentage'],
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'conditions' => $data['conditions'] ?? null,
                'minimum_order' => (float) ($data['minimum_order'] ?? 0),
                'starts_at' => $data['starts_at'] ?? now()->toDateString(),
                'ends_at' => $data['ends_at'] ?? now()->addMonth()->toDateString(),
                'usage_limit' => (int) ($data['usage_limit'] ?? 100),
                'used' => (int) ($data['used'] ?? 0),
                'status_key' => $data['status'] ?? 'active',
                'status' => self::STATUSES[$data['status'] ?? 'active'] ?? self::STATUSES['active'],
            ],
            'marketing_campaigns' => [
                'name' => $data['name'] ?? 'حملة تسويقية',
                'type_key' => $data['type'] ?? 'email',
                'type' => self::CAMPAIGN_TYPES[$data['type'] ?? 'email'] ?? self::CAMPAIGN_TYPES['email'],
                'target_audience' => $data['target_audience'] ?? 'كل العملاء',
                'products' => $data['products'] ?? null,
                'coupon_code' => $data['coupon_code'] ?? null,
                'scheduled_at' => $data['scheduled_at'] ?? now()->addDay()->format('Y-m-d H:i'),
                'visits' => (int) ($data['visits'] ?? 0),
                'orders' => (int) ($data['orders'] ?? 0),
                'sales' => self::formatMoney((float) ($data['sales'] ?? 0)),
                'status_key' => $data['status'] ?? 'scheduled',
                'status' => self::STATUSES[$data['status'] ?? 'scheduled'] ?? self::STATUSES['scheduled'],
            ],
            'marketing_bundles' => [
                'name' => $data['name'] ?? 'حزمة تسويقية',
                'products' => $data['products'] ?? null,
                'bundle_price' => self::formatMoney((float) ($data['bundle_price'] ?? 0)),
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'orders' => (int) ($data['orders'] ?? 0),
                'sales' => self::formatMoney((float) ($data['sales'] ?? 0)),
                'status_key' => $data['status'] ?? 'active',
                'status' => self::STATUSES[$data['status'] ?? 'active'] ?? self::STATUSES['active'],
            ],
            'marketing_affiliate_links' => [
                'name' => $data['name'] ?? 'رابط عمولة',
                'marketer' => $data['marketer'] ?? 'مسوق',
                'url' => $data['url'] ?? ('https://solve.test/a/' . Str::lower(Str::random(6))),
                'commission_rate' => (float) ($data['commission_rate'] ?? 10),
                'orders' => (int) ($data['orders'] ?? 0),
                'earnings' => self::formatMoney((float) ($data['earnings'] ?? 0)),
                'status_key' => $data['status'] ?? 'active',
                'status' => self::STATUSES[$data['status'] ?? 'active'] ?? self::STATUSES['active'],
            ],
            'marketing_ads_integrations' => [
                'name' => $data['name'] ?? 'Pixel',
                'provider' => $data['provider'] ?? 'Meta Pixel',
                'pixel_id' => $data['pixel_id'] ?? null,
                'conversions' => (int) ($data['conversions'] ?? 0),
                'spend' => self::formatMoney((float) ($data['spend'] ?? 0)),
                'sales' => self::formatMoney((float) ($data['sales'] ?? 0)),
                'status_key' => $data['status'] ?? 'active',
                'status' => self::STATUSES[$data['status'] ?? 'active'] ?? self::STATUSES['active'],
            ],
            default => [],
        };
    }

    private static function ensureCoupons(array $partner): void
    {
        self::ensureSection($partner, 'marketing_coupons', [
            ['name' => 'خصم الترحيب', 'code' => 'WELCOME10', 'discount_type' => 'percentage', 'discount_value' => 10, 'used' => 12, 'usage_limit' => 100, 'status' => 'active', 'minimum_order' => 150],
            ['name' => 'شحن مجاني', 'code' => 'FREESHIP', 'discount_type' => 'free_shipping', 'discount_value' => 0, 'used' => 6, 'usage_limit' => 50, 'status' => 'active', 'minimum_order' => 250],
        ]);
    }

    private static function ensureCampaigns(array $partner): void
    {
        self::ensureSection($partner, 'marketing_campaigns', [
            ['name' => 'حملة العملاء المتكررين', 'type' => 'whatsapp', 'target_audience' => 'عملاء VIP', 'coupon_code' => 'WELCOME10', 'visits' => 420, 'orders' => 32, 'sales' => 18400, 'status' => 'active'],
            ['name' => 'استرجاع العملاء الخاملين', 'type' => 'email', 'target_audience' => 'غير نشط', 'visits' => 210, 'orders' => 9, 'sales' => 5200, 'status' => 'scheduled'],
        ]);
    }

    private static function ensureBundles(array $partner): void
    {
        $products = collect($partner['products'] ?? [])->pluck('name')->take(3)->implode(', ');
        self::ensureSection($partner, 'marketing_bundles', [
            ['name' => 'باقة الأكثر مبيعا', 'products' => $products, 'bundle_price' => 499, 'discount_value' => 15, 'orders' => 18, 'sales' => 8982, 'status' => 'active'],
        ]);
    }

    private static function ensureLoyalty(array $partner): void
    {
        self::ensureSection($partner, 'marketing_loyalty_settings', [
            ['name' => 'إعدادات الولاء', 'enabled' => true, 'points_per_currency' => 1, 'point_value' => 0.1, 'levels' => ['عادي', 'فضي', 'ذهبي', 'VIP'], 'status' => 'نشط'],
        ]);
        self::ensureSection($partner, 'marketing_loyalty_customers', collect($partner['customers'] ?? [])->take(3)->values()->map(fn (array $customer, int $index) => [
            'customer' => $customer['name'] ?? 'عميل',
            'points' => 250 + ($index * 180),
            'level' => ['عادي', 'فضي', 'ذهبي'][$index] ?? 'VIP',
            'redeemed' => 50 * $index,
            'status' => 'نشط',
        ])->all());
        self::ensureSection($partner, 'marketing_loyalty_transactions', collect($partner['customers'] ?? [])->take(3)->values()->map(fn (array $customer, int $index) => [
            'customer' => $customer['name'] ?? 'عميل',
            'points' => 80 + ($index * 30),
            'type' => $index % 2 === 0 ? 'اكتساب' : 'استبدال',
            'source' => 'طلب متجر',
            'created_at' => now()->subDays($index + 1)->toDateString(),
            'status' => 'مكتمل',
        ])->all());
    }

    private static function ensureAffiliate(array $partner): void
    {
        self::ensureSection($partner, 'marketing_affiliate_links', [
            ['name' => 'رابط المؤثرين', 'marketer' => 'فريق المؤثرين', 'url' => 'https://solve.test/a/atlas', 'commission_rate' => 8, 'orders' => 11, 'earnings' => 1320, 'status' => 'active'],
        ]);
    }

    private static function ensureAds(array $partner): void
    {
        self::ensureSection($partner, 'marketing_ads_integrations', [
            ['name' => 'Meta Pixel', 'provider' => 'Meta Pixel', 'pixel_id' => 'META-' . $partner['store_id'], 'conversions' => 38, 'spend' => 1200, 'sales' => 9600, 'status' => 'active'],
            ['name' => 'TikTok Pixel', 'provider' => 'TikTok Pixel', 'pixel_id' => null, 'conversions' => 0, 'spend' => 0, 'sales' => 0, 'status' => 'paused'],
            ['name' => 'Google Analytics', 'provider' => 'Google Analytics', 'pixel_id' => 'GA-' . $partner['store_id'], 'conversions' => 54, 'spend' => 0, 'sales' => 14200, 'status' => 'active'],
        ]);
        self::ensureSection($partner, 'marketing_ads_reports', [
            ['name' => 'تقرير التحويلات', 'channel' => 'Meta', 'clicks' => 840, 'conversions' => 38, 'sales' => '9,600 ر.س', 'status' => 'نشط'],
        ]);
    }

    private static function ensureSection(array $partner, string $section, array $rows): void
    {
        if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            $payload = in_array($section, ['marketing_coupons', 'marketing_campaigns', 'marketing_bundles', 'marketing_affiliate_links', 'marketing_ads_integrations'], true)
                ? self::payload($section, $row)
                : $row;

            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $section . '-' . $partner['store_id'] . '-' . ($index + 1),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'] ?? $row['status'] ?? null,
                'payload' => $payload + ['store_id' => $partner['store_id']],
            ]);
        }
    }

    private static function recordForStore(array $partner, string $section, string $recordId): PlatformRecord
    {
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available.');

        $record = PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $recordId)
            ->first();

        abort_unless($record, 404);

        return $record;
    }

    private static function assertSection(string $section): void
    {
        abort_unless(in_array($section, [
            'marketing_coupons',
            'marketing_campaigns',
            'marketing_bundles',
            'marketing_loyalty_settings',
            'marketing_loyalty_customers',
            'marketing_loyalty_transactions',
            'marketing_affiliate_links',
            'marketing_ads_integrations',
            'marketing_ads_reports',
        ], true), 404);
    }

    private static function assertMutableSection(string $section): void
    {
        abort_unless(in_array($section, ['marketing_coupons', 'marketing_campaigns', 'marketing_bundles', 'marketing_affiliate_links', 'marketing_ads_integrations'], true), 404);
    }

    private static function prefix(string $section): string
    {
        return match ($section) {
            'marketing_coupons' => 'coupon',
            'marketing_campaigns' => 'campaign',
            'marketing_bundles' => 'bundle',
            'marketing_affiliate_links' => 'affiliate',
            'marketing_ads_integrations' => 'ads',
            default => 'marketing',
        };
    }

    private static function statusKey(string $status): string
    {
        $status = Str::lower($status);

        return match (true) {
            Str::contains($status, ['متوقف', 'paused']) => 'paused',
            Str::contains($status, ['مجدول', 'scheduled']) => 'scheduled',
            Str::contains($status, ['منتهي', 'expired']) => 'expired',
            Str::contains($status, ['مسودة', 'draft']) => 'draft',
            default => 'active',
        };
    }

    private static function money(mixed $value): float
    {
        return (float) preg_replace('/[^\d.]/', '', (string) $value);
    }

    private static function formatMoney(float $value): string
    {
        return number_format($value, 0) . ' ر.س';
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
