<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PartnerCustomers
{
    public const CUSTOMER_STATUSES = [
        'active' => 'نشط',
        'vip' => 'عميل مميز',
        'new' => 'جديد',
        'inactive' => 'غير نشط',
        'blocked' => 'محظور',
    ];

    public const REVIEW_STATUSES = [
        'pending' => 'بانتظار المراجعة',
        'published' => 'منشور',
        'rejected' => 'مرفوض',
    ];

    public const QUESTION_STATUSES = [
        'pending' => 'بانتظار الرد',
        'answered' => 'تم الرد',
        'hidden' => 'مخفي',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerDashboardSummary::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureCustomerGroups($partner);
        self::ensureReviews($partner);
        self::ensureQuestions($partner);
        self::ensureBackInStock($partner);
    }

    public static function list(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $customers = self::customerRecords((string) $partner['store_id']);
        $filtered = self::applyFilters($customers, $request);
        $perPage = max(1, min(50, (int) $request->query('per_page', 12)));
        $page = max(1, (int) $request->query('page', 1));
        $paginated = $filtered->forPage($page, $perPage)->values();

        return [
            'customers' => $paginated->all(),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
                'city' => trim((string) $request->query('city', 'all')),
                'orders' => trim((string) $request->query('orders', 'all')),
            ],
            'statusOptions' => ['all' => 'كل الحالات'] + self::CUSTOMER_STATUSES,
            'cityOptions' => ['all' => 'كل المدن'] + $customers->pluck('city')->filter()->unique()->mapWithKeys(fn (string $city) => [$city => $city])->all(),
            'orderOptions' => [
                'all' => 'كل العملاء',
                'none' => 'بدون طلبات',
                'repeat' => 'أكثر من طلب',
                'vip' => '5 طلبات فأكثر',
            ],
            'summary' => [
                'total' => $customers->count(),
                'filtered' => $filtered->count(),
                'active' => $customers->where('status_key', 'active')->count() + $customers->where('status_key', 'vip')->count(),
                'vip' => $customers->where('status_key', 'vip')->count(),
                'new' => $customers->where('status_key', 'new')->count(),
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

    public static function findForStore(array $partner, string $customerId): array
    {
        self::ensureStoreData($partner);

        $record = self::recordForStore($partner, 'customers', $customerId);

        return array_merge(self::normalizeCustomer($record), [
            'orders' => self::ordersForCustomer($partner, $record),
            'timeline' => self::timelineForCustomer($partner, $record),
        ]);
    }

    public static function update(array $partner, string $customerId, array $data, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customers', $customerId);
        $payload = array_merge($record->payload ?? [], [
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'city' => $data['city'] ?? null,
            'status_key' => $data['status'] ?? 'active',
            'status' => self::CUSTOMER_STATUSES[$data['status'] ?? 'active'] ?? self::CUSTOMER_STATUSES['active'],
            'tags' => self::splitList($data['tags'] ?? ''),
            'updated_at_human' => 'الآن',
        ]);

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'customer_updated', 'customers', $customerId);

        return self::findForStore($partner, $customerId);
    }

    public static function addNote(array $partner, string $customerId, string $body, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customers', $customerId);
        $payload = $record->payload ?? [];
        $notes = $payload['notes'] ?? [];
        $notes[] = [
            'body' => $body,
            'actor' => $actor['name'] ?? 'Partner',
            'created_at' => now()->format('Y-m-d H:i'),
        ];
        $payload['notes'] = $notes;
        $payload['last_activity'] = 'تمت إضافة ملاحظة داخلية';

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'customer_note_added', 'customers', $customerId, ['body' => Str::limit($body, 120)]);

        return self::findForStore($partner, $customerId);
    }

    public static function addTags(array $partner, string $customerId, array|string $tags, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customers', $customerId);
        $payload = $record->payload ?? [];
        $newTags = is_array($tags) ? $tags : self::splitList($tags);
        $payload['tags'] = collect($payload['tags'] ?? [])->merge($newTags)->map(fn ($tag) => trim((string) $tag))->filter()->unique()->values()->all();
        $payload['last_activity'] = 'تم تحديث الوسوم';

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'customer_tags_updated', 'customers', $customerId, ['tags' => $payload['tags']]);

        return self::findForStore($partner, $customerId);
    }

    public static function relatedRows(array $partner, string $section): array
    {
        self::ensureStoreData($partner);
        self::assertCustomerSection($section);

        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', $section)
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::relatedRecord($record))
            ->values()
            ->all();
    }

    public static function createGroup(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        $recordId = 'group-' . Str::lower(Str::random(8));
        $payload = self::groupPayload($data) + ['store_id' => $partner['store_id']];

        $record = PlatformRecord::query()->create([
            'section' => 'customer_groups',
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $payload['status'],
            'payload' => $payload,
        ]);

        self::logActivity($partner, $actor, 'customer_group_created', 'customer_groups', $recordId);

        return self::relatedRecord($record);
    }

    public static function updateGroup(array $partner, string $groupId, array $data, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customer_groups', $groupId);
        $payload = array_merge($record->payload ?? [], self::groupPayload($data));

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'customer_group_updated', 'customer_groups', $groupId);

        return self::relatedRecord($record->refresh());
    }

    public static function deleteGroup(array $partner, string $groupId, ?array $actor = null): void
    {
        $record = self::recordForStore($partner, 'customer_groups', $groupId);
        $record->delete();
        self::logActivity($partner, $actor, 'customer_group_deleted', 'customer_groups', $groupId);
    }

    public static function updateReviewStatus(array $partner, string $reviewId, string $status, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customer_reviews', $reviewId);
        $payload = $record->payload ?? [];
        $payload['status_key'] = $status;
        $payload['status'] = self::REVIEW_STATUSES[$status] ?? self::REVIEW_STATUSES['pending'];
        $payload['moderated_at'] = now()->format('Y-m-d H:i');

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'review_status_updated', 'customer_reviews', $reviewId, ['status' => $status]);

        return self::relatedRecord($record->refresh());
    }

    public static function replyReview(array $partner, string $reviewId, string $reply, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customer_reviews', $reviewId);
        $payload = $record->payload ?? [];
        $payload['reply'] = $reply;
        $payload['reply_by'] = $actor['name'] ?? 'Partner';
        $payload['reply_at'] = now()->format('Y-m-d H:i');

        $record->update(['payload' => $payload]);
        self::logActivity($partner, $actor, 'review_replied', 'customer_reviews', $reviewId);

        return self::relatedRecord($record->refresh());
    }

    public static function replyQuestion(array $partner, string $questionId, string $reply, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customer_questions', $questionId);
        $payload = $record->payload ?? [];
        $payload['answer'] = $reply;
        $payload['answered_by'] = $actor['name'] ?? 'Partner';
        $payload['answered_at'] = now()->format('Y-m-d H:i');
        $payload['status_key'] = 'answered';
        $payload['status'] = self::QUESTION_STATUSES['answered'];

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'question_replied', 'customer_questions', $questionId);

        return self::relatedRecord($record->refresh());
    }

    public static function updateQuestionStatus(array $partner, string $questionId, string $status, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'customer_questions', $questionId);
        $payload = $record->payload ?? [];
        $payload['status_key'] = $status;
        $payload['status'] = self::QUESTION_STATUSES[$status] ?? self::QUESTION_STATUSES['pending'];

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'question_status_updated', 'customer_questions', $questionId, ['status' => $status]);

        return self::relatedRecord($record->refresh());
    }

    public static function notifyBackInStock(array $partner, string $alertId, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'back_in_stock_alerts', $alertId);
        $payload = $record->payload ?? [];
        $payload['status'] = 'تم الإشعار';
        $payload['status_key'] = 'sent';
        $payload['notified_at'] = now()->format('Y-m-d H:i');
        $payload['notification_channel'] = trim((string) ($payload['email'] ?? '')) !== '' ? 'email' : 'sms';

        $record->update(['status' => $payload['status'], 'payload' => $payload]);
        self::logActivity($partner, $actor, 'back_in_stock_notified', 'back_in_stock_alerts', $alertId);

        return self::relatedRecord($record->refresh());
    }

    public static function exportCsv(array $partner, Request $request): Response
    {
        $rows = self::list($partner, $request)['customers'];
        $lines = ["id,name,email,phone,orders,total_spent,status,city,tags,store_id"];

        foreach ($rows as $customer) {
            $lines[] = implode(',', array_map([self::class, 'csv'], [
                $customer['id'],
                $customer['name'],
                $customer['email'],
                $customer['phone'],
                $customer['orders_count'],
                $customer['total_spent'],
                $customer['status'],
                $customer['city'],
                implode('|', $customer['tags'] ?? []),
                $customer['store_id'],
            ]));
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=customers-' . $partner['store_id'] . '-' . now()->format('Ymd-His') . '.csv',
        ]);
    }

    private static function customerRecords(string $storeId): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', 'customers')
            ->where('store_id', $storeId)
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::normalizeCustomer($record));
    }

    private static function normalizeCustomer(PlatformRecord $record): array
    {
        $payload = $record->payload ?? [];
        $orders = (int) ($payload['orders_count'] ?? $payload['orders'] ?? 0);
        $spent = $payload['total_spent'] ?? $payload['spent'] ?? '0 ر.س';
        $statusKey = $payload['status_key'] ?? self::statusKeyFromCustomer($orders, $spent, $record->status);
        $status = self::CUSTOMER_STATUSES[$statusKey] ?? ($payload['status'] ?? self::CUSTOMER_STATUSES['active']);

        return array_merge($payload, [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'name' => $payload['name'] ?? $payload['customer'] ?? 'عميل',
            'email' => $payload['email'] ?? 'customer@example.test',
            'phone' => $payload['phone'] ?? $payload['mobile'] ?? '966500000000',
            'orders_count' => $orders,
            'total_spent' => $spent,
            'average_order_value' => self::formatMoney($orders > 0 ? self::money($spent) / $orders : 0),
            'last_order' => $payload['last_order'] ?? $payload['last_order_at'] ?? $record->updated_at?->toDateString(),
            'last_activity' => $payload['last_activity'] ?? $record->updated_at?->diffForHumans(),
            'city' => $payload['city'] ?? self::cityFromIndex((int) $record->id),
            'status_key' => $statusKey,
            'status' => $status,
            'tags' => $payload['tags'] ?? self::defaultTags($orders),
            'addresses' => $payload['addresses'] ?? [[
                'label' => 'العنوان الرئيسي',
                'city' => $payload['city'] ?? self::cityFromIndex((int) $record->id),
                'address' => $payload['address'] ?? 'لم يحدد العميل عنوانا مفصلا بعد',
            ]],
            'notes' => $payload['notes'] ?? [],
            'created_at' => $payload['created_at'] ?? $record->created_at?->toDateString(),
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function applyFilters(Collection $customers, Request $request): Collection
    {
        $query = Str::lower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', 'all'));
        $city = trim((string) $request->query('city', 'all'));
        $orders = trim((string) $request->query('orders', 'all'));

        return $customers
            ->filter(fn (array $customer) => $query === '' || Str::contains(Str::lower(json_encode($customer, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $customer) => $status === 'all' || ($customer['status_key'] ?? null) === $status)
            ->filter(fn (array $customer) => $city === 'all' || ($customer['city'] ?? null) === $city)
            ->filter(fn (array $customer) => match ($orders) {
                'none' => (int) ($customer['orders_count'] ?? 0) === 0,
                'repeat' => (int) ($customer['orders_count'] ?? 0) > 1,
                'vip' => (int) ($customer['orders_count'] ?? 0) >= 5,
                default => true,
            })
            ->values();
    }

    private static function ordersForCustomer(array $partner, PlatformRecord $customer): array
    {
        $normalized = self::normalizeCustomer($customer);

        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->latest()
            ->get()
            ->filter(function (PlatformRecord $order) use ($normalized) {
                $payload = $order->payload ?? [];
                $haystack = Str::lower(json_encode([
                    $payload['customer'] ?? '',
                    $payload['email'] ?? '',
                    $payload['phone'] ?? '',
                    $payload['customer_phone'] ?? '',
                ], JSON_UNESCAPED_UNICODE));

                return Str::contains($haystack, Str::lower((string) $normalized['name']))
                    || Str::contains($haystack, Str::lower((string) $normalized['email']))
                    || Str::contains($haystack, Str::lower((string) $normalized['phone']));
            })
            ->map(fn (PlatformRecord $order) => [
                'id' => $order->record_id,
                'order_number' => $order->payload['order_number'] ?? $order->record_id,
                'total' => $order->payload['total'] ?? $order->payload['amount'] ?? '0 ر.س',
                'status' => $order->payload['status'] ?? $order->status,
                'created_at' => $order->payload['created_at'] ?? $order->payload['date'] ?? $order->created_at?->toDateString(),
            ])
            ->values()
            ->all();
    }

    private static function timelineForCustomer(array $partner, PlatformRecord $customer): array
    {
        $normalized = self::normalizeCustomer($customer);
        $orders = self::ordersForCustomer($partner, $customer);
        $timeline = [
            ['label' => 'تم إنشاء ملف العميل', 'time' => $normalized['created_at'], 'type' => 'system'],
            ['label' => 'آخر نشاط: ' . $normalized['last_activity'], 'time' => $normalized['last_order'], 'type' => 'activity'],
        ];

        foreach (array_slice($orders, 0, 5) as $order) {
            $timeline[] = [
                'label' => 'طلب ' . $order['order_number'] . ' - ' . $order['status'],
                'time' => $order['created_at'],
                'type' => 'order',
            ];
        }

        foreach (($normalized['notes'] ?? []) as $note) {
            $timeline[] = [
                'label' => 'ملاحظة: ' . Str::limit((string) ($note['body'] ?? ''), 80),
                'time' => $note['created_at'] ?? now()->format('Y-m-d H:i'),
                'type' => 'note',
            ];
        }

        return $timeline;
    }

    private static function ensureCustomerGroups(array $partner): void
    {
        self::ensureSection($partner, 'customer_groups', [
            ['name' => 'عملاء VIP', 'condition_type' => 'orders_count', 'condition_value' => '5', 'status' => 'نشط', 'customers_count' => 0, 'campaigns_count' => 1],
            ['name' => 'عملاء الرياض', 'condition_type' => 'city', 'condition_value' => 'الرياض', 'status' => 'نشط', 'customers_count' => 0, 'campaigns_count' => 2],
        ]);
    }

    private static function ensureReviews(array $partner): void
    {
        $products = $partner['products'] ?? [];
        $customers = $partner['customers'] ?? [];

        self::ensureSection($partner, 'customer_reviews', collect($customers)->take(3)->values()->map(function (array $customer, int $index) use ($products) {
            $product = $products[$index % max(1, count($products))] ?? [];

            return [
                'customer' => $customer['name'] ?? 'عميل',
                'product' => $product['name'] ?? 'منتج',
                'stars' => 5 - min($index, 2),
                'body' => $index === 0 ? 'جودة ممتازة وسرعة في التوصيل.' : 'تجربة جيدة وتحتاج متابعة بسيطة.',
                'status_key' => $index === 0 ? 'published' : 'pending',
                'status' => $index === 0 ? self::REVIEW_STATUSES['published'] : self::REVIEW_STATUSES['pending'],
                'created_at' => now()->subDays($index + 1)->toDateString(),
            ];
        })->all());
    }

    private static function ensureQuestions(array $partner): void
    {
        $products = $partner['products'] ?? [];
        $customers = $partner['customers'] ?? [];

        self::ensureSection($partner, 'customer_questions', collect($customers)->take(3)->values()->map(function (array $customer, int $index) use ($products) {
            $product = $products[$index % max(1, count($products))] ?? [];

            return [
                'customer' => $customer['name'] ?? 'عميل',
                'product' => $product['name'] ?? 'منتج',
                'question' => $index === 0 ? 'هل المنتج متوفر بلون آخر؟' : 'كم مدة التوصيل؟',
                'status_key' => 'pending',
                'status' => self::QUESTION_STATUSES['pending'],
                'created_at' => now()->subDays($index + 1)->toDateString(),
            ];
        })->all());
    }

    private static function ensureBackInStock(array $partner): void
    {
        $products = $partner['products'] ?? [];
        $customers = $partner['customers'] ?? [];

        self::ensureSection($partner, 'back_in_stock_alerts', collect($customers)->take(3)->values()->map(function (array $customer, int $index) use ($products) {
            $product = $products[$index % max(1, count($products))] ?? [];

            return [
                'customer' => $customer['name'] ?? 'زائر',
                'email' => $customer['email'] ?? null,
                'phone' => $customer['phone'] ?? '966500000000',
                'product' => $product['name'] ?? 'منتج',
                'sku' => $product['sku'] ?? null,
                'requested_at' => now()->subDays($index + 2)->toDateString(),
                'status_key' => 'waiting',
                'status' => 'بانتظار توفر المخزون',
            ];
        })->all());
    }

    private static function ensureSection(array $partner, string $section, array $rows): void
    {
        if (PlatformRecord::query()->where('section', $section)->where('store_id', $partner['store_id'])->exists()) {
            return;
        }

        foreach ($rows as $index => $row) {
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $section . '-' . $partner['store_id'] . '-' . ($index + 1),
                'store_id' => $partner['store_id'],
                'partner_id' => $partner['id'] ?? null,
                'status' => $row['status'] ?? null,
                'payload' => $row + ['store_id' => $partner['store_id']],
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

    private static function relatedRecord(PlatformRecord $record): array
    {
        return array_merge($record->payload ?? [], [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'status' => $record->status ?? ($record->payload['status'] ?? null),
            'updated_at_human' => $record->updated_at?->diffForHumans(),
        ]);
    }

    private static function groupPayload(array $data): array
    {
        return [
            'name' => $data['name'],
            'condition_type' => $data['condition_type'] ?? 'orders_count',
            'condition_value' => $data['condition_value'] ?? '',
            'status' => $data['status'] ?? 'نشط',
            'customers_count' => (int) ($data['customers_count'] ?? 0),
            'campaigns_count' => (int) ($data['campaigns_count'] ?? 0),
            'linked_campaign' => $data['linked_campaign'] ?? null,
            'updated_at_human' => 'الآن',
        ];
    }

    private static function assertCustomerSection(string $section): void
    {
        abort_unless(in_array($section, ['customer_groups', 'customer_reviews', 'customer_questions', 'back_in_stock_alerts'], true), 404);
    }

    private static function statusKeyFromCustomer(int $orders, string $spent, ?string $status): string
    {
        $status = Str::lower((string) $status);

        return match (true) {
            Str::contains($status, ['محظور', 'blocked']) => 'blocked',
            $orders >= 5 || self::money($spent) >= 3000 => 'vip',
            $orders === 0 => 'new',
            $orders <= 1 => 'active',
            default => 'active',
        };
    }

    private static function defaultTags(int $orders): array
    {
        return $orders >= 5 ? ['VIP', 'متكرر'] : ($orders === 0 ? ['جديد'] : ['عميل']);
    }

    private static function splitList(array|string|null $value): array
    {
        if (is_array($value)) {
            return collect($value)->map(fn ($item) => trim((string) $item))->filter()->values()->all();
        }

        return collect(explode(',', (string) $value))->map(fn (string $item) => trim($item))->filter()->values()->all();
    }

    private static function cityFromIndex(int $index): string
    {
        $cities = ['الرياض', 'جدة', 'الدمام', 'مكة', 'الخبر'];

        return $cities[$index % count($cities)];
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

    private static function csv(mixed $value): string
    {
        $value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
        $value = preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
