<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PartnerServices
{
    public const STATUSES = [
        'enabled' => 'مفعلة',
        'disabled' => 'غير مفعلة',
        'needs_setup' => 'تحتاج إعداد',
        'admin_paused' => 'موقوفة من الأدمن',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerOrders::ensureStoreData($partner);
        PartnerMarketing::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureCatalog($partner);
        self::ensureLogistics($partner);
        self::ensurePaymentGateways($partner);
        self::ensureWhatsapp($partner);
        self::ensureFinancing($partner);
        self::ensureGrowth($partner);
    }

    public static function summary(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $catalog = self::filtered(self::records($partner, 'partner_services'), $request);
        $alerts = $catalog
            ->filter(fn (array $row) => in_array($row['status_key'] ?? '', ['needs_setup', 'admin_paused'], true))
            ->map(fn (array $row) => ['title' => $row['name'], 'body' => $row['status'] . ' · ' . ($row['help_tip'] ?? 'أكمل الإعداد من صفحة الخدمة')])
            ->values()
            ->all();

        return [
            'store_id' => $partner['store_id'],
            'plan' => $partner['plan'] ?? 'Starter',
            'services' => $catalog->values()->all(),
            'counts' => [
                'total' => $catalog->count(),
                'enabled' => $catalog->where('status_key', 'enabled')->count(),
                'needs_setup' => $catalog->where('status_key', 'needs_setup')->count(),
                'admin_paused' => $catalog->where('status_key', 'admin_paused')->count(),
            ],
            'alerts' => $alerts,
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
            ],
            'statusOptions' => ['all' => 'كل الحالات'] + self::STATUSES,
            'quickActions' => [
                ['label' => 'إعداد الشحن', 'route' => 'partner.services.logistics'],
                ['label' => 'إعداد الدفع', 'route' => 'partner.services.payment-gateways'],
                ['label' => 'ربط واتساب', 'route' => 'partner.services.whatsapp'],
                ['label' => 'توصيات النمو', 'route' => 'partner.services.growth'],
            ],
        ];
    }

    public static function service(array $partner, string $id): array
    {
        self::ensureStoreData($partner);

        return self::normalize(self::recordForStore($partner, 'partner_services', $id));
    }

    public static function updateServiceStatus(array $partner, string $id, string $status, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'partner_services', $id);
        $payload = $record->payload ?? [];
        abort_if(($payload['status_key'] ?? null) === 'admin_paused' && $status === 'enabled', 403, 'Service is paused by admin.');

        $payload['status_key'] = $status;
        $payload['status'] = self::STATUSES[$status] ?? $status;
        $payload['updated_at_human'] = 'الآن';
        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'service_status_updated', 'partner_services', $id, ['status' => $status]);

        return self::normalize($record->refresh());
    }

    public static function testService(array $partner, string $id, ?array $actor = null): array
    {
        $service = self::service($partner, $id);
        $ok = ($service['status_key'] ?? '') !== 'admin_paused';
        self::logActivity($partner, $actor, 'service_connection_tested', 'partner_services', $id, ['success' => $ok]);

        return [
            'store_id' => $partner['store_id'],
            'service' => $service,
            'success' => $ok,
            'message' => $ok ? 'تم اختبار الاتصال بنجاح.' : 'الخدمة موقوفة من الأدمن ولا يمكن اختبارها.',
            'tested_at' => now()->toIso8601String(),
        ];
    }

    public static function typed(array $partner, string $type, Request $request): array
    {
        self::ensureStoreData($partner);
        $section = self::sectionFor($type);
        $rows = self::filtered(self::records($partner, $section), $request);

        return [
            'store_id' => $partner['store_id'],
            'type' => $type,
            'rows' => $rows->values()->all(),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
            ],
            'statusOptions' => ['all' => 'كل الحالات'] + self::STATUSES,
            'summary' => [
                'total' => $rows->count(),
                'enabled' => $rows->where('status_key', 'enabled')->count(),
                'needs_setup' => $rows->where('status_key', 'needs_setup')->count(),
            ],
        ];
    }

    public static function updateTypedSettings(array $partner, string $type, string $id, array $data, ?array $actor = null): array
    {
        $section = self::sectionFor($type);
        $record = self::recordForStore($partner, $section, $id);
        $payload = array_merge($record->payload ?? [], self::settingsPayload($type, $data), ['updated_at_human' => 'الآن']);
        abort_if(($record->payload['status_key'] ?? null) === 'admin_paused', 403, 'Service is paused by admin.');

        $record->update(['status' => $payload['status'] ?? $record->status, 'payload' => self::maskPayload($payload)]);
        self::syncCatalogStatus($partner, $type, $payload['status_key'] ?? null);
        self::logActivity($partner, $actor, $type . '_settings_updated', $section, $id);

        return self::normalize($record->refresh());
    }

    public static function testTyped(array $partner, string $type, string $id, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, self::sectionFor($type), $id);
        $row = self::normalize($record);
        $success = ($row['status_key'] ?? '') !== 'admin_paused' && ! empty($row['api_key_masked'] ?? $row['access_token_masked'] ?? $row['provider'] ?? $row['name']);
        $payload = array_merge($record->payload ?? [], [
            'last_test' => $success ? 'ناجح' : 'فشل',
            'last_test_at' => now()->toDateTimeString(),
        ]);
        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, $type . '_connection_tested', $record->section, $id, ['success' => $success]);

        return [
            'store_id' => $partner['store_id'],
            'success' => $success,
            'message' => $success ? 'الاتصال يعمل بشكل صحيح.' : 'أكمل مفاتيح الربط أو راجع حالة الخدمة.',
            'service' => self::normalize($record->refresh()),
        ];
    }

    public static function status(array $partner, string $type, string $id): array
    {
        $row = self::normalize(self::recordForStore($partner, self::sectionFor($type), $id));

        return [
            'store_id' => $partner['store_id'],
            'id' => $id,
            'status' => $row['status'] ?? null,
            'status_key' => $row['status_key'] ?? null,
            'last_test' => $row['last_test'] ?? null,
            'last_test_at' => $row['last_test_at'] ?? null,
        ];
    }

    public static function whatsapp(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'settings' => self::records($partner, 'service_whatsapp_settings')->first(),
            'logs' => self::records($partner, 'service_whatsapp_logs')->take(20)->values()->all(),
        ];
    }

    public static function updateWhatsapp(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'service_whatsapp_settings');
        $payload = array_merge($record->payload ?? [], [
            'business_number' => $data['business_number'] ?? ($record->payload['business_number'] ?? null),
            'access_token_masked' => self::maskSecret($data['access_token'] ?? $record->payload['access_token_masked'] ?? null),
            'order_confirmation_template' => $data['order_confirmation_template'] ?? null,
            'order_status_template' => $data['order_status_template'] ?? null,
            'abandoned_cart_template' => $data['abandoned_cart_template'] ?? null,
            'back_in_stock_template' => $data['back_in_stock_template'] ?? null,
            'status_key' => 'enabled',
            'status' => self::STATUSES['enabled'],
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::syncCatalogStatus($partner, 'whatsapp', 'enabled');
        self::logActivity($partner, $actor, 'whatsapp_settings_updated', 'service_whatsapp_settings', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function testWhatsapp(array $partner, ?array $actor = null): array
    {
        $settings = self::whatsapp($partner)['settings'];
        $success = ! empty($settings['business_number']);
        PlatformRecord::query()->create([
            'section' => 'service_whatsapp_logs',
            'record_id' => 'wa-log-' . Str::lower(Str::random(8)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $success ? 'تم الإرسال' : 'فشل',
            'payload' => [
                'name' => 'رسالة اختبار',
                'template' => 'test',
                'recipient' => $settings['business_number'] ?? '-',
                'status' => $success ? 'تم الإرسال' : 'فشل',
                'status_key' => $success ? 'enabled' : 'needs_setup',
                'created_at' => now()->toDateTimeString(),
                'store_id' => $partner['store_id'],
            ],
        ]);
        self::logActivity($partner, $actor, 'whatsapp_test_sent', 'service_whatsapp_settings', $settings['id'] ?? 'whatsapp');

        return ['store_id' => $partner['store_id'], 'success' => $success, 'message' => $success ? 'تم إرسال رسالة الاختبار.' : 'أكمل رقم واتساب أولا.'];
    }

    public static function financing(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'settings' => self::records($partner, 'service_financing_settings')->first(),
            'requests' => self::records($partner, 'service_financing_requests')->values()->all(),
        ];
    }

    public static function updateFinancing(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $record = self::singleRecord($partner, 'service_financing_settings');
        $payload = array_merge($record->payload ?? [], [
            'provider' => $data['provider'] ?? 'تمارا للأعمال',
            'enabled' => (bool) ($data['enabled'] ?? false),
            'min_order_total' => (float) ($data['min_order_total'] ?? 0),
            'max_installments' => (int) ($data['max_installments'] ?? 4),
            'terms' => $data['terms'] ?? null,
            'status_key' => ! empty($data['enabled']) ? 'enabled' : 'disabled',
            'status' => ! empty($data['enabled']) ? self::STATUSES['enabled'] : self::STATUSES['disabled'],
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::syncCatalogStatus($partner, 'financing', $payload['status_key']);
        self::logActivity($partner, $actor, 'financing_settings_updated', 'service_financing_settings', $record->record_id);

        return self::normalize($record->refresh());
    }

    public static function updateFinancingRequest(array $partner, string $id, string $status, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'service_financing_requests', $id);
        $payload = array_merge($record->payload ?? [], [
            'request_status' => $status,
            'status' => $status,
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $status, 'payload' => $payload]);
        self::logActivity($partner, $actor, 'financing_request_status_updated', 'service_financing_requests', $id, ['status' => $status]);

        return self::normalize($record->refresh());
    }

    public static function growth(array $partner): array
    {
        self::ensureStoreData($partner);

        return [
            'store_id' => $partner['store_id'],
            'tools' => self::records($partner, 'service_growth_tools')->values()->all(),
            'recommendations' => self::records($partner, 'service_growth_recommendations')->values()->all(),
        ];
    }

    private static function ensureCatalog(array $partner): void
    {
        self::ensureSection($partner, 'partner_services', [
            ['id' => 'logistics', 'name' => 'اللوجستيات', 'type' => 'logistics', 'plan' => 'Starter', 'status_key' => 'enabled', 'provider' => $partner['shipping_provider'] ?? 'Aramex', 'help_tip' => 'اربط شركات الشحن وحدد مناطق وأسعار الشحن.'],
            ['id' => 'payment-gateways', 'name' => 'بوابات الدفع', 'type' => 'payment-gateways', 'plan' => 'Starter', 'status_key' => 'enabled', 'provider' => $partner['payment_provider'] ?? 'Mada', 'help_tip' => 'أدخل مفاتيح الإنتاج ولا تشاركها مع أي طرف.'],
            ['id' => 'whatsapp', 'name' => 'واتساب', 'type' => 'whatsapp', 'plan' => 'Growth', 'status_key' => 'needs_setup', 'provider' => 'WhatsApp Business', 'help_tip' => 'أكمل رقم الأعمال وقوالب الرسائل.'],
            ['id' => 'financing', 'name' => 'التمويل', 'type' => 'financing', 'plan' => 'Enterprise', 'status_key' => 'disabled', 'provider' => 'تمارا للأعمال', 'help_tip' => 'فعّل التمويل للطلبات أو الاشتراكات.'],
            ['id' => 'growth', 'name' => 'النمو', 'type' => 'growth', 'plan' => 'Growth', 'status_key' => 'enabled', 'provider' => 'Solve Growth', 'help_tip' => 'توصيات SEO وتحسين التحويل من بيانات المتجر.'],
        ], true);
    }

    private static function ensureLogistics(array $partner): void
    {
        self::ensureSection($partner, 'service_logistics', [
            ['name' => 'Aramex', 'provider' => 'Aramex', 'api_key_masked' => self::maskSecret('atlas-aramex-key'), 'regions' => 'الرياض, جدة, الدمام', 'shipping_rates' => 'داخلية 25 ر.س', 'orders_linked' => self::countRecords($partner, 'shipments'), 'status_key' => 'enabled'],
            ['name' => 'SMSA', 'provider' => 'SMSA', 'api_key_masked' => null, 'regions' => 'كل المدن', 'shipping_rates' => 'حسب الوزن', 'orders_linked' => 0, 'status_key' => 'needs_setup'],
        ]);
    }

    private static function ensurePaymentGateways(array $partner): void
    {
        self::ensureSection($partner, 'service_payment_gateways', [
            ['name' => 'Mada', 'provider' => 'Mada', 'mode' => 'production', 'api_key_masked' => self::maskSecret('mada-production-key'), 'payments_linked' => self::countRecords($partner, 'payments'), 'status_key' => 'enabled'],
            ['name' => 'Apple Pay', 'provider' => 'Apple Pay', 'mode' => 'test', 'api_key_masked' => null, 'payments_linked' => 0, 'status_key' => 'needs_setup'],
        ]);
    }

    private static function ensureWhatsapp(array $partner): void
    {
        self::ensureSection($partner, 'service_whatsapp_settings', [[
            'name' => 'WhatsApp Business',
            'business_number' => $partner['phone'] ?? null,
            'access_token_masked' => null,
            'order_confirmation_template' => 'تم استلام طلبك {{order_id}}',
            'order_status_template' => 'تم تحديث حالة طلبك إلى {{status}}',
            'abandoned_cart_template' => 'سلتك بانتظارك',
            'back_in_stock_template' => 'المنتج متوفر الآن',
            'status_key' => 'needs_setup',
        ]]);
        self::ensureSection($partner, 'service_whatsapp_logs', [[
            'name' => 'تأكيد طلب تجريبي',
            'template' => 'order_confirmation',
            'recipient' => $partner['phone'] ?? '-',
            'status' => 'تم الإرسال',
            'status_key' => 'enabled',
            'created_at' => now()->subDay()->toDateTimeString(),
        ]]);
    }

    private static function ensureFinancing(array $partner): void
    {
        self::ensureSection($partner, 'service_financing_settings', [[
            'name' => 'إعدادات التمويل',
            'provider' => 'تمارا للأعمال',
            'enabled' => false,
            'min_order_total' => 500,
            'max_installments' => 4,
            'terms' => 'تمويل للطلبات المؤهلة فقط.',
            'status_key' => 'disabled',
        ]]);
        self::ensureSection($partner, 'service_financing_requests', [[
            'name' => 'طلب تمويل اشتراك',
            'customer' => $partner['owner'] ?? 'التاجر',
            'amount' => '5,000 ر.س',
            'request_status' => 'بانتظار المراجعة',
            'status' => 'بانتظار المراجعة',
            'created_at' => now()->subDays(2)->toDateString(),
        ]]);
    }

    private static function ensureGrowth(array $partner): void
    {
        self::ensureSection($partner, 'service_growth_tools', [
            ['name' => 'SEO Booster', 'category' => 'SEO', 'impact' => 'تحسين الأرشفة', 'status_key' => 'enabled'],
            ['name' => 'Conversion Checklist', 'category' => 'CRO', 'impact' => 'رفع التحويل', 'status_key' => 'enabled'],
        ]);
        self::ensureSection($partner, 'service_growth_recommendations', [
            ['name' => 'أضف بنرات موسمية', 'category' => 'واجهة المتجر', 'priority' => 'عالية', 'source' => 'بيانات البنرات والتحليلات', 'status' => 'مفتوحة'],
            ['name' => 'فعّل تذكير السلات المتروكة', 'category' => 'تسويق', 'priority' => 'متوسطة', 'source' => 'السلات المتروكة', 'status' => 'مفتوحة'],
        ]);
    }

    private static function ensureSection(array $partner, string $section, array $rows, bool $useGivenId = false): void
    {
        if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            $statusKey = $row['status_key'] ?? 'enabled';
            $payload = $row + [
                'status_key' => $statusKey,
                'status' => self::STATUSES[$statusKey] ?? ($row['status'] ?? $statusKey),
                'store_id' => $partner['store_id'],
            ];
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $useGivenId ? $row['id'] : $section . '-' . $partner['store_id'] . '-' . ($index + 1),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'],
                'payload' => $payload,
            ]);
        }
    }

    private static function sectionFor(string $type): string
    {
        return match ($type) {
            'logistics' => 'service_logistics',
            'payment-gateways' => 'service_payment_gateways',
            default => abort(404),
        };
    }

    private static function settingsPayload(string $type, array $data): array
    {
        $status = $data['status'] ?? 'enabled';

        return match ($type) {
            'logistics' => [
                'provider' => $data['provider'] ?? null,
                'api_key_masked' => self::maskSecret($data['api_key'] ?? null),
                'regions' => $data['regions'] ?? null,
                'shipping_rates' => $data['shipping_rates'] ?? null,
                'status_key' => $status,
                'status' => self::STATUSES[$status] ?? $status,
            ],
            'payment-gateways' => [
                'provider' => $data['provider'] ?? null,
                'api_key_masked' => self::maskSecret($data['api_key'] ?? null),
                'mode' => $data['mode'] ?? 'test',
                'status_key' => $status,
                'status' => self::STATUSES[$status] ?? $status,
            ],
            default => [],
        };
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
        $statusKey = $payload['status_key'] ?? self::statusKey((string) ($payload['status'] ?? $record->status ?? 'enabled'));

        return array_merge($payload, [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'name' => $payload['name'] ?? $payload['provider'] ?? $record->record_id,
            'status_key' => $statusKey,
            'status' => $payload['status'] ?? self::STATUSES[$statusKey] ?? $record->status,
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function recordForStore(array $partner, string $section, string $id): PlatformRecord
    {
        abort_unless(Schema::hasTable('platform_records'), 503);
        $record = PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->where('record_id', $id)->first();
        abort_unless($record, 404);

        return $record;
    }

    private static function singleRecord(array $partner, string $section): PlatformRecord
    {
        return PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->firstOrFail();
    }

    private static function syncCatalogStatus(array $partner, string $type, ?string $status): void
    {
        if (! $status) {
            return;
        }
        $catalog = PlatformRecord::query()->where('section', 'partner_services')->where('store_id', $partner['store_id'])->where('record_id', $type)->first();
        if (! $catalog || ($catalog->payload['status_key'] ?? null) === 'admin_paused') {
            return;
        }
        $payload = $catalog->payload ?? [];
        $payload['status_key'] = $status;
        $payload['status'] = self::STATUSES[$status] ?? $status;
        $catalog->update(['status' => $payload['status'], 'payload' => $payload]);
    }

    private static function statusKey(string $status): string
    {
        return match (true) {
            Str::contains($status, ['موقوفة', 'admin_paused']) => 'admin_paused',
            Str::contains($status, ['تحتاج', 'needs_setup']) => 'needs_setup',
            Str::contains($status, ['غير', 'disabled']) => 'disabled',
            default => 'enabled',
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

    private static function countRecords(array $partner, string $section): int
    {
        if (! Schema::hasTable('platform_records')) {
            return 0;
        }

        return PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->count();
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
