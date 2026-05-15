<?php

namespace App\Support;

use App\Models\PlatformActivityLog;
use App\Models\PlatformRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PartnerOrders
{
    public const ORDER_STATUSES = [
        'new' => 'جديد',
        'processing' => 'جاري التجهيز',
        'ready' => 'جاهز',
        'delivery' => 'جاري التوصيل',
        'completed' => 'مكتمل',
        'cancelled' => 'ملغي',
        'return_requested' => 'قيد الاسترجاع',
        'returned' => 'مسترجع',
    ];

    public const PAYMENT_STATUSES = [
        'paid' => 'مدفوع',
        'unpaid' => 'غير مدفوع',
        'pending' => 'بانتظار الدفع',
        'refunded' => 'مسترجع',
    ];

    public static function ensureStoreData(array $partner): void
    {
        PartnerDashboardSummary::ensureStoreData($partner);

        if (! Schema::hasTable('platform_records')) {
            return;
        }

        self::ensureAbandonedCarts($partner);
        self::ensureReturns($partner);
        self::ensureShipments($partner);
    }

    public static function list(array $partner, Request $request): array
    {
        self::ensureStoreData($partner);

        $orders = self::orderRecords((string) $partner['store_id']);
        $filtered = self::applyFilters($orders, $request);
        $perPage = max(1, min(50, (int) $request->query('per_page', 10)));
        $page = max(1, (int) $request->query('page', 1));
        $paginated = $filtered->forPage($page, $perPage)->values();

        return [
            'orders' => $paginated->all(),
            'counts' => self::statusCounts($orders),
            'filters' => [
                'q' => trim((string) $request->query('q', '')),
                'status' => trim((string) $request->query('status', 'all')),
                'payment_status' => trim((string) $request->query('payment_status', 'all')),
                'shipping_status' => trim((string) $request->query('shipping_status', 'all')),
                'date_from' => trim((string) $request->query('date_from', '')),
                'date_to' => trim((string) $request->query('date_to', '')),
            ],
            'statusOptions' => ['all' => 'الكل'] + self::ORDER_STATUSES,
            'paymentOptions' => ['all' => 'كل المدفوعات'] + self::PAYMENT_STATUSES,
            'shippingOptions' => [
                'all' => 'كل الشحنات',
                'normal' => 'عادي',
                'fast' => 'سريع',
                'pickup' => 'استلام',
            ],
            'summary' => [
                'total' => $orders->count(),
                'filtered' => $filtered->count(),
                'total_sales' => self::formatMoney($filtered->sum(fn (array $order) => self::money($order['total'] ?? 0))),
                'pending' => $orders->filter(fn (array $order) => self::statusKey($order) !== 'completed')->count(),
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

    public static function findForStore(array $partner, string $orderId): array
    {
        self::ensureStoreData($partner);

        abort_unless(Schema::hasTable('platform_records'), 404);

        $record = PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $orderId)
            ->first();

        abort_unless($record, 404);

        return self::normalizeOrder($record);
    }

    public static function updateStatus(array $partner, string $orderId, string $statusKey, ?array $actor = null): array
    {
        abort_unless(isset(self::ORDER_STATUSES[$statusKey]), 422);
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available. Run the platform migrations before updating orders.');

        $record = PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $orderId)
            ->first();

        abort_unless($record, 404);

        $payload = $record->payload ?? [];
        $history = $payload['timeline'] ?? [];
        $label = self::ORDER_STATUSES[$statusKey];

        $history[] = [
            'label' => 'تم تحديث الحالة إلى ' . $label,
            'time' => now()->format('Y-m-d H:i'),
            'state' => 'done',
        ];

        $payload['status_key'] = $statusKey;
        $payload['status'] = $label;
        $payload['timeline'] = $history;
        $payload['updated_at_human'] = 'الآن';

        $record->update([
            'status' => $label,
            'payload' => $payload,
        ]);

        self::logActivity($partner, $actor, 'order_status_updated', 'orders', $record->record_id, [
            'status' => $label,
            'status_key' => $statusKey,
        ]);

        return self::normalizeOrder($record->refresh());
    }

    public static function createManual(array $partner, array $data, ?array $actor = null): array
    {
        self::ensureStoreData($partner);
        abort_unless(Schema::hasTable('platform_records'), 503, 'platform_records table is not available. Run the platform migrations before creating orders.');
        if (SubscriptionManager::limitReached($partner, 'orders')) {
            SubscriptionManager::recordUsageDenied($partner, $actor, 'orders');
            abort(402, 'Order limit reached for the current subscription plan.');
        }

        $recordId = 'manual-' . Str::lower(Str::random(8));
        $total = (float) ($data['total'] ?? 0);
        $unitPrice = (float) ($data['unit_price'] ?? $total);
        $discount = (float) ($data['discount'] ?? 0);
        $shippingFee = (float) ($data['shipping_fee'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $customer = trim((string) $data['customer']);
        $phone = trim((string) ($data['phone'] ?? ''));

        $record = PlatformRecord::query()->create([
            'section' => 'orders',
            'record_id' => $recordId,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => self::ORDER_STATUSES['new'],
            'payload' => [
                'id' => $recordId,
                'order_number' => 'MAN-' . now()->format('ymd') . '-' . Str::upper(Str::random(4)),
                'store_id' => $partner['store_id'],
                'store' => $partner['name'],
                'source' => 'لوحة التحكم',
                'customer' => $customer,
                'phone' => $phone,
                'customer_email' => $data['email'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'source_channel' => $data['source_channel'] ?? 'لوحة التحكم',
                'fulfillment_priority' => $data['fulfillment_priority'] ?? 'normal',
                'product_id' => $data['product_id'] ?? null,
                'product_sku' => $data['product_sku'] ?? null,
                'status_key' => 'new',
                'status' => self::ORDER_STATUSES['new'],
                'payment_status_key' => $data['payment_status'] ?? 'unpaid',
                'payment_status' => self::PAYMENT_STATUSES[$data['payment_status'] ?? 'unpaid'] ?? 'غير مدفوع',
                'payment_method' => $data['payment_method'] ?? 'إرسال رابط دفع',
                'shipping_method' => $data['shipping_method'] ?? 'عادي',
                'shipping_status' => 'بانتظار التجهيز',
                'total' => self::formatMoney($total),
                'subtotal' => self::formatMoney($unitPrice * (int) ($data['qty'] ?? 1)),
                'discount' => self::formatMoney($discount),
                'shipping_fee' => self::formatMoney($shippingFee),
                'tax' => self::formatMoney($tax),
                'coupon_code' => $data['coupon_code'] ?? null,
                'customer_note' => $data['customer_note'] ?? null,
                'internal_note' => $data['internal_note'] ?? null,
                'currency' => 'ر.س',
                'created_at' => now()->toDateString(),
                'date' => now()->toDateString(),
                'items' => [
                    [
                        'product_id' => $data['product_id'] ?? null,
                        'sku' => $data['product_sku'] ?? null,
                        'name' => $data['item_name'] ?? 'طلب يدوي',
                        'qty' => (int) ($data['qty'] ?? 1),
                        'price' => self::formatMoney($unitPrice),
                        'line_total' => self::formatMoney($unitPrice * (int) ($data['qty'] ?? 1)),
                    ],
                ],
                'timeline' => [
                    ['label' => 'تم إنشاء الطلب اليدوي', 'time' => now()->format('Y-m-d H:i'), 'state' => 'done'],
                ],
                'notes' => array_filter([
                    $data['internal_note'] ?? null ? [
                        'body' => $data['internal_note'],
                        'actor' => $actor['name'] ?? 'Partner',
                        'created_at' => now()->format('Y-m-d H:i'),
                    ] : null,
                ]),
                'change_log' => [
                    ['action' => 'manual_order_created', 'actor' => $actor['name'] ?? 'Partner', 'time' => now()->format('Y-m-d H:i')],
                ],
            ],
        ]);

        self::syncManualOrderEffects($partner, $record, $data, $actor);

        self::logActivity($partner, $actor, 'manual_order_created', 'orders', $record->record_id, [
            'order_number' => $record->payload['order_number'] ?? $recordId,
            'total' => $record->payload['total'] ?? null,
        ]);

        return self::normalizeOrder($record);
    }

    public static function addNote(array $partner, string $orderId, string $body, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'orders', $orderId);
        $payload = $record->payload ?? [];
        $notes = $payload['notes'] ?? [];
        $changeLog = $payload['change_log'] ?? [];

        $notes[] = [
            'body' => $body,
            'actor' => $actor['name'] ?? 'Partner',
            'created_at' => now()->format('Y-m-d H:i'),
        ];

        $changeLog[] = [
            'action' => 'note_added',
            'actor' => $actor['name'] ?? 'Partner',
            'time' => now()->format('Y-m-d H:i'),
        ];

        $payload['notes'] = $notes;
        $payload['change_log'] = $changeLog;
        $payload['internal_note'] = trim(($payload['internal_note'] ?? '') . "\n" . $body);

        $record->update(['payload' => $payload]);

        self::logActivity($partner, $actor, 'order_note_added', 'orders', $record->record_id, ['body' => Str::limit($body, 120)]);

        return self::normalizeOrder($record->refresh());
    }

    public static function timeline(array $partner, string $orderId): array
    {
        return self::findForStore($partner, $orderId)['timeline'] ?? [];
    }

    public static function remindCart(array $partner, string $cartId, ?array $actor = null): array
    {
        $record = self::recordForStore($partner, 'abandoned_carts', $cartId);
        $payload = $record->payload ?? [];
        $payload['last_reminder_at'] = now()->format('Y-m-d H:i');
        $payload['reminders_count'] = (int) ($payload['reminders_count'] ?? 0) + 1;
        $payload['recovery_coupon'] = $payload['recovery_coupon'] ?? 'BACK' . now()->format('md');
        $payload['recovery_action'] = 'تم إرسال تذكير';

        $record->update([
            'status' => 'تم التذكير',
            'payload' => $payload,
        ]);

        self::logActivity($partner, $actor, 'abandoned_cart_reminded', 'abandoned_carts', $record->record_id, [
            'coupon' => $payload['recovery_coupon'],
        ]);

        return self::relatedRecord($record);
    }

    public static function convertCartToOrder(array $partner, string $cartId, ?array $actor = null): array
    {
        $cart = self::recordForStore($partner, 'abandoned_carts', $cartId);
        $payload = $cart->payload ?? [];
        $total = self::money($payload['total'] ?? 0);

        $order = self::createManual($partner, [
            'customer' => $payload['customer'] ?? 'عميل السلة',
            'phone' => $payload['phone'] ?? null,
            'email' => null,
            'product_id' => 'cart-' . $cart->record_id,
            'product_sku' => 'CART-' . $cart->record_id,
            'item_name' => $payload['items'][0]['name'] ?? 'منتجات السلة المتروكة',
            'qty' => max(1, (int) ($payload['items_count'] ?? 1)),
            'total' => $total,
            'unit_price' => $total,
            'discount' => 0,
            'shipping_fee' => 0,
            'tax' => 0,
            'payment_status' => 'pending',
            'payment_method' => 'إرسال رابط دفع',
            'shipping_method' => 'عادي',
            'source_channel' => 'سلة متروكة',
            'fulfillment_priority' => 'normal',
            'internal_note' => 'تم تحويل السلة المتروكة إلى طلب.',
        ], $actor);

        $cartPayload = $cart->payload ?? [];
        $cartPayload['converted_order_id'] = $order['id'];
        $cart->update(['status' => 'تم التحويل إلى طلب', 'payload' => $cartPayload]);

        self::logActivity($partner, $actor, 'abandoned_cart_converted', 'abandoned_carts', $cart->record_id, [
            'order_id' => $order['id'],
        ]);

        return $order;
    }

    public static function updateRelatedStatus(array $partner, string $section, string $recordId, string $status, ?array $actor = null): array
    {
        abort_unless(in_array($section, ['returns', 'shipments'], true), 404);

        $record = self::recordForStore($partner, $section, $recordId);
        $payload = $record->payload ?? [];
        $payload['status'] = $status;
        $payload['updated_at_human'] = 'الآن';

        if ($section === 'returns' && Str::contains($status, ['موافقة', 'استرداد', 'approved', 'refund'])) {
            $payload['inventory_action'] = 'تمت جدولة تحديث المخزون تلقائياً';
        }

        if ($section === 'shipments') {
            $payload['tracking_status'] = $status;
        }

        $record->update(['status' => $status, 'payload' => $payload]);

        if ($section === 'returns' && self::isRefundingStatus($status)) {
            self::syncReturnEffects($partner, $record->refresh(), $actor);
        }

        self::logActivity($partner, $actor, $section . '_status_updated', $section, $record->record_id, ['status' => $status]);

        return self::relatedRecord($record->refresh());
    }

    public static function relatedRows(array $partner, string $section): array
    {
        self::ensureStoreData($partner);

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

    public static function exportCsv(array $partner, Request $request): Response
    {
        $rows = self::list($partner, $request)['orders'];
        $lines = ["order_number,customer,phone,status,payment_status,shipping_method,total,source,created_at,store_id"];

        foreach ($rows as $order) {
            $lines[] = implode(',', array_map([self::class, 'csv'], [
                $order['order_number'] ?? $order['id'],
                $order['customer'] ?? '',
                $order['phone'] ?? '',
                $order['status'] ?? '',
                $order['payment_status'] ?? '',
                $order['shipping_method'] ?? '',
                $order['total'] ?? '',
                $order['source'] ?? '',
                $order['created_at'] ?? '',
                $order['store_id'] ?? $partner['store_id'],
            ]));
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=orders-' . $partner['store_id'] . '-' . now()->format('Ymd-His') . '.csv',
        ]);
    }

    private static function orderRecords(string $storeId): Collection
    {
        if (! Schema::hasTable('platform_records')) {
            return collect();
        }

        return PlatformRecord::query()
            ->where('section', 'orders')
            ->where('store_id', $storeId)
            ->latest()
            ->get()
            ->map(fn (PlatformRecord $record) => self::normalizeOrder($record));
    }

    private static function normalizeOrder(PlatformRecord $record): array
    {
        $payload = $record->payload ?? [];
        $status = $payload['status'] ?? $record->status ?? 'جديد';
        $paymentStatus = $payload['payment_status'] ?? 'مدفوع';

        return array_merge($payload, [
            'id' => $record->record_id,
            'store_id' => $record->store_id,
            'status' => $status,
            'status_key' => $payload['status_key'] ?? self::statusKeyFromLabel($status),
            'payment_status' => $paymentStatus,
            'payment_status_key' => $payload['payment_status_key'] ?? self::paymentKeyFromLabel($paymentStatus),
            'order_number' => $payload['order_number'] ?? $record->record_id,
            'customer' => $payload['customer'] ?? 'عميل',
            'phone' => $payload['phone'] ?? $payload['customer_phone'] ?? '966500000000',
            'source' => $payload['source'] ?? 'لوحة التحكم',
            'total' => $payload['total'] ?? $payload['amount'] ?? '0 ر.س',
            'currency' => $payload['currency'] ?? 'ر.س',
            'payment_method' => $payload['payment_method'] ?? self::paymentMethod($payload),
            'shipping_method' => $payload['shipping_method'] ?? self::shippingMethod($payload),
            'shipping_status' => $payload['shipping_status'] ?? 'قيد التجهيز',
            'created_at' => $payload['created_at'] ?? $payload['date'] ?? $record->created_at?->toDateString(),
            'updated_at_human' => $payload['updated_at_human'] ?? $record->updated_at?->diffForHumans(),
            'timeline' => $payload['timeline'] ?? self::timelineFor($status),
            'items' => $payload['items'] ?? [['name' => 'منتج من الطلب', 'qty' => 1, 'price' => $payload['total'] ?? '0 ر.س']],
            'notes' => $payload['notes'] ?? array_values(array_filter([
                ! empty($payload['internal_note'] ?? null) ? [
                    'body' => $payload['internal_note'],
                    'actor' => 'Partner',
                    'created_at' => $payload['created_at'] ?? $record->created_at?->format('Y-m-d H:i'),
                ] : null,
            ])),
            'change_log' => $payload['change_log'] ?? [],
            'subtotal' => $payload['subtotal'] ?? $payload['total'] ?? '0 ر.س',
            'discount' => $payload['discount'] ?? '0 ر.س',
            'tax' => $payload['tax'] ?? '0 ر.س',
            'shipping_fee' => $payload['shipping_fee'] ?? '0 ر.س',
        ]);
    }

    private static function applyFilters(Collection $orders, Request $request): Collection
    {
        $query = Str::lower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', 'all'));
        $payment = trim((string) $request->query('payment_status', 'all'));
        $shipping = trim((string) $request->query('shipping_status', 'all'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        return $orders
            ->filter(function (array $order) use ($query) {
                if ($query === '') {
                    return true;
                }

                return Str::contains(Str::lower(json_encode($order, JSON_UNESCAPED_UNICODE)), $query);
            })
            ->filter(fn (array $order) => $status === 'all' || self::statusKey($order) === $status)
            ->filter(fn (array $order) => $payment === 'all' || ($order['payment_status_key'] ?? null) === $payment)
            ->filter(fn (array $order) => $shipping === 'all' || Str::contains(Str::lower((string) ($order['shipping_method'] ?? $order['shipping_status'] ?? '')), Str::lower($shipping)))
            ->filter(fn (array $order) => $dateFrom === '' || self::orderDate($order)?->greaterThanOrEqualTo(Carbon::parse($dateFrom)->startOfDay()) !== false)
            ->filter(fn (array $order) => $dateTo === '' || self::orderDate($order)?->lessThanOrEqualTo(Carbon::parse($dateTo)->endOfDay()) !== false)
            ->values();
    }

    private static function statusCounts(Collection $orders): array
    {
        $counts = ['all' => $orders->count()];

        foreach (self::ORDER_STATUSES as $key => $label) {
            $counts[$key] = $orders->filter(fn (array $order) => self::statusKey($order) === $key)->count();
        }

        return $counts;
    }

    private static function ensureAbandonedCarts(array $partner): void
    {
        $section = 'abandoned_carts';
        $storeId = (string) $partner['store_id'];

        if (PlatformRecord::query()->where('section', $section)->where('store_id', $storeId)->exists()) {
            return;
        }

        foreach (array_slice($partner['customers'] ?? [], 0, 3) as $index => $customer) {
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => 'cart-' . $storeId . '-' . ($index + 1),
                'store_id' => $storeId,
                'partner_id' => $partner['id'] ?? null,
                'status' => 'مفتوحة',
                'payload' => [
                    'customer' => $customer['name'] ?? 'عميل',
                    'phone' => $customer['phone'] ?? '966500000000',
                    'items_count' => $index + 1,
                    'items' => [
                        ['name' => 'منتج متروك', 'qty' => $index + 1, 'price' => self::formatMoney(180 + ($index * 40))],
                    ],
                    'total' => self::formatMoney(180 + ($index * 95)),
                    'last_activity' => now()->subHours($index + 2)->diffForHumans(),
                    'recovery_action' => 'إرسال تذكير واتساب',
                    'updated_at' => now()->subHours($index + 2)->format('Y-m-d H:i'),
                ],
            ]);
        }
    }

    private static function ensureReturns(array $partner): void
    {
        $section = 'returns';
        $storeId = (string) $partner['store_id'];

        if (PlatformRecord::query()->where('section', $section)->where('store_id', $storeId)->exists()) {
            return;
        }

        foreach (array_slice($partner['orders'] ?? [], 0, 2) as $index => $order) {
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => 'return-' . ($order['id'] ?? $index + 1),
                'store_id' => $storeId,
                'partner_id' => $partner['id'] ?? null,
                'status' => 'قيد المراجعة',
                'payload' => [
                    'order_number' => $order['id'] ?? 'ORD-' . ($index + 1),
                    'customer' => $order['customer'] ?? 'عميل',
                    'reason' => $index === 0 ? 'استبدال المقاس' : 'إلغاء بعد الدفع',
                    'total' => $order['amount'] ?? '0 ر.س',
                    'items' => [
                        ['name' => 'منتج مرتجع', 'qty' => 1, 'price' => $order['amount'] ?? '0 ر.س'],
                    ],
                    'refund_status' => 'بانتظار القرار',
                    'created_at' => now()->subDays($index + 1)->toDateString(),
                ],
            ]);
        }
    }

    private static function ensureShipments(array $partner): void
    {
        $section = 'shipments';
        $storeId = (string) $partner['store_id'];

        if (PlatformRecord::query()->where('section', $section)->where('store_id', $storeId)->exists()) {
            return;
        }

        foreach (($partner['shipments'] ?? []) as $index => $shipment) {
            PlatformRecord::query()->create([
                'section' => $section,
                'record_id' => $shipment['id'] ?? 'shipment-' . $storeId . '-' . ($index + 1),
                'store_id' => $storeId,
                'partner_id' => $partner['id'] ?? null,
                'status' => $shipment['status'] ?? 'قيد الشحن',
                'payload' => [
                    'shipment_number' => $shipment['id'] ?? 'SHP-' . ($index + 1),
                    'carrier' => $shipment['carrier'] ?? 'ناقل',
                    'status' => $shipment['status'] ?? 'قيد الشحن',
                    'order_number' => $partner['orders'][$index]['id'] ?? ('ORD-' . ($index + 1)),
                    'city' => $shipment['city'] ?? 'الرياض',
                    'eta' => $shipment['eta'] ?? now()->addDay()->toDateString(),
                    'service' => $index % 2 === 0 ? 'سريع' : 'عادي',
                    'tracking_number' => 'TRK' . now()->format('ymd') . ($index + 100),
                    'tracking_url' => 'https://tracking.solve.test/' . ($shipment['id'] ?? 'shipment-' . $index),
                ],
            ]);
        }
    }

    private static function timelineFor(string $status): array
    {
        return [
            ['label' => 'تم إنشاء الطلب', 'time' => now()->subHours(5)->format('Y-m-d H:i'), 'state' => 'done'],
            ['label' => 'تم تأكيد الدفع', 'time' => now()->subHours(4)->format('Y-m-d H:i'), 'state' => 'done'],
            ['label' => 'التجهيز في المستودع', 'time' => now()->subHours(2)->format('Y-m-d H:i'), 'state' => self::statusKeyFromLabel($status) === 'new' ? 'pending' : 'done'],
            ['label' => 'التسليم للناقل', 'time' => now()->format('Y-m-d H:i'), 'state' => in_array(self::statusKeyFromLabel($status), ['delivery', 'completed'], true) ? 'done' : 'pending'],
        ];
    }

    private static function statusKey(array $order): string
    {
        return $order['status_key'] ?? self::statusKeyFromLabel((string) ($order['status'] ?? ''));
    }

    private static function statusKeyFromLabel(string $label): string
    {
        $label = Str::lower($label);

        return match (true) {
            Str::contains($label, ['مكتمل', 'تم التوصيل', 'تم التسليم', 'completed']) => 'completed',
            Str::contains($label, ['ملغي', 'إلغاء', 'cancel']) => 'cancelled',
            Str::contains($label, ['استرجاع']) => 'return_requested',
            Str::contains($label, ['جاري التوصيل', 'شحن', 'delivery']) => 'delivery',
            Str::contains($label, ['جاهز']) => 'ready',
            Str::contains($label, ['تجهيز', 'معالجة', 'processing']) => 'processing',
            default => 'new',
        };
    }

    private static function paymentKeyFromLabel(string $label): string
    {
        $label = Str::lower($label);

        return match (true) {
            Str::contains($label, ['غير', 'unpaid']) => 'unpaid',
            Str::contains($label, ['انتظار', 'pending', 'معلق']) => 'pending',
            Str::contains($label, ['استرجاع', 'refund']) => 'refunded',
            default => 'paid',
        };
    }

    private static function paymentMethod(array $payload): string
    {
        return $payload['payment_method'] ?? $payload['gateway'] ?? 'Apple Pay';
    }

    private static function shippingMethod(array $payload): string
    {
        return $payload['shipping_method'] ?? $payload['shipping_status'] ?? 'عادي';
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

    private static function orderDate(array $order): ?Carbon
    {
        foreach (['created_at', 'date'] as $key) {
            if (empty($order[$key])) {
                continue;
            }

            try {
                return Carbon::parse($order[$key]);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private static function logActivity(array $partner, ?array $actor, string $action, string $subjectType, string $subjectId, array $properties = []): void
    {
        if (! Schema::hasTable('platform_activity_logs')) {
            return;
        }

        PlatformActivityLog::query()->create([
            'actor_type' => 'partner',
            'actor_id' => $actor['username'] ?? $actor['email'] ?? null,
            'actor_name' => $actor['name'] ?? 'Partner',
            'role' => $actor['role'] ?? null,
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
        ]);
    }

    private static function syncManualOrderEffects(array $partner, PlatformRecord $orderRecord, array $data, ?array $actor): void
    {
        $payload = $orderRecord->payload ?? [];
        $productId = (string) ($payload['product_id'] ?? '');
        $qty = max(1, (int) ($payload['items'][0]['qty'] ?? $data['qty'] ?? 1));

        if ($productId !== '') {
            self::adjustProductStock($partner, $productId, -$qty, 'order_created', $orderRecord->record_id, $actor);
        }

        self::createFinancialRecordsForOrder($partner, $orderRecord, $actor);
    }

    private static function createFinancialRecordsForOrder(array $partner, PlatformRecord $orderRecord, ?array $actor): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        $payload = $orderRecord->payload ?? [];
        $orderNumber = (string) ($payload['order_number'] ?? $orderRecord->record_id);
        $total = self::money($payload['total'] ?? 0);
        $tax = self::money($payload['tax'] ?? 0);
        $discount = self::money($payload['discount'] ?? 0);
        $shipping = self::money($payload['shipping_fee'] ?? 0);
        $paymentStatus = (string) ($payload['payment_status'] ?? '');
        $paymentStatusKey = (string) ($payload['payment_status_key'] ?? 'unpaid');
        $paymentId = 'payment-' . $orderRecord->record_id;
        $invoiceId = 'invoice-' . $orderRecord->record_id;

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'payments', 'store_id' => $partner['store_id'], 'record_id' => $paymentId],
            [
                'partner_id' => $partner['id'] ?? null,
                'status' => $paymentStatus,
                'payload' => [
                    'id' => $paymentId,
                    'store_id' => $partner['store_id'],
                    'order_id' => $orderRecord->record_id,
                    'order_number' => $orderNumber,
                    'customer' => $payload['customer'] ?? null,
                    'method' => $payload['payment_method'] ?? null,
                    'payment_status_key' => $paymentStatusKey,
                    'status' => $paymentStatus,
                    'amount' => self::formatMoney($total),
                    'fee' => self::formatMoney(0),
                    'created_at' => now()->toDateString(),
                ],
            ]
        );

        PlatformRecord::query()->updateOrCreate(
            ['section' => 'invoices', 'store_id' => $partner['store_id'], 'record_id' => $invoiceId],
            [
                'partner_id' => $partner['id'] ?? null,
                'status' => $paymentStatus,
                'payload' => [
                    'id' => $invoiceId,
                    'invoice_number' => 'INV-' . $orderNumber,
                    'store_id' => $partner['store_id'],
                    'order_id' => $orderRecord->record_id,
                    'order_number' => $orderNumber,
                    'customer' => $payload['customer'] ?? null,
                    'total' => self::formatMoney($total),
                    'tax' => self::formatMoney($tax),
                    'discount' => self::formatMoney($discount),
                    'shipping_fee' => self::formatMoney($shipping),
                    'payment_status' => $paymentStatus,
                    'created_at' => now()->toDateString(),
                ],
            ]
        );

        if ($paymentStatusKey === 'paid') {
            self::createWalletTransaction($partner, 'wallet-order-' . $orderRecord->record_id, 'order_payment', $total, [
                'order_id' => $orderRecord->record_id,
                'order_number' => $orderNumber,
                'payment_id' => $paymentId,
                'description' => 'Manual order payment captured',
            ]);
        }

        self::logActivity($partner, $actor, 'order_financial_records_synced', 'orders', $orderRecord->record_id, [
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceId,
        ]);
    }

    private static function syncReturnEffects(array $partner, PlatformRecord $returnRecord, ?array $actor): void
    {
        $payload = $returnRecord->payload ?? [];
        $productId = (string) ($payload['product_id'] ?? data_get($payload, 'items.0.product_id', ''));
        $qty = max(1, (int) ($payload['qty'] ?? data_get($payload, 'items.0.qty', 1)));
        $amount = self::money($payload['amount'] ?? $payload['total'] ?? $payload['refund_amount'] ?? 0);
        $orderNumber = (string) ($payload['order_number'] ?? $payload['order_id'] ?? '');

        if ($productId !== '') {
            self::adjustProductStock($partner, $productId, $qty, 'return_refunded', $returnRecord->record_id, $actor);
        }

        $refundId = 'refund-' . $returnRecord->record_id;
        PlatformRecord::query()->updateOrCreate(
            ['section' => 'refunds', 'store_id' => $partner['store_id'], 'record_id' => $refundId],
            [
                'partner_id' => $partner['id'] ?? null,
                'status' => $payload['status'] ?? 'approved',
                'payload' => [
                    'id' => $refundId,
                    'store_id' => $partner['store_id'],
                    'return_id' => $returnRecord->record_id,
                    'order_number' => $orderNumber,
                    'product_id' => $productId ?: null,
                    'qty' => $qty,
                    'amount' => self::formatMoney($amount),
                    'status' => $payload['status'] ?? 'approved',
                    'created_at' => now()->toDateString(),
                ],
            ]
        );

        if ($amount > 0) {
            self::createWalletTransaction($partner, 'wallet-refund-' . $returnRecord->record_id, 'refund', -$amount, [
                'return_id' => $returnRecord->record_id,
                'order_number' => $orderNumber,
                'description' => 'Refund deducted from wallet',
            ]);
        }

        self::markPaymentRefunded($partner, $orderNumber, $amount);
        self::logActivity($partner, $actor, 'return_financial_records_synced', 'returns', $returnRecord->record_id, [
            'refund_id' => $refundId,
            'amount' => self::formatMoney($amount),
        ]);
    }

    private static function adjustProductStock(array $partner, string $productId, int $delta, string $reason, string $sourceId, ?array $actor): void
    {
        if (! Schema::hasTable('platform_records')) {
            return;
        }

        $product = PlatformRecord::query()
            ->where('section', 'products')
            ->where('store_id', $partner['store_id'])
            ->where('record_id', $productId)
            ->first();

        if (! $product) {
            return;
        }

        $payload = $product->payload ?? [];
        $before = (int) ($payload['stock'] ?? 0);
        $after = max(0, $before + $delta);
        $payload['stock'] = $after;
        $payload['updated_at_human'] = 'الآن';

        $product->update(['payload' => $payload]);

        PlatformRecord::query()->create([
            'section' => 'inventory_logs',
            'record_id' => 'inventory-' . Str::lower(Str::random(10)),
            'store_id' => $partner['store_id'],
            'partner_id' => $partner['id'] ?? null,
            'status' => $reason,
            'payload' => [
                'store_id' => $partner['store_id'],
                'product_id' => $productId,
                'source_id' => $sourceId,
                'reason' => $reason,
                'delta' => $delta,
                'before' => $before,
                'after' => $after,
                'actor' => $actor['name'] ?? 'Partner',
                'created_at' => now()->format('Y-m-d H:i'),
            ],
        ]);
    }

    private static function createWalletTransaction(array $partner, string $recordId, string $type, float $amount, array $payload): void
    {
        PlatformRecord::query()->updateOrCreate(
            ['section' => 'wallet_transactions', 'store_id' => $partner['store_id'], 'record_id' => $recordId],
            [
                'partner_id' => $partner['id'] ?? null,
                'status' => $type,
                'payload' => [
                    'id' => $recordId,
                    'store_id' => $partner['store_id'],
                    'type' => $type,
                    'amount' => self::formatMoney($amount),
                    'amount_numeric' => $amount,
                    'created_at' => now()->toDateString(),
                ] + $payload,
            ]
        );
    }

    private static function markPaymentRefunded(array $partner, string $orderNumber, float $amount): void
    {
        if ($orderNumber === '') {
            return;
        }

        $payment = PlatformRecord::query()
            ->where('section', 'payments')
            ->where('store_id', $partner['store_id'])
            ->get()
            ->first(function (PlatformRecord $record) use ($orderNumber) {
                $payload = $record->payload ?? [];

                return ($payload['order_number'] ?? null) === $orderNumber || ($payload['order_id'] ?? null) === $orderNumber;
            });

        if (! $payment) {
            return;
        }

        $payload = $payment->payload ?? [];
        $payload['status'] = 'مسترد';
        $payload['refund_status'] = 'refunded';
        $payload['refunded_amount'] = self::formatMoney($amount);

        $payment->update(['status' => 'مسترد', 'payload' => $payload]);
    }

    private static function isRefundingStatus(string $status): bool
    {
        return Str::contains(Str::lower($status), ['موافقة', 'استرداد', 'approved', 'refund']);
    }

    private static function money(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private static function formatMoney(float|int $amount): string
    {
        return number_format((float) $amount) . ' ر.س';
    }

    private static function csv(mixed $value): string
    {
        $value = (string) $value;

        if ($value !== '' && preg_match('/^[=\-+@\t\r]/', $value) === 1) {
            $value = "'" . $value;
        }

        return '"' . str_replace('"', '""', $value) . '"';
    }
}
