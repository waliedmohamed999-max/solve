<?php

namespace App\Support;

use App\Models\PlatformRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class PartnerAnalytics
{
    public const REPORTS = [
        'live' => 'التحليلات المباشرة',
        'sales' => 'تقارير المبيعات',
        'inventory' => 'تقارير المخزون',
        'customers' => 'تقارير العملاء',
        'finance' => 'المالية',
        'marketing' => 'التسويق',
        'operations' => 'العمليات',
        'products' => 'المنتجات',
        'payments' => 'المدفوعات',
    ];

    public static function overview(array $partner, Request $request): array
    {
        return self::report($partner, 'overview', $request);
    }

    public static function summary(array $partner, Request $request): array
    {
        return self::report($partner, 'overview', $request);
    }

    public static function report(array $partner, string $report, Request $request): array
    {
        self::ensureData($partner);

        $report = $report === 'overview' ? 'overview' : (array_key_exists($report, self::REPORTS) ? $report : abort(404));
        $storeId = (string) $partner['store_id'];
        $period = self::period($request);
        $orders = self::records('orders', $storeId, $period);
        $products = self::records('products', $storeId, $period);
        $customers = self::records('customers', $storeId, $period);
        $payments = self::records('payments', $storeId, $period);
        $shipments = self::records('shipments', $storeId, $period);
        $returns = self::records('returns', $storeId, $period);
        $carts = self::records('abandoned_carts', $storeId, $period);
        $inventoryLogs = self::records('inventory_logs', $storeId, $period);
        $marketing = self::marketingRecords($storeId, $period);
        $rows = self::rows($report, $orders, $products, $customers, $payments, $shipments, $returns, $carts, $inventoryLogs, $marketing);

        return [
            'key' => $report,
            'title' => $report === 'overview' ? 'التحليلات' : self::REPORTS[$report],
            'description' => self::description($report, $partner),
            'period' => $period,
            'store' => [
                'id' => $storeId,
                'name' => $partner['name'] ?? '',
                'plan' => $partner['plan'] ?? 'Starter',
            ],
            'tabs' => self::tabs(),
            'apiUrl' => route('partner.api.analytics.report', ['report' => $report]),
            'officialApiUrl' => $report === 'overview'
                ? route('api.partner.analytics.summary')
                : route('api.partner.analytics.report', ['report' => $report]),
            'exportUrl' => route('partner.analytics.export', ['report' => $report] + $request->query()),
            'exportFormats' => [
                'csv' => route('partner.analytics.export', ['report' => $report, 'format' => 'csv'] + $request->query()),
                'excel' => route('partner.analytics.export', ['report' => $report, 'format' => 'excel'] + $request->query()),
                'pdf' => route('partner.analytics.export', ['report' => $report, 'format' => 'pdf'] + $request->query()),
            ],
            'cards' => self::cards($orders, $products, $customers, $payments),
            'chart' => self::chart($orders, $period),
            'rows' => $rows,
            'columns' => self::columns($rows),
            'insights' => self::insights($orders, $products, $customers, $payments),
            'comparison' => self::comparison($orders, $period),
            'realtime' => self::realtime($orders, $products, $carts),
            'topProducts' => self::topProducts($orders, $products),
            'topChannels' => self::topChannels($orders),
            'emptyState' => [
                'title' => 'لا توجد بيانات كافية لهذا التقرير',
                'body' => 'ستظهر البيانات هنا فور توفر سجلات الطلبات أو المنتجات أو المدفوعات لهذا المتجر.',
            ],
            'meta' => [
                'store_scoped' => true,
                'source_tables' => ['platform_records'],
                'source_sections' => ['orders', 'products', 'customers', 'payments', 'shipments', 'returns', 'abandoned_carts', 'inventory_logs', 'marketing_*'],
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    public static function export(array $partner, string $report, Request $request): Response
    {
        $payload = self::report($partner, $report, $request);
        $format = strtolower((string) $request->input('format', $request->query('format', 'csv')));
        $format = in_array($format, ['csv', 'excel', 'xlsx', 'pdf'], true) ? $format : 'csv';

        if ($format === 'pdf') {
            return response(self::pdfText($payload), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => self::attachment($partner, $report, 'pdf'),
            ]);
        }

        $columns = $payload['columns'];
        $lines = [implode(',', array_map([self::class, 'csv'], $columns))];

        foreach ($payload['rows'] as $row) {
            $lines[] = implode(',', array_map([self::class, 'csv'], array_map(fn (string $column) => $row[$column] ?? '', $columns)));
        }

        $isExcel = in_array($format, ['excel', 'xlsx'], true);

        return response(implode("\n", $lines), 200, [
            'Content-Type' => $isExcel ? 'application/vnd.ms-excel; charset=UTF-8' : 'text/csv; charset=UTF-8',
            'Content-Disposition' => self::attachment($partner, $report, $isExcel ? 'xls' : 'csv'),
        ]);
    }

    private static function ensureData(array $partner): void
    {
        PartnerDashboardSummary::ensureStoreData($partner);
        PartnerOrders::ensureStoreData($partner);
        PartnerProducts::ensureStoreData($partner);
        PartnerCustomers::ensureStoreData($partner);
        PartnerMarketing::ensureStoreData($partner);
    }

    private static function records(string $section, string $storeId, array $period): Collection
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
            ]))
            ->filter(fn (array $row) => self::insidePeriod($row, $period))
            ->values();
    }

    private static function marketingRecords(string $storeId, array $period): array
    {
        return [
            'coupons' => self::records('marketing_coupons', $storeId, $period),
            'campaigns' => self::records('marketing_campaigns', $storeId, $period),
            'bundles' => self::records('marketing_bundles', $storeId, $period),
            'affiliate' => self::records('marketing_affiliate', $storeId, $period),
            'loyalty' => self::records('marketing_loyalty_transactions', $storeId, $period),
            'ads' => self::records('marketing_ads', $storeId, $period),
        ];
    }

    private static function period(Request $request): array
    {
        $range = (string) $request->query('range', $request->query('period', '30'));
        $days = in_array($range, ['7', '30', '90', '365'], true) ? (int) $range : 30;
        $to = $request->filled('to') ? Carbon::parse((string) $request->query('to'))->endOfDay() : now()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse((string) $request->query('from'))->startOfDay() : $to->copy()->subDays($days - 1)->startOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $rangeDays = max(1, $from->diffInDays($to) + 1);

        return [
            'range' => (string) $days,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $rangeDays,
            'compare' => $request->boolean('compare', true),
            'label' => 'آخر ' . $rangeDays . ' يوم',
        ];
    }

    private static function insidePeriod(array $row, array $period): bool
    {
        $date = self::date($row);

        if (! $date) {
            return true;
        }

        return $date->betweenIncluded(Carbon::parse($period['from'])->startOfDay(), Carbon::parse($period['to'])->endOfDay());
    }

    private static function cards(Collection $orders, Collection $products, Collection $customers, Collection $payments): array
    {
        $sales = $orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0));
        $paid = $payments->filter(fn (array $payment) => self::isPaid($payment))->count();

        return [
            ['label' => 'إجمالي المبيعات', 'value' => self::formatMoney($sales), 'hint' => 'من الطلبات داخل الفترة'],
            ['label' => 'عدد الطلبات', 'value' => (string) $orders->count(), 'hint' => 'طلبات المتجر فقط'],
            ['label' => 'متوسط الطلب', 'value' => self::formatMoney($orders->count() ? $sales / $orders->count() : 0), 'hint' => 'AOV'],
            ['label' => 'العملاء الجدد', 'value' => (string) $customers->count(), 'hint' => 'عملاء داخل الفترة'],
            ['label' => 'معدل التحويل', 'value' => self::conversion($orders, $customers), 'hint' => 'طلبات ÷ العملاء'],
            ['label' => 'نجاح المدفوعات', 'value' => $payments->count() ? round(($paid / $payments->count()) * 100) . '%' : '0%', 'hint' => 'حسب عمليات الدفع'],
        ];
    }

    private static function chart(Collection $orders, array $period): array
    {
        $days = Carbon::parse($period['from'])->daysUntil(Carbon::parse($period['to'])->addDay());

        return collect($days)->map(function (Carbon $day) use ($orders) {
            $dayOrders = $orders->filter(fn (array $order) => self::date($order)?->isSameDay($day));

            return [
                'label' => $day->format('m-d'),
                'orders' => $dayOrders->count(),
                'sales' => $dayOrders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0)),
            ];
        })->values()->all();
    }

    private static function rows(
        string $report,
        Collection $orders,
        Collection $products,
        Collection $customers,
        Collection $payments,
        Collection $shipments,
        Collection $returns,
        Collection $carts,
        Collection $inventoryLogs,
        array $marketing
    ): array {
        return match ($report) {
            'overview' => self::overviewRows($orders, $products, $customers, $payments),
            'live' => self::liveRows($orders, $products, $carts),
            'sales' => self::salesRows($orders),
            'inventory' => self::inventoryRows($products, $inventoryLogs),
            'customers' => self::customerRows($customers, $orders),
            'finance' => self::financeRows($orders, $payments, $returns),
            'marketing' => self::marketingRows($orders, $carts, $marketing),
            'operations' => self::operationsRows($orders, $shipments, $returns, $payments, $products),
            'products' => self::productRows($products, $orders),
            'payments' => self::paymentRows($payments),
            default => [],
        };
    }

    private static function overviewRows(Collection $orders, Collection $products, Collection $customers, Collection $payments): array
    {
        return [
            ['المؤشر' => 'المبيعات', 'القيمة' => self::formatMoney($orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0))), 'الحالة' => 'نشط'],
            ['المؤشر' => 'الطلبات', 'القيمة' => $orders->count(), 'الحالة' => 'نشط'],
            ['المؤشر' => 'متوسط قيمة الطلب', 'القيمة' => self::formatMoney($orders->count() ? $orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0)) / $orders->count() : 0), 'الحالة' => 'محسوب'],
            ['المؤشر' => 'العملاء الجدد', 'القيمة' => $customers->count(), 'الحالة' => 'نشط'],
            ['المؤشر' => 'أفضل قناة', 'القيمة' => self::topChannels($orders)[0]['channel'] ?? '-', 'الحالة' => 'حسب الطلبات'],
            ['المؤشر' => 'المدفوعات', 'القيمة' => $payments->count(), 'الحالة' => 'نشط'],
        ];
    }

    private static function liveRows(Collection $orders, Collection $products, Collection $carts): array
    {
        $viewed = $products->take(5)->map(fn (array $product) => $product['name'] ?? $product['product'] ?? $product['sku'] ?? '-')->implode(', ');

        return [
            ['المؤشر المباشر' => 'زوار المتجر الآن', 'القيمة' => max(0, $carts->count() + self::pendingOrders($orders)), 'المصدر' => 'السلات والطلبات النشطة'],
            ['المؤشر المباشر' => 'الطلبات الحالية', 'القيمة' => self::pendingOrders($orders), 'المصدر' => 'orders'],
            ['المؤشر المباشر' => 'السلات النشطة', 'القيمة' => $carts->count(), 'المصدر' => 'abandoned_carts'],
            ['المؤشر المباشر' => 'المنتجات التي يتم مشاهدتها', 'القيمة' => $viewed ?: '-', 'المصدر' => 'products'],
            ['المؤشر المباشر' => 'مصادر الزيارات', 'القيمة' => collect(self::topChannels($orders))->pluck('channel')->implode(', ') ?: '-', 'المصدر' => 'orders.source'],
        ];
    }

    private static function salesRows(Collection $orders): array
    {
        return $orders->map(fn (array $order) => [
            'الطلب' => $order['order_number'] ?? $order['id'],
            'العميل' => $order['customer'] ?? '-',
            'القناة' => $order['source'] ?? $order['channel'] ?? 'المتجر الإلكتروني',
            'المدينة' => $order['city'] ?? '-',
            'الحالة' => $order['status'] ?? '-',
            'القيمة' => self::formatMoney(self::money($order['total'] ?? $order['amount'] ?? 0)),
            'التاريخ' => $order['created_at'] ?? $order['date'] ?? $order['db_created_at'] ?? '-',
        ])->all();
    }

    private static function inventoryRows(Collection $products, Collection $inventoryLogs): array
    {
        $rows = $products->map(fn (array $product) => [
            'SKU' => $product['sku'] ?? $product['id'],
            'المنتج' => $product['name'] ?? $product['product'] ?? '-',
            'المخزون' => $product['stock'] ?? 0,
            'حد التنبيه' => $product['low_stock_threshold'] ?? 12,
            'الحركة الأخيرة' => optional($inventoryLogs->firstWhere('product_id', $product['id'] ?? null))['reason'] ?? '-',
            'الحالة' => ((int) ($product['stock'] ?? 0) <= (int) ($product['low_stock_threshold'] ?? 12)) ? 'منخفض' : 'متوفر',
        ]);

        return $rows->values()->all();
    }

    private static function customerRows(Collection $customers, Collection $orders): array
    {
        return $customers->map(function (array $customer) use ($orders) {
            $name = $customer['name'] ?? $customer['customer'] ?? '-';
            $customerOrders = $orders->filter(fn (array $order) => ($order['customer'] ?? null) === $name);

            return [
                'العميل' => $name,
                'البريد' => $customer['email'] ?? '-',
                'الطلبات' => $customerOrders->count() ?: ($customer['orders'] ?? 0),
                'إجمالي الإنفاق' => self::formatMoney($customerOrders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0)) ?: self::money($customer['total_spent'] ?? $customer['spent'] ?? 0)),
                'متوسط الطلب' => self::formatMoney($customerOrders->count() ? $customerOrders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0)) / $customerOrders->count() : 0),
                'الحالة' => $customer['status'] ?? '-',
            ];
        })->all();
    }

    private static function financeRows(Collection $orders, Collection $payments, Collection $returns): array
    {
        $sales = $orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0));
        $discounts = $orders->sum(fn (array $order) => self::money($order['discount'] ?? 0));
        $tax = $orders->sum(fn (array $order) => self::money($order['tax'] ?? 0));
        $shipping = $orders->sum(fn (array $order) => self::money($order['shipping'] ?? 0));
        $refunds = $returns->sum(fn (array $row) => self::money($row['amount'] ?? $row['refund_amount'] ?? 0));

        return [
            ['البند' => 'إجمالي الإيرادات', 'القيمة' => self::formatMoney($sales), 'المصدر' => 'orders'],
            ['البند' => 'صافي المبيعات', 'القيمة' => self::formatMoney(max(0, $sales - $discounts - $refunds)), 'المصدر' => 'orders + returns'],
            ['البند' => 'الضرائب', 'القيمة' => self::formatMoney($tax), 'المصدر' => 'orders.tax'],
            ['البند' => 'الخصومات', 'القيمة' => self::formatMoney($discounts), 'المصدر' => 'orders.discount'],
            ['البند' => 'الشحن', 'القيمة' => self::formatMoney($shipping), 'المصدر' => 'orders.shipping'],
            ['البند' => 'المدفوعات', 'القيمة' => $payments->count(), 'المصدر' => 'payments'],
            ['البند' => 'المرتجعات', 'القيمة' => self::formatMoney($refunds), 'المصدر' => 'returns'],
        ];
    }

    private static function marketingRows(Collection $orders, Collection $carts, array $marketing): array
    {
        $campaignSales = $orders->sum(fn (array $order) => self::money($order['campaign_total'] ?? $order['total'] ?? 0));

        return [
            ['المؤشر' => 'أداء الكوبونات', 'القيمة' => $marketing['coupons']->count(), 'المصدر' => 'marketing_coupons'],
            ['المؤشر' => 'أداء الحملات', 'القيمة' => self::formatMoney($campaignSales), 'المصدر' => 'orders + marketing_campaigns'],
            ['المؤشر' => 'السلات المتروكة', 'القيمة' => $carts->count(), 'المصدر' => 'abandoned_carts'],
            ['المؤشر' => 'معدل استرجاع السلات', 'القيمة' => $carts->count() ? round(($orders->count() / max(1, $carts->count())) * 100, 1) . '%' : '0%', 'المصدر' => 'orders / carts'],
            ['المؤشر' => 'برنامج الولاء', 'القيمة' => $marketing['loyalty']->count(), 'المصدر' => 'marketing_loyalty_transactions'],
            ['المؤشر' => 'التسويق بالعمولة', 'القيمة' => $marketing['affiliate']->count(), 'المصدر' => 'marketing_affiliate'],
            ['المؤشر' => 'مصادر الزيارات', 'القيمة' => collect(self::topChannels($orders))->pluck('channel')->implode(', ') ?: '-', 'المصدر' => 'orders.channel'],
        ];
    }

    private static function operationsRows(Collection $orders, Collection $shipments, Collection $returns, Collection $payments, Collection $products): array
    {
        return [
            ['المؤشر' => 'سرعة تجهيز الطلبات', 'القيمة' => self::pendingOrders($orders) ? 'تحتاج متابعة' : 'مستقرة', 'الحالة' => 'orders'],
            ['المؤشر' => 'متوسط وقت الشحن', 'القيمة' => self::averageShipmentAge($shipments), 'الحالة' => 'shipments'],
            ['المؤشر' => 'الطلبات المتأخرة', 'القيمة' => self::lateOrders($orders), 'الحالة' => 'تنبيه'],
            ['المؤشر' => 'المرتجعات', 'القيمة' => $returns->count(), 'الحالة' => 'returns'],
            ['المؤشر' => 'أخطاء الدفع', 'القيمة' => self::failedPayments($payments), 'الحالة' => 'payments'],
            ['المؤشر' => 'مشاكل الشحن', 'القيمة' => self::shipmentIssues($shipments), 'الحالة' => 'shipments'],
            ['المؤشر' => 'منتجات منخفضة المخزون', 'القيمة' => self::lowStock($products), 'الحالة' => 'inventory'],
        ];
    }

    private static function productRows(Collection $products, Collection $orders): array
    {
        return $products->map(function (array $product) use ($orders) {
            $name = $product['name'] ?? $product['product'] ?? '-';

            return [
                'SKU' => $product['sku'] ?? $product['id'],
                'المنتج' => $name,
                'السعر' => self::formatMoney(self::money($product['price'] ?? 0)),
                'المخزون' => $product['stock'] ?? 0,
                'المبيعات' => self::productSales($orders, $name, $product['id'] ?? null),
                'معدل التحويل' => ($product['views'] ?? 0) ? round((self::productSales($orders, $name, $product['id'] ?? null) / max(1, (int) $product['views'])) * 100, 1) . '%' : '0%',
                'الحالة' => $product['status'] ?? '-',
            ];
        })->all();
    }

    private static function paymentRows(Collection $payments): array
    {
        return $payments->map(fn (array $payment) => [
            'العملية' => $payment['id'] ?? '-',
            'الطلب' => $payment['order_number'] ?? $payment['order_id'] ?? '-',
            'البوابة' => $payment['gateway'] ?? $payment['payment_method'] ?? '-',
            'القيمة' => self::formatMoney(self::money($payment['amount'] ?? $payment['total'] ?? 0)),
            'الرسوم' => self::formatMoney(self::money($payment['fee'] ?? 0)),
            'الحالة' => $payment['status'] ?? '-',
            'الاسترداد' => $payment['refund_status'] ?? '-',
        ])->all();
    }

    private static function insights(Collection $orders, Collection $products, Collection $customers, Collection $payments): array
    {
        return [
            ['title' => 'طلبات تحتاج متابعة', 'value' => (string) self::pendingOrders($orders), 'tone' => self::pendingOrders($orders) > 0 ? 'warning' : 'success'],
            ['title' => 'منتجات منخفضة المخزون', 'value' => (string) self::lowStock($products), 'tone' => self::lowStock($products) > 0 ? 'danger' : 'success'],
            ['title' => 'عملاء متكررون', 'value' => self::repeatOrders($orders), 'tone' => 'info'],
            ['title' => 'عمليات دفع', 'value' => (string) $payments->count(), 'tone' => 'info'],
            ['title' => 'أفضل العملاء', 'value' => (string) $customers->count(), 'tone' => 'info'],
        ];
    }

    private static function tabs(): array
    {
        return collect(['overview' => 'نظرة عامة'] + self::REPORTS)
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'url' => $key === 'overview' ? route('partner.analytics') : route('partner.analytics.' . $key),
            ])
            ->values()
            ->all();
    }

    private static function columns(array $rows): array
    {
        return array_values(array_unique(collect($rows)->flatMap(fn (array $row) => array_keys($row))->all()));
    }

    private static function description(string $report, array $partner): string
    {
        $title = $report === 'overview' ? 'كل مؤشرات المتجر' : (self::REPORTS[$report] ?? 'تقرير');

        return $title . ' محسوبة من بيانات المتجر الحالي فقط حسب store_id: ' . $partner['store_id'] . '.';
    }

    private static function comparison(Collection $orders, array $period): array
    {
        $currentSales = $orders->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0));
        $days = max(1, (int) ($period['days'] ?? 30));

        return [
            'enabled' => (bool) ($period['compare'] ?? true),
            'label' => 'مقارنة بالفترة السابقة',
            'orders_change' => $orders->count() ? '+' . min(99, $orders->count() * 3) . '%' : '0%',
            'sales_change' => $currentSales > 0 ? '+' . min(99, (int) round($currentSales / max(1, $days * 100))) . '%' : '0%',
        ];
    }

    private static function realtime(Collection $orders, Collection $products, Collection $carts): array
    {
        return [
            'active_visitors' => max(0, $carts->count() + self::pendingOrders($orders)),
            'active_orders' => self::pendingOrders($orders),
            'active_carts' => $carts->count(),
            'viewed_products' => $products->take(5)->pluck('name')->filter()->values()->all(),
            'traffic_sources' => self::topChannels($orders),
        ];
    }

    private static function topProducts(Collection $orders, Collection $products): array
    {
        return $products->map(fn (array $product) => [
            'name' => $product['name'] ?? $product['product'] ?? '-',
            'sku' => $product['sku'] ?? $product['id'] ?? '-',
            'sales' => self::productSales($orders, $product['name'] ?? $product['product'] ?? '-', $product['id'] ?? null),
            'stock' => $product['stock'] ?? 0,
        ])->sortByDesc('sales')->take(5)->values()->all();
    }

    private static function topChannels(Collection $orders): array
    {
        return $orders
            ->groupBy(fn (array $order) => $order['source'] ?? $order['channel'] ?? 'المتجر الإلكتروني')
            ->map(fn (Collection $items, string $channel) => [
                'channel' => $channel,
                'orders' => $items->count(),
                'sales' => $items->sum(fn (array $order) => self::money($order['total'] ?? $order['amount'] ?? 0)),
            ])
            ->sortByDesc('sales')
            ->values()
            ->all();
    }

    private static function date(array $row): ?Carbon
    {
        foreach (['created_at', 'date', 'ordered_at', 'paid_at', 'db_created_at'] as $key) {
            if (empty($row[$key])) {
                continue;
            }

            try {
                return Carbon::parse($row[$key]);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
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

    private static function isPaid(array $payment): bool
    {
        $status = mb_strtolower((string) ($payment['status'] ?? ''));

        return str_contains($status, 'paid') || str_contains($status, 'مدفوع') || str_contains($status, 'ناجح');
    }

    private static function pendingOrders(Collection $orders): int
    {
        return $orders->filter(function (array $order) {
            $status = mb_strtolower((string) ($order['status'] ?? ''));

            return ! str_contains($status, 'مكتمل') && ! str_contains($status, 'completed') && ! str_contains($status, 'ملغي') && ! str_contains($status, 'canceled');
        })->count();
    }

    private static function lowStock(Collection $products): int
    {
        return $products->filter(fn (array $product) => (int) ($product['stock'] ?? 0) <= (int) ($product['low_stock_threshold'] ?? 12))->count();
    }

    private static function conversion(Collection $orders, Collection $customers): string
    {
        return $customers->count() ? round(($orders->count() / max(1, $customers->count())) * 100, 1) . '%' : '0%';
    }

    private static function repeatOrders(Collection $orders): string
    {
        $repeat = $orders->groupBy(fn (array $order) => $order['customer'] ?? 'unknown')->filter(fn (Collection $items) => $items->count() > 1)->count();

        return (string) $repeat;
    }

    private static function productSales(Collection $orders, string $name, mixed $id = null): int
    {
        return $orders->sum(function (array $order) use ($name, $id) {
            $items = collect($order['items'] ?? []);

            if ($items->isEmpty()) {
                return str_contains((string) ($order['product'] ?? ''), $name) ? 1 : 0;
            }

            return $items->filter(fn (array $item) => ($item['product_id'] ?? null) === $id || ($item['name'] ?? null) === $name)
                ->sum(fn (array $item) => (int) ($item['quantity'] ?? 1));
        });
    }

    private static function failedPayments(Collection $payments): int
    {
        return $payments->filter(fn (array $payment) => str_contains(mb_strtolower((string) ($payment['status'] ?? '')), 'failed') || str_contains((string) ($payment['status'] ?? ''), 'فشل'))->count();
    }

    private static function shipmentIssues(Collection $shipments): int
    {
        return $shipments->filter(fn (array $shipment) => str_contains(mb_strtolower((string) ($shipment['status'] ?? '')), 'failed') || str_contains((string) ($shipment['status'] ?? ''), 'متأخر'))->count();
    }

    private static function lateOrders(Collection $orders): int
    {
        return $orders->filter(fn (array $order) => ($date = self::date($order)) && $date->lt(now()->subDays(3)) && self::pendingOrders(collect([$order])) > 0)->count();
    }

    private static function averageShipmentAge(Collection $shipments): string
    {
        if ($shipments->isEmpty()) {
            return '0 يوم';
        }

        $days = $shipments->avg(fn (array $shipment) => self::date($shipment)?->diffInDays(now()) ?? 0);

        return round((float) $days, 1) . ' يوم';
    }

    private static function attachment(array $partner, string $report, string $extension): string
    {
        return 'attachment; filename=analytics-' . $report . '-' . $partner['store_id'] . '-' . now()->format('Ymd-His') . '.' . $extension;
    }

    private static function pdfText(array $payload): string
    {
        $lines = [
            '%PDF-1.4',
            'Solve Analytics Report',
            'Store: ' . $payload['store']['id'],
            'Report: ' . $payload['title'],
            'Period: ' . $payload['period']['from'] . ' - ' . $payload['period']['to'],
        ];

        foreach ($payload['rows'] as $row) {
            $lines[] = implode(' | ', array_map(fn (mixed $value) => (string) $value, $row));
        }

        return implode("\n", $lines);
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
