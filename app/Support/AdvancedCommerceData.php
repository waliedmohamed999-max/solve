<?php

namespace App\Support;

use App\Models\PartnerStore;
use App\Models\PlatformRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AdvancedCommerceData
{
    public static function orders(): array
    {
        $orders = collect(self::platformOrders())
            ->merge(AdminSectionStore::get('orders', self::defaultOrders()))
            ->unique(fn (array $order) => $order['id'] ?? $order['order_number'] ?? (($order['store_id'] ?? '-') . ':' . Str::random(8)))
            ->values();

        return $orders->map(function (array $order, int $index) {
            $orderNumber = $order['order_number'] ?? ('SO-' . str_pad((string) ($index + 1001), 5, '0', STR_PAD_LEFT));
            $status = $order['status'] ?? 'قيد المعالجة';
            $storeId = $order['store_id'] ?? self::storeIdFromName((string) ($order['store'] ?? 'متجر أطلس'));

            $storeId = self::normalizeStoreId($storeId, (string) ($order['store'] ?? ''));

            return array_merge([
                'id' => $order['id'] ?? Str::slug($orderNumber),
                'order_number' => $orderNumber,
                'admin_reference' => $order['admin_reference'] ?? self::adminOrderReference((string) ($order['id'] ?? ''), $orderNumber),
                'store_id' => $storeId,
                'store' => $order['store'] ?? self::storeName($storeId),
                'customer_id' => $order['customer_id'] ?? 'customer-noura',
                'customer' => $order['customer'] ?? 'نورة السالم',
                'total' => $order['total'] ?? '1,248 ر.س',
                'status' => $status,
                'payment_status' => $order['payment_status'] ?? 'مدفوع',
                'shipping_status' => $order['shipping_status'] ?? 'جاهز للشحن',
                'invoice_id' => $order['invoice_id'] ?? 'INV-2026-0001',
                'shipment_id' => $order['shipment_id'] ?? 'SHP-88210',
                'payment_id' => $order['payment_id'] ?? 'PAY-44290',
                'created_at' => $order['created_at'] ?? '12 مايو 2026',
                'custom_statuses' => ['قيد المراجعة', 'بانتظار التجهيز', 'جاهز للشحن', 'تم التسليم', 'مرتجع'],
                'timeline' => self::orderTimeline($status),
                'status_history' => self::statusHistory($status),
                'internal_notes' => [
                    'العميل طلب تغليف هدية.',
                    'تم تأكيد العنوان مع شركة الشحن.',
                ],
                'linked' => [
                    'customer' => $order['customer'] ?? 'نورة السالم',
                    'shipment' => $order['shipment_id'] ?? 'SHP-88210',
                    'payment' => $order['payment_id'] ?? 'PAY-44290',
                ],
            ], $order);
        })->values()->all();
    }

    public static function adminOrdersDashboard(Request $request): array
    {
        $stores = collect(self::adminStores())->keyBy('id');
        $orders = collect(self::orders());
        $selectedStoreId = (string) $request->query('store_id', '');
        $query = Str::lower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', 'all'));
        $payment = trim((string) $request->query('payment_status', 'all'));

        $filtered = $orders
            ->when($selectedStoreId !== '', fn ($rows) => $rows->where('store_id', $selectedStoreId))
            ->filter(fn (array $order) => $query === '' || Str::contains(Str::lower(json_encode($order, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $order) => $status === 'all' || ($order['status'] ?? '') === $status)
            ->filter(fn (array $order) => $payment === 'all' || ($order['payment_status'] ?? '') === $payment)
            ->values();

        $storeCards = $stores->map(function (array $store) use ($orders, $selectedStoreId) {
            $rows = $orders->where('store_id', $store['id'])->values();

            return [
                'id' => $store['id'],
                'partner_id' => $store['partner_id'] ?? Str::after((string) $store['id'], 'store-'),
                'name' => $store['name'],
                'owner' => $store['owner'] ?? '-',
                'plan' => $store['plan'] ?? 'Starter',
                'status' => $store['status'] ?? 'نشط',
                'orders_count' => $rows->count(),
                'sales_total' => self::formatMoney($rows->sum(fn (array $order) => self::moneyToNumber($order['total'] ?? 0))),
                'pending_count' => $rows->filter(fn (array $order) => ! Str::contains((string) ($order['status'] ?? ''), ['تم التسليم', 'مكتمل', 'delivered', 'completed']))->count(),
                'paid_count' => $rows->filter(fn (array $order) => Str::contains((string) ($order['payment_status'] ?? ''), ['مدفوع', 'paid']))->count(),
                'last_order' => $rows->first()['order_number'] ?? '-',
                'url' => route('admin.orders', ['store_id' => $store['id']]),
                'active' => $selectedStoreId === $store['id'],
            ];
        })->values()->all();

        $selectedStore = $selectedStoreId !== '' ? $stores->get($selectedStoreId) : null;

        return [
            'activeRoute' => 'admin.orders',
            'title' => 'إدارة طلبات المتاجر',
            'summary' => 'تحكم مركزي في طلبات كل متجر وشريك، مع ملخص لكل متجر وفلترة مباشرة حسب store_id.',
            'stores' => $storeCards,
            'selectedStore' => $selectedStore,
            'filters' => [
                'store_id' => $selectedStoreId,
                'q' => $request->query('q', ''),
                'status' => $status,
                'payment_status' => $payment,
            ],
            'statusOptions' => $orders->pluck('status')->filter()->unique()->values()->all(),
            'paymentOptions' => $orders->pluck('payment_status')->filter()->unique()->values()->all(),
            'stats' => [
                ['label' => 'إجمالي الطلبات', 'value' => (string) $filtered->count(), 'hint' => $selectedStore['name'] ?? 'كل المتاجر'],
                ['label' => 'إجمالي المبيعات', 'value' => self::formatMoney($filtered->sum(fn (array $order) => self::moneyToNumber($order['total'] ?? 0))), 'hint' => 'من الطلبات المعروضة'],
                ['label' => 'مدفوع', 'value' => (string) $filtered->filter(fn (array $order) => Str::contains((string) ($order['payment_status'] ?? ''), ['مدفوع', 'paid']))->count(), 'hint' => 'عمليات مؤكدة'],
                ['label' => 'تحتاج متابعة', 'value' => (string) $filtered->filter(fn (array $order) => ! Str::contains((string) ($order['status'] ?? ''), ['تم التسليم', 'مكتمل', 'delivered', 'completed']))->count(), 'hint' => 'ليست مكتملة'],
            ],
            'orders' => $filtered->sortByDesc('created_at')->values()->all(),
            'allOrdersCount' => $orders->count(),
        ];
    }

    public static function products(): array
    {
        $products = collect(self::platformProducts())
            ->merge(AdminSectionStore::get('products', self::defaultProducts()))
            ->unique(fn (array $product) => $product['id'] ?? $product['sku'] ?? (($product['store_id'] ?? '-') . ':' . Str::random(8)))
            ->values();

        return $products->map(function (array $product, int $index) {
            $sku = $product['sku'] ?? ('SOLVE-SKU-' . ($index + 1));
            $storeId = self::normalizeStoreId($product['store_id'] ?? null, (string) ($product['store'] ?? ''));

            return array_merge([
                'id' => $product['id'] ?? Str::slug($sku),
                'store_id' => $storeId,
                'store' => $product['store'] ?? self::storeName($storeId),
                'name' => $product['name'] ?? 'منتج احترافي',
                'sku' => $sku,
                'type' => $product['type'] ?? 'متغير',
                'status' => $product['status'] ?? 'نشط',
                'price' => $product['price'] ?? '249 ر.س',
                'stock' => $product['stock'] ?? 42,
                'low_stock_threshold' => $product['low_stock_threshold'] ?? 10,
                'categories' => $product['categories'] ?? ['أزياء', 'وصل حديثا'],
                'tags' => $product['tags'] ?? ['رمضان', 'أفضل مبيع'],
                'images' => $product['images'] ?? ['product-main.jpg', 'product-gallery-1.jpg'],
                'variants' => $product['variants'] ?? [
                    ['sku' => $sku . '-BLK-M', 'option' => 'أسود / M', 'stock' => 18, 'price' => '249 ر.س'],
                    ['sku' => $sku . '-WHT-L', 'option' => 'أبيض / L', 'stock' => 6, 'price' => '259 ر.س'],
                ],
                'branch_inventory' => $product['branch_inventory'] ?? [
                    ['branch' => 'فرع الرياض', 'stock' => 18],
                    ['branch' => 'مستودع جدة', 'stock' => 24],
                ],
            ], $product);
        })->values()->all();
    }

    public static function adminProductsDashboard(Request $request): array
    {
        $stores = collect(self::adminStores())->keyBy('id');
        $products = collect(self::products());
        $orders = collect(self::orders());
        $selectedStoreId = (string) $request->query('store_id', '');
        $query = Str::lower(trim((string) $request->query('q', '')));
        $status = trim((string) $request->query('status', 'all'));
        $stock = trim((string) $request->query('stock', 'all'));

        $filtered = $products
            ->when($selectedStoreId !== '', fn ($rows) => $rows->where('store_id', $selectedStoreId))
            ->filter(fn (array $product) => $query === '' || Str::contains(Str::lower(json_encode($product, JSON_UNESCAPED_UNICODE)), $query))
            ->filter(fn (array $product) => $status === 'all' || ($product['status'] ?? '') === $status)
            ->filter(function (array $product) use ($stock) {
                $quantity = (int) ($product['stock'] ?? 0);
                $threshold = (int) ($product['low_stock_threshold'] ?? 10);

                return match ($stock) {
                    'low' => $quantity > 0 && $quantity <= $threshold,
                    'out' => $quantity <= 0,
                    'available' => $quantity > $threshold,
                    default => true,
                };
            })
            ->values();

        $storeCards = $stores->map(function (array $store) use ($products, $orders, $selectedStoreId) {
            $storeProducts = $products->where('store_id', $store['id'])->values();
            $storeOrders = $orders->where('store_id', $store['id'])->values();

            return [
                'id' => $store['id'],
                'partner_id' => $store['partner_id'] ?? Str::after((string) $store['id'], 'store-'),
                'name' => $store['name'],
                'owner' => $store['owner'] ?? '-',
                'plan' => $store['plan'] ?? 'Starter',
                'status' => $store['status'] ?? 'active',
                'products_count' => $storeProducts->count(),
                'active_count' => $storeProducts->filter(fn (array $product) => self::isActiveProduct($product))->count(),
                'low_stock_count' => $storeProducts->filter(fn (array $product) => self::isLowStockProduct($product))->count(),
                'orders_count' => $storeOrders->count(),
                'sales_total' => self::formatMoney($storeOrders->sum(fn (array $order) => self::moneyToNumber($order['total'] ?? 0))),
                'last_product' => $storeProducts->first()['name'] ?? '-',
                'url' => route('admin.products', ['store_id' => $store['id']]),
                'orders_url' => route('admin.orders', ['store_id' => $store['id']]),
                'active' => $selectedStoreId === $store['id'],
            ];
        })->values()->all();

        $selectedStore = $selectedStoreId !== '' ? $stores->get($selectedStoreId) : null;
        $selectedOrders = $selectedStoreId !== '' ? $orders->where('store_id', $selectedStoreId)->values() : $orders;

        return [
            'activeRoute' => 'admin.products',
            'title' => 'إدارة منتجات المتاجر',
            'summary' => 'تحكم مركزي في منتجات كل متجر وشريك، مع ملخص المنتجات والمخزون والطلبات حسب store_id.',
            'stores' => $storeCards,
            'selectedStore' => $selectedStore,
            'filters' => [
                'store_id' => $selectedStoreId,
                'q' => $request->query('q', ''),
                'status' => $status,
                'stock' => $stock,
            ],
            'statusOptions' => $products->pluck('status')->filter()->unique()->values()->all(),
            'stockOptions' => [
                'all' => 'كل المخزون',
                'available' => 'متوفر',
                'low' => 'مخزون منخفض',
                'out' => 'نفد',
            ],
            'stats' => [
                ['label' => 'إجمالي المنتجات', 'value' => (string) $filtered->count(), 'hint' => $selectedStore['name'] ?? 'كل المتاجر'],
                ['label' => 'منتجات نشطة', 'value' => (string) $filtered->filter(fn (array $product) => self::isActiveProduct($product))->count(), 'hint' => 'قابلة للبيع'],
                ['label' => 'مخزون منخفض', 'value' => (string) $filtered->filter(fn (array $product) => self::isLowStockProduct($product))->count(), 'hint' => 'تحتاج متابعة'],
                ['label' => 'طلبات المتجر', 'value' => (string) $selectedOrders->count(), 'hint' => self::formatMoney($selectedOrders->sum(fn (array $order) => self::moneyToNumber($order['total'] ?? 0)))],
            ],
            'products' => $filtered->sortBy('name')->values()->all(),
            'ordersSummary' => [
                'count' => $selectedOrders->count(),
                'sales' => self::formatMoney($selectedOrders->sum(fn (array $order) => self::moneyToNumber($order['total'] ?? 0))),
                'pending' => $selectedOrders->filter(fn (array $order) => ! Str::contains((string) ($order['status'] ?? ''), ['ØªÙ… Ø§Ù„ØªØ³Ù„ÙŠÙ…', 'Ù…ÙƒØªÙ…Ù„', 'delivered', 'completed', 'تم التسليم', 'مكتمل']))->count(),
                'latest' => $selectedOrders->take(5)->values()->all(),
            ],
            'allProductsCount' => $products->count(),
        ];
    }

    public static function customers(): array
    {
        $customers = AdminSectionStore::get('customers', self::defaultCustomers());
        $orders = self::orders();

        return collect($customers)->map(function (array $customer, int $index) use ($orders) {
            $id = $customer['id'] ?? ('customer-' . ($index + 1));
            $customerOrders = collect($orders)
                ->filter(fn (array $order) => ($order['customer_id'] ?? null) === $id || ($order['customer'] ?? null) === ($customer['name'] ?? null))
                ->values()
                ->all();

            return array_merge([
                'id' => $id,
                'name' => $customer['name'] ?? 'عميل Solve',
                'email' => $customer['email'] ?? 'customer@solve.sa',
                'phone' => $customer['phone'] ?? '+966500000000',
                'segment' => $customer['segment'] ?? 'VIP',
                'status' => $customer['status'] ?? 'نشط',
                'orders_count' => count($customerOrders) ?: ($customer['orders_count'] ?? 8),
                'total_spent' => $customer['total_spent'] ?? '8,940 ر.س',
                'average_order_value' => $customer['average_order_value'] ?? '1,117 ر.س',
                'last_order' => $customerOrders[0]['order_number'] ?? 'SO-01001',
                'order_history' => $customerOrders ?: array_slice($orders, 0, 2),
                'internal_notes' => $customer['internal_notes'] ?? ['يفضل التواصل عبر واتساب.', 'عميل مهتم بالعروض الموسمية.'],
                'notification_channels' => ['Email', 'SMS', 'WhatsApp'],
            ], $customer);
        })->values()->all();
    }

    public static function inventory(): array
    {
        return [
            'branches' => [
                ['id' => 'branch-riyadh', 'name' => 'فرع الرياض', 'type' => 'فرع بيع', 'status' => 'نشط', 'items' => 1840],
                ['id' => 'warehouse-jeddah', 'name' => 'مستودع جدة', 'type' => 'مستودع', 'status' => 'نشط', 'items' => 3920],
                ['id' => 'warehouse-dammam', 'name' => 'مستودع الدمام', 'type' => 'مستودع', 'status' => 'مراجعة', 'items' => 740],
            ],
            'stock' => collect(self::products())->flatMap(fn (array $product) => collect($product['branch_inventory'])->map(fn (array $row) => [
                'product' => $product['name'],
                'sku' => $product['sku'],
                'branch' => $row['branch'],
                'stock' => $row['stock'],
                'status' => $row['stock'] <= ($product['low_stock_threshold'] ?? 10) ? 'منخفض' : 'متوفر',
            ]))->values()->all(),
            'transfers' => [
                ['id' => 'TR-1001', 'from' => 'مستودع جدة', 'to' => 'فرع الرياض', 'items' => 28, 'status' => 'قيد النقل', 'date' => '12 مايو 2026'],
                ['id' => 'TR-1002', 'from' => 'مستودع الدمام', 'to' => 'مستودع جدة', 'items' => 14, 'status' => 'مكتمل', 'date' => '10 مايو 2026'],
            ],
            'movements' => [
                ['type' => 'خصم تلقائي', 'reference' => 'SO-01001', 'qty' => '-2', 'date' => '12 مايو 2026'],
                ['type' => 'توريد', 'reference' => 'PO-2201', 'qty' => '+120', 'date' => '11 مايو 2026'],
            ],
        ];
    }

    public static function invoices(): array
    {
        return [
            'settings' => ['prefix' => 'INV', 'next_number' => '2026-0042', 'vat' => '15%', 'template' => 'Solve Classic'],
            'records' => [
                ['id' => 'INV-2026-0001', 'order' => 'SO-01001', 'customer' => 'نورة السالم', 'total' => '1,248 ر.س', 'vat' => '162.78 ر.س', 'status' => 'مدفوعة', 'date' => '12 مايو 2026'],
                ['id' => 'INV-2026-0002', 'order' => 'SO-01002', 'customer' => 'سلمان العتيبي', 'total' => '879 ر.س', 'vat' => '114.65 ر.س', 'status' => 'مرسلة', 'date' => '11 مايو 2026'],
            ],
        ];
    }

    public static function plans(): array
    {
        return [
            ['name' => 'Starter', 'price' => '299 ر.س', 'status' => 'متاح', 'limits' => ['products' => 100, 'orders' => 500, 'staff' => 3, 'apps' => 4], 'locked_features' => ['تقارير متقدمة', 'API مفتوح']],
            ['name' => 'Growth', 'price' => '799 ر.س', 'status' => 'الأكثر استخداما', 'limits' => ['products' => 1000, 'orders' => 5000, 'staff' => 12, 'apps' => 12], 'locked_features' => ['White Label']],
            ['name' => 'Enterprise', 'price' => 'حسب الاتفاق', 'status' => 'نشط', 'limits' => ['products' => 'غير محدود', 'orders' => 'غير محدود', 'staff' => 'غير محدود', 'apps' => 'كل التطبيقات'], 'locked_features' => []],
        ];
    }

    public static function roles(): array
    {
        return [
            'roles' => [
                ['name' => 'Super Admin', 'scope' => 'كل المنصة', 'users' => 2],
                ['name' => 'Partner Admin', 'scope' => 'متجر محدد', 'users' => 18],
                ['name' => 'Store Staff', 'scope' => 'صلاحيات محدودة', 'users' => 64],
                ['name' => 'Support Agent', 'scope' => 'الدعم والتذاكر', 'users' => 9],
            ],
            'permissions' => [
                ['module' => 'الطلبات', 'view' => true, 'create' => true, 'update' => true, 'delete' => false, 'export' => true],
                ['module' => 'المنتجات', 'view' => true, 'create' => true, 'update' => true, 'delete' => true, 'export' => true],
                ['module' => 'العملاء', 'view' => true, 'create' => false, 'update' => true, 'delete' => false, 'export' => true],
                ['module' => 'الفواتير', 'view' => true, 'create' => true, 'update' => false, 'delete' => false, 'export' => true],
                ['module' => 'الإعدادات', 'view' => true, 'create' => false, 'update' => true, 'delete' => false, 'export' => false],
            ],
        ];
    }

    public static function merchantModule(string $module): array
    {
        $modules = [
            'products' => [
                'activeRoute' => 'admin.products',
                'title' => 'إدارة المنتجات',
                'summary' => 'إضافة منتج بسهولة، نسخ المنتجات، خيارات متعددة، صور، تصنيفات، منتجات مقترحة وحفظ تلقائي Draft.',
                'primaryAction' => 'إضافة منتج',
                'stats' => [
                    ['label' => 'منتجات نشطة', 'value' => '248', 'hint' => 'منشورة في المتجر'],
                    ['label' => 'مسودات', 'value' => '12', 'hint' => 'محفوظة تلقائيا'],
                    ['label' => 'مخزون منخفض', 'value' => '7', 'hint' => 'تحتاج متابعة'],
                    ['label' => 'منتجات مقترحة', 'value' => '18', 'hint' => 'فرص نمو'],
                ],
                'quick' => ['منتج بسيط', 'منتج متعدد الخيارات', 'نسخ منتج', 'رفع صور'],
                'table' => [
                    'title' => 'قائمة المنتجات',
                    'columns' => ['المنتج', 'SKU', 'المخزون', 'الحالة'],
                    'rows' => [
                        ['عباية أطلس الفاخرة', 'ATL-ABY-001', '24', 'نشط'],
                        ['طقم ضيافة رواء', 'RWA-HOS-014', '7', 'مخزون منخفض'],
                        ['حقيبة جلدية', 'BAG-LEA-020', 'Draft', 'مسودة'],
                    ],
                ],
                'side' => [
                    ['title' => 'رفع الصور', 'body' => 'منطقة صور متعددة مع معاينة وترتيب بالسحب.'],
                    ['title' => 'التصنيفات', 'body' => 'نظّم المنتجات حسب أقسام واضحة للمتجر.'],
                    ['title' => 'حفظ تلقائي', 'body' => 'أي تعديل يحفظ كمسودة قبل النشر.'],
                ],
            ],
            'orders' => [
                'activeRoute' => 'admin.orders',
                'title' => 'إدارة الطلبات',
                'summary' => 'Timeline واضح لكل طلب، تحديث سريع للحالة، طباعة الفاتورة، تتبع الشحنة وفلاتر ذكية.',
                'primaryAction' => 'إنشاء طلب يدوي',
                'stats' => [
                    ['label' => 'طلبات اليوم', 'value' => '64', 'hint' => 'آخر 24 ساعة'],
                    ['label' => 'بانتظار الشحن', 'value' => '12', 'hint' => 'جاهزة للتسليم'],
                    ['label' => 'دفع يحتاج مراجعة', 'value' => '4', 'hint' => 'تحتاج متابعة'],
                    ['label' => 'تم التسليم', 'value' => '41', 'hint' => 'اليوم'],
                ],
                'quick' => ['تحديث الحالة', 'طباعة فاتورة', 'ملصق شحن', 'فلترة ذكية'],
                'table' => [
                    'title' => 'آخر الطلبات',
                    'columns' => ['الطلب', 'العميل', 'الحالة', 'الإجمالي'],
                    'rows' => [
                        ['ORD-1001', 'نورة السالم', 'جاهز للشحن', '1,248 ر.س'],
                        ['SO-01002', 'سلمان العتيبي', 'قيد المعالجة', '879 ر.س'],
                        ['SO-01003', 'ريم خالد', 'تم التسليم', '432 ر.س'],
                    ],
                ],
                'side' => [
                    ['title' => 'Timeline الطلب', 'body' => 'أنشئ، دفع، تجهيز، شحن، تسليم في خط زمني واحد.'],
                    ['title' => 'حالات احترافية', 'body' => 'جاهز للشحن، بانتظار الدفع، مرتجع، مكتمل.'],
                    ['title' => 'تتبع الشحنة', 'body' => 'رابط مباشر لحالة الشحن وملصق الطباعة.'],
                ],
            ],
            'customers' => [
                'activeRoute' => 'admin.customers',
                'title' => 'إدارة العملاء',
                'summary' => 'ملف عميل مرتب، سجل الطلبات، إجمالي المشتريات، ملاحظات داخلية وتصنيف العملاء.',
                'primaryAction' => 'إضافة ملاحظة',
                'stats' => [
                    ['label' => 'إجمالي العملاء', 'value' => '8,420', 'hint' => 'كل العملاء'],
                    ['label' => 'عملاء جدد', 'value' => '31', 'hint' => 'اليوم'],
                    ['label' => 'VIP', 'value' => '312', 'hint' => 'أعلى قيمة'],
                    ['label' => 'يحتاجون متابعة', 'value' => '18', 'hint' => 'فرص بيع'],
                ],
                'quick' => ['تصنيف عميل', 'ملاحظة داخلية', 'رسالة واتساب', 'تصدير العملاء'],
                'table' => [
                    'title' => 'قائمة العملاء',
                    'columns' => ['العميل', 'الطلبات', 'إجمالي المشتريات', 'التصنيف'],
                    'rows' => [
                        ['نورة السالم', '18', '8,940 ر.س', 'VIP'],
                        ['سلمان العتيبي', '9', '4,230 ر.س', 'ذهبي'],
                        ['ريم خالد', '3', '980 ر.س', 'جديد'],
                    ],
                ],
                'side' => [
                    ['title' => 'ملف العميل', 'body' => 'كل الطلبات والملاحظات والقيمة في صفحة واحدة.'],
                    ['title' => 'تصنيف العملاء', 'body' => 'عادي، فضي، ذهبي، VIP حسب الإنفاق.'],
                    ['title' => 'متابعة ذكية', 'body' => 'عملاء يحتاجون حملة عودة أو عرض خاص.'],
                ],
            ],
            'reports' => [
                'activeRoute' => 'admin.analytics',
                'title' => 'التقارير',
                'summary' => 'تقارير بسيطة وواضحة: مبيعات اليوم، أفضل المنتجات، أفضل العملاء، والطلبات حسب الفترة.',
                'primaryAction' => 'تصدير تقرير',
                'stats' => [
                    ['label' => 'مبيعات اليوم', 'value' => '18,420 ر.س', 'hint' => '+12%'],
                    ['label' => 'متوسط الطلب', 'value' => '287 ر.س', 'hint' => 'مستقر'],
                    ['label' => 'أفضل منتج', 'value' => 'عباية أطلس', 'hint' => '624 طلب'],
                    ['label' => 'إكمال الدفع', 'value' => '72%', 'hint' => '+4%'],
                ],
                'quick' => ['اليوم', 'آخر 7 أيام', 'هذا الشهر', 'مقارنة فترة'],
                'table' => [
                    'title' => 'أداء مختصر',
                    'columns' => ['البند', 'القيمة', 'التغير', 'ملاحظة'],
                    'rows' => [
                        ['أفضل المنتجات', 'عباية أطلس', '+18%', 'زود المخزون'],
                        ['أفضل العملاء', 'نورة السالم', '8,940 ر.س', 'VIP'],
                        ['الطلبات', '64', '+12%', 'اليوم'],
                    ],
                ],
                'side' => [
                    ['title' => 'رسوم واضحة', 'body' => 'مخططات بسيطة بدون ازدحام.'],
                    ['title' => 'أفضل المنتجات', 'body' => 'يعرض فرص المخزون والحملات.'],
                    ['title' => 'تصدير سريع', 'body' => 'CSV / PDF للتقارير الأساسية.'],
                ],
            ],
            'integrations' => [
                'activeRoute' => 'admin.integrations',
                'title' => 'مركز التكاملات والتطبيقات',
                'summary' => 'صفحة تطبيقات مثل زد: بوابات دفع، شركات شحن، تسويق، وتفعيل بسيط من نفس الصفحة.',
                'primaryAction' => 'تصفح التطبيقات',
                'stats' => [
                    ['label' => 'تطبيقات متصلة', 'value' => '9', 'hint' => 'جاهزة'],
                    ['label' => 'تحتاج إعداد', 'value' => '4', 'hint' => 'مفاتيح ناقصة'],
                    ['label' => 'بوابات دفع', 'value' => '3', 'hint' => 'Moyasar / HyperPay'],
                    ['label' => 'شركات شحن', 'value' => '5', 'hint' => 'Aramex / SPL'],
                ],
                'quick' => ['بوابات الدفع', 'شركات الشحن', 'WhatsApp', 'تطبيقات التسويق'],
                'table' => [
                    'title' => 'التطبيقات الأساسية',
                    'columns' => ['التطبيق', 'الفئة', 'الحالة', 'الإجراء'],
                    'rows' => [
                        ['Moyasar', 'بوابة دفع', 'متصل', 'إعداد'],
                        ['Aramex', 'شركة شحن', 'متصل', 'اختبار'],
                        ['WhatsApp Business', 'رسائل', 'يحتاج إعداد', 'إكمال'],
                        ['TikTok Pixel', 'تسويق', 'غير متصل', 'تفعيل'],
                        ['Meta Pixel', 'تسويق', 'متصل', 'إدارة'],
                    ],
                ],
                'side' => [
                    ['title' => 'تثبيت سهل', 'body' => 'زر واحد للتفعيل ثم شاشة إعداد بسيطة.'],
                    ['title' => 'حالة واضحة', 'body' => 'متصل، غير متصل، يحتاج إعداد.'],
                    ['title' => 'بدون تعقيد', 'body' => 'لا تظهر تفاصيل تقنية إلا عند الحاجة.'],
                ],
            ],
            'subscriptions' => [
                'activeRoute' => 'admin.subscriptions',
                'title' => 'الباقات والاشتراك',
                'summary' => 'مقارنة باقات مرتبة، ترقية الباقة، إدارة الفواتير وحالة الاشتراك.',
                'primaryAction' => 'ترقية الباقة',
                'stats' => [
                    ['label' => 'الباقة الحالية', 'value' => 'Growth', 'hint' => 'نشطة'],
                    ['label' => 'تاريخ التجديد', 'value' => '12 يونيو', 'hint' => '2026'],
                    ['label' => 'الفواتير', 'value' => '12', 'hint' => 'مدفوعة'],
                    ['label' => 'الحدود', 'value' => '68%', 'hint' => 'استخدام الباقة'],
                ],
                'quick' => ['مقارنة الباقات', 'ترقية', 'الفواتير', 'طريقة الدفع'],
                'table' => [
                    'title' => 'مقارنة سريعة',
                    'columns' => ['الباقة', 'السعر', 'الحدود', 'الحالة'],
                    'rows' => [
                        ['Starter', '299 ر.س', '100 منتج', 'متاح'],
                        ['Growth', '799 ر.س', '1000 منتج', 'الحالية'],
                        ['Enterprise', 'حسب الاتفاق', 'غير محدود', 'ترقية'],
                    ],
                ],
                'side' => [
                    ['title' => 'حالة الاشتراك', 'body' => 'واضحة ومباشرة بدون تفاصيل مالية معقدة.'],
                    ['title' => 'الفواتير', 'body' => 'تحميل الفواتير ومراجعة حالة الدفع.'],
                    ['title' => 'ترقية الباقة', 'body' => 'اعرف ما ستحصل عليه قبل الترقية.'],
                ],
            ],
        ];

        return $modules[$module] ?? $modules['products'];
    }

    public static function enterpriseModule(string $module): array
    {
        $modules = array_merge([
            'integrations' => [
                'activeRoute' => 'admin.integrations',
                'title' => 'مركز التكاملات',
                'eyebrow' => 'Integrations Hub',
                'summary' => 'ربط بوابات الدفع، الشحن، الرسائل، والتحليلات من مكان واحد مع حالة كل تكامل.',
                'stats' => [
                    ['label' => 'تكاملات متصلة', 'value' => '9'],
                    ['label' => 'تحتاج إعداد', 'value' => '4'],
                    ['label' => 'تكاملات غير متصلة', 'value' => '6'],
                    ['label' => 'آخر مزامنة', 'value' => 'قبل 8 دقائق'],
                ],
                'sections' => [
                    self::cardsSection('بوابات الدفع', [
                        ['name' => 'Moyasar', 'status' => 'متصل', 'meta' => 'مدفوعات وبطاقات مدى'],
                        ['name' => 'HyperPay', 'status' => 'يحتاج إعداد', 'meta' => 'يتطلب مفاتيح الإنتاج'],
                        ['name' => 'Tabby / Tamara', 'status' => 'غير متصل', 'meta' => 'الدفع الآجل'],
                    ]),
                    self::cardsSection('الشحن والرسائل والتحليلات', [
                        ['name' => 'Aramex', 'status' => 'متصل', 'meta' => 'تتبع مباشر وملصقات'],
                        ['name' => 'WhatsApp Business', 'status' => 'يحتاج إعداد', 'meta' => 'قوالب OTP والطلبات'],
                        ['name' => 'Google Analytics', 'status' => 'متصل', 'meta' => 'قياس التحويلات'],
                        ['name' => 'Meta Pixel', 'status' => 'متصل', 'meta' => 'إعلانات وإعادة استهداف'],
                        ['name' => 'TikTok Pixel', 'status' => 'غير متصل', 'meta' => 'تتبع الحملات'],
                    ]),
                ],
            ],
            'automation' => [
                'activeRoute' => 'admin.automation',
                'title' => 'نظام Automation',
                'eyebrow' => 'Rule Builder',
                'summary' => 'قواعد تلقائية بسيطة وواضحة لإدارة إشعارات الطلبات والمخزون والدفع والاشتراكات.',
                'stats' => [
                    ['label' => 'قواعد نشطة', 'value' => '12'],
                    ['label' => 'تشغيل اليوم', 'value' => '248'],
                    ['label' => 'فشل يحتاج مراجعة', 'value' => '3'],
                    ['label' => 'متوسط التنفيذ', 'value' => '1.4s'],
                ],
                'sections' => [
                    self::tableSection('قواعد جاهزة', ['الحدث', 'الشرط', 'الإجراء', 'الحالة'], [
                        ['طلب جديد', 'كل المتاجر', 'إرسال إشعار WhatsApp', 'نشطة'],
                        ['انخفاض المخزون', 'أقل من 10', 'إرسال تنبيه للمدير', 'نشطة'],
                        ['فشل الدفع', '3 محاولات', 'إنشاء تذكرة دعم', 'نشطة'],
                        ['انتهاء الاشتراك', 'بعد تاريخ التجديد', 'إيقاف المتجر مؤقتا', 'مراجعة'],
                    ]),
                    self::builderSection('واجهة Rule Builder', ['Trigger', 'Condition', 'Action', 'Retry']),
                ],
            ],
            'developer' => [
                'activeRoute' => 'admin.developer',
                'title' => 'Webhooks و API Keys',
                'eyebrow' => 'Developer Access',
                'summary' => 'مفاتيح API لكل متجر، صلاحيات دقيقة، Webhooks، Logs و Rate Limits.',
                'stats' => [
                    ['label' => 'API Keys', 'value' => '18'],
                    ['label' => 'Webhooks نشطة', 'value' => '27'],
                    ['label' => 'طلبات آخر ساعة', 'value' => '12.4K'],
                    ['label' => 'Rate Limit Alerts', 'value' => '2'],
                ],
                'sections' => [
                    self::tableSection('مفاتيح API', ['المتجر', 'Key', 'الصلاحيات', 'Rate Limit'], [
                        ['متجر أطلس', 'sk_live_atl_****82', 'orders:read, products:write', '1200/min'],
                        ['متجر رواء', 'sk_live_rwa_****41', 'orders:read, customers:read', '600/min'],
                    ]),
                    self::tableSection('Webhooks و Logs', ['Event', 'Endpoint', 'آخر استجابة', 'الحالة'], [
                        ['order.created', 'https://atlas.sa/hooks/orders', '200 OK', 'نشط'],
                        ['payment.failed', 'https://rowaa.sa/hooks/payments', '500 Retry', 'إعادة محاولة'],
                    ]),
                ],
            ],
            'messages' => [
                'activeRoute' => 'admin.messages',
                'title' => 'مركز الرسائل',
                'eyebrow' => 'Messaging Center',
                'summary' => 'رسائل العملاء والدعم والقوالب الجاهزة عبر WhatsApp وEmail وSMS.',
                'stats' => [
                    ['label' => 'رسائل اليوم', 'value' => '1,284'],
                    ['label' => 'قوالب فعالة', 'value' => '16'],
                    ['label' => 'محادثات مفتوحة', 'value' => '38'],
                    ['label' => 'معدل الوصول', 'value' => '97.8%'],
                ],
                'sections' => [
                    self::tableSection('سجل الرسائل', ['القناة', 'المستلم', 'القالب', 'الحالة'], [
                        ['WhatsApp', 'نورة السالم', 'تأكيد الطلب', 'وصلت'],
                        ['Email', 'سلمان العتيبي', 'الفاتورة', 'مفتوحة'],
                        ['SMS', 'متجر أطلس', 'تنبيه مخزون', 'وصلت'],
                    ]),
                    self::cardsSection('قوالب جاهزة', [
                        ['name' => 'طلب جديد', 'status' => 'WhatsApp', 'meta' => 'رسالة تأكيد تلقائية'],
                        ['name' => 'دفع جديد', 'status' => 'Email', 'meta' => 'إيصال دفع وفاتورة'],
                        ['name' => 'تذكرة دعم', 'status' => 'SMS', 'meta' => 'إشعار تحديث حالة'],
                    ]),
                ],
            ],
            'reviews' => [
                'activeRoute' => 'admin.reviews',
                'title' => 'المراجعات والتقييمات',
                'eyebrow' => 'Reviews Moderation',
                'summary' => 'إدارة تقييمات المنتجات والمتاجر، الموافقة والرفض والردود الرسمية.',
                'stats' => [
                    ['label' => 'تقييمات جديدة', 'value' => '42'],
                    ['label' => 'متوسط النجوم', 'value' => '4.7'],
                    ['label' => 'بانتظار المراجعة', 'value' => '9'],
                    ['label' => 'ردود الأدمن', 'value' => '31'],
                ],
                'sections' => [
                    self::tableSection('قائمة التقييمات', ['النوع', 'العنصر', 'النجوم', 'الحالة'], [
                        ['منتج', 'عباية أطلس الفاخرة', '5', 'بانتظار الموافقة'],
                        ['متجر', 'متجر رواء', '4', 'منشور'],
                        ['منتج', 'طقم ضيافة رواء', '3', 'يحتاج رد'],
                    ]),
                ],
            ],
            'financials' => [
                'activeRoute' => 'admin.financials',
                'title' => 'تقارير مالية متقدمة',
                'eyebrow' => 'Finance Reports',
                'summary' => 'صافي المبيعات والعمولات والرسوم والضرائب والمبالغ المستحقة لكل متجر.',
                'stats' => [
                    ['label' => 'صافي المبيعات', 'value' => '1.84M ر.س'],
                    ['label' => 'عمولات المنصة', 'value' => '82.4K ر.س'],
                    ['label' => 'الضرائب', 'value' => '276K ر.س'],
                    ['label' => 'مستحقات المتاجر', 'value' => '1.48M ر.س'],
                ],
                'sections' => [
                    self::tableSection('تحليل مالي حسب المتجر', ['المتجر', 'المبيعات', 'العمولة', 'المستحق'], [
                        ['متجر أطلس', '418,200 ر.س', '18,819 ر.س', '399,381 ر.س'],
                        ['متجر رواء', '276,900 ر.س', '11,076 ر.س', '265,824 ر.س'],
                    ]),
                    self::builderSection('تصدير التقارير', ['Excel', 'PDF', 'فترة مقارنة', 'فلترة حسب المتجر']),
                ],
            ],
            'payouts' => [
                'activeRoute' => 'admin.payouts',
                'title' => 'نظام التسويات Payouts',
                'eyebrow' => 'Merchant Payouts',
                'summary' => 'طلبات سحب الأرباح، مراجعة الأدمن، حالة التحويل، وربط الحساب البنكي للمتجر.',
                'stats' => [
                    ['label' => 'طلبات معلقة', 'value' => '7'],
                    ['label' => 'تم تحويلها', 'value' => '23'],
                    ['label' => 'قيد المراجعة', 'value' => '4'],
                    ['label' => 'إجمالي الشهر', 'value' => '642K ر.س'],
                ],
                'sections' => [
                    self::tableSection('سجل التسويات', ['المتجر', 'المبلغ', 'الحساب البنكي', 'الحالة'], [
                        ['متجر أطلس', '92,400 ر.س', 'SA****4421', 'جاهز للتحويل'],
                        ['متجر رواء', '41,100 ر.س', 'SA****9012', 'قيد المراجعة'],
                    ]),
                ],
            ],
            'security-center' => [
                'activeRoute' => 'admin.security-center',
                'title' => 'مركز الأمان',
                'eyebrow' => 'Security Center',
                'summary' => 'Two Factor Authentication، الأجهزة الموثوقة، سجل الدخول، والتنبيهات المشبوهة.',
                'stats' => [
                    ['label' => '2FA مفعل', 'value' => '86%'],
                    ['label' => 'أجهزة موثوقة', 'value' => '124'],
                    ['label' => 'تنبيهات مشبوهة', 'value' => '5'],
                    ['label' => 'IP Whitelist', 'value' => '8'],
                ],
                'sections' => [
                    self::tableSection('Login History', ['المستخدم', 'IP', 'الجهاز', 'الحالة'], [
                        ['admin@solve.sa', '185.12.44.10', 'Chrome / Windows', 'موثوق'],
                        ['ops@solve.sa', '91.22.10.7', 'Safari / iOS', 'يتطلب تحقق'],
                    ]),
                    self::cardsSection('سياسات الأمان', [
                        ['name' => 'Two Factor Authentication', 'status' => 'مفعل', 'meta' => 'إلزامي للأدمن'],
                        ['name' => 'Trusted Devices', 'status' => 'مفعل', 'meta' => 'صلاحية 30 يوم'],
                        ['name' => 'IP Whitelist', 'status' => 'يحتاج إعداد', 'meta' => 'مكاتب الإدارة فقط'],
                    ]),
                ],
            ],
            'merchant-experience' => [
                'activeRoute' => 'admin.merchant-experience',
                'title' => 'تجربة تجهيز المتجر',
                'eyebrow' => 'Merchant Experience',
                'summary' => 'Setup Checklist، مؤشر اكتمال الإعداد، نصائح ذكية و Guided Tour.',
                'stats' => [
                    ['label' => 'متوسط الاكتمال', 'value' => '74%'],
                    ['label' => 'متاجر جاهزة', 'value' => '31'],
                    ['label' => 'تحتاج دفع', 'value' => '8'],
                    ['label' => 'تحتاج دومين', 'value' => '11'],
                ],
                'sections' => [
                    self::tableSection('Setup Checklist', ['الخطوة', 'الوصف', 'الأثر', 'الحالة'], [
                        ['بيانات المتجر', 'الاسم واللوجو والهوية', '+20%', 'مكتمل'],
                        ['الدفع', 'تفعيل بوابة الدفع', '+25%', 'يحتاج إعداد'],
                        ['الشحن', 'ربط شركة شحن', '+20%', 'مكتمل'],
                        ['أول منتج', 'إضافة منتج قابل للبيع', '+20%', 'مكتمل'],
                        ['الدومين', 'ربط دومين المتجر', '+15%', 'بانتظار DNS'],
                    ]),
                    self::cardsSection('نصائح ذكية', [
                        ['name' => 'أضف صور منتجات أوضح', 'status' => 'فرصة نمو', 'meta' => 'ترفع التحويل المتوقع 8%'],
                        ['name' => 'فعّل الدفع الآجل', 'status' => 'اقتراح', 'meta' => 'مفيد لمتاجر الأزياء'],
                    ]),
                ],
            ],
            'operations' => [
                'activeRoute' => 'admin.operations',
                'title' => 'الأداء والاستقرار',
                'eyebrow' => 'Operations Center',
                'summary' => 'Virtualized Tables، Background Jobs، Queues، Retry، ومراقبة الأخطاء.',
                'stats' => [
                    ['label' => 'Queue Jobs', 'value' => '3,842'],
                    ['label' => 'Retry Pending', 'value' => '17'],
                    ['label' => 'Error Rate', 'value' => '0.08%'],
                    ['label' => 'Avg Response', 'value' => '128ms'],
                ],
                'sections' => [
                    self::tableSection('Monitoring & Jobs', ['الخدمة', 'الحالة', 'آخر تشغيل', 'ملاحظة'], [
                        ['orders-sync', 'Healthy', 'قبل دقيقة', 'يعمل عبر Queue'],
                        ['webhook-retry', 'Degraded', 'قبل 4 دقائق', '17 إعادة محاولة'],
                        ['report-export', 'Healthy', 'قبل 12 دقيقة', 'Background Job'],
                    ]),
                    self::builderSection('جاهزية الأداء', ['Virtualized Tables', 'Queue System', 'Retry Mechanism', 'Error Tracking']),
                ],
            ],
        ], self::commerceGrowthModules(), self::commerceOsModules(), self::commerceEcosystemModules());

        return $modules[$module] ?? $modules['integrations'];
    }

    private static function commerceGrowthModules(): array
    {
        return [
            'marketing-campaigns' => [
                'activeRoute' => 'admin.marketing-campaigns',
                'title' => 'الحملات التسويقية',
                'eyebrow' => 'Marketing Campaigns',
                'summary' => 'إنشاء حملات خصومات وكوبونات وشحن مجاني للعملاء الجدد مع جدولة وقياس أداء واضح.',
                'stats' => [
                    ['label' => 'حملات نشطة', 'value' => '14'],
                    ['label' => 'إيراد من الحملات', 'value' => '286K ر.س'],
                    ['label' => 'معدل التحويل', 'value' => '8.9%'],
                    ['label' => 'كوبونات مستخدمة', 'value' => '1,842'],
                ],
                'sections' => [
                    self::tableSection('الحملات الحالية', ['الحملة', 'النوع', 'الفترة', 'الأداء'], [
                        ['خصم نهاية الأسبوع', 'خصم مباشر 20%', '12 مايو - 15 مايو', '128K ر.س'],
                        ['استرجاع العملاء الجدد', 'كوبون ترحيبي', 'مستمر', '14.2% تحويل'],
                        ['شحن مجاني للرياض', 'شحن مجاني', '10 مايو - 20 مايو', '642 طلب'],
                    ]),
                    self::builderSection('Campaign Builder', ['الهدف', 'الجمهور', 'العرض', 'الجدولة']),
                ],
            ],
            'loyalty' => [
                'activeRoute' => 'admin.loyalty',
                'title' => 'الولاء والنقاط',
                'eyebrow' => 'Loyalty Program',
                'summary' => 'نقاط لكل عملية شراء، استبدال النقاط بخصومات، مستويات العملاء وسجل نقاط كامل.',
                'stats' => [
                    ['label' => 'عملاء لديهم نقاط', 'value' => '8,420'],
                    ['label' => 'نقاط مكتسبة', 'value' => '1.2M'],
                    ['label' => 'نقاط مستبدلة', 'value' => '284K'],
                    ['label' => 'VIP', 'value' => '312'],
                ],
                'sections' => [
                    self::cardsSection('مستويات العملاء', [
                        ['name' => 'عادي', 'status' => 'نشط', 'meta' => '1 نقطة لكل 10 ر.س'],
                        ['name' => 'فضي', 'status' => 'نشط', 'meta' => '1.5 نقطة لكل 10 ر.س'],
                        ['name' => 'ذهبي', 'status' => 'نشط', 'meta' => '2 نقطة لكل 10 ر.س'],
                        ['name' => 'VIP', 'status' => 'نشط', 'meta' => 'مضاعفة نقاط وعروض خاصة'],
                    ]),
                    self::tableSection('سجل النقاط', ['العميل', 'المستوى', 'النقاط', 'آخر عملية'], [
                        ['نورة السالم', 'VIP', '18,420', '+240 من طلب SO-01001'],
                        ['سلمان العتيبي', 'ذهبي', '7,840', 'استبدال 500 نقطة'],
                    ]),
                ],
            ],
            'abandoned-carts' => [
                'activeRoute' => 'admin.abandoned-carts',
                'title' => 'السلات المتروكة',
                'eyebrow' => 'Abandoned Carts',
                'summary' => 'عرض السلات غير المكتملة، إرسال تذكيرات تلقائية، كوبونات استرجاع وتحليل أسباب الترك.',
                'stats' => [
                    ['label' => 'سلات متروكة', 'value' => '436'],
                    ['label' => 'قيمة مفقودة', 'value' => '92K ر.س'],
                    ['label' => 'تم استرجاعها', 'value' => '18.4%'],
                    ['label' => 'كوبونات استرجاع', 'value' => '124'],
                ],
                'sections' => [
                    self::tableSection('السلات غير المكتملة', ['العميل', 'القيمة', 'سبب محتمل', 'الإجراء'], [
                        ['عميل بدون تسجيل', '420 ر.س', 'تكلفة الشحن', 'إرسال كوبون شحن'],
                        ['نورة السالم', '1,120 ر.س', 'تردد في الدفع', 'رسالة WhatsApp'],
                    ]),
                    self::builderSection('استرجاع تلقائي', ['بعد ساعة', 'بعد 24 ساعة', 'كوبون استرجاع', 'قياس النتيجة']),
                ],
            ],
            'smart-recommendations' => [
                'activeRoute' => 'admin.smart-recommendations',
                'title' => 'توصيات ذكية',
                'eyebrow' => 'Smart Recommendations',
                'summary' => 'أفضل المنتجات مبيعا، منتجات مقترحة، عملاء يحتاجون متابعة وتنبيهات فرص نمو.',
                'stats' => [
                    ['label' => 'فرص نمو', 'value' => '23'],
                    ['label' => 'منتجات مقترحة', 'value' => '18'],
                    ['label' => 'عملاء للمتابعة', 'value' => '64'],
                    ['label' => 'أثر متوقع', 'value' => '+11%'],
                ],
                'sections' => [
                    self::cardsSection('تنبيهات فرص النمو', [
                        ['name' => 'عباية أطلس الفاخرة', 'status' => 'أفضل مبيع', 'meta' => 'ارفع المخزون قبل نهاية الأسبوع'],
                        ['name' => 'عملاء لم يشتروا منذ 45 يوم', 'status' => 'فرصة نمو', 'meta' => 'أرسل حملة عودة بكوبون 10%'],
                        ['name' => 'منتجات ناقصة صور', 'status' => 'اقتراح', 'meta' => 'تحسين الصور يرفع التحويل المتوقع'],
                    ]),
                    self::tableSection('أفضل المنتجات', ['المنتج', 'المبيعات', 'الهامش', 'التوصية'], [
                        ['عباية أطلس الفاخرة', '624 طلب', '38%', 'زيادة المخزون'],
                        ['طقم ضيافة رواء', '312 طلب', '31%', 'حملة Bundle'],
                    ]),
                ],
            ],
            'store-content' => [
                'activeRoute' => 'admin.store-content',
                'title' => 'إدارة محتوى المتجر',
                'eyebrow' => 'Store Content',
                'summary' => 'إدارة صفحات المتجر والبنرات والأقسام والأسئلة الشائعة وسياسات الاسترجاع والخصوصية.',
                'stats' => [
                    ['label' => 'صفحات منشورة', 'value' => '42'],
                    ['label' => 'بنرات نشطة', 'value' => '11'],
                    ['label' => 'أقسام المتاجر', 'value' => '86'],
                    ['label' => 'سياسات مكتملة', 'value' => '91%'],
                ],
                'sections' => [
                    self::tableSection('المحتوى', ['العنصر', 'النوع', 'المتجر', 'الحالة'], [
                        ['بنر العودة الصيفية', 'Banner', 'متجر أطلس', 'منشور'],
                        ['سياسة الاسترجاع', 'Policy', 'كل المتاجر', 'مراجعة'],
                        ['الأسئلة الشائعة', 'FAQ', 'متجر رواء', 'منشور'],
                    ]),
                    self::builderSection('Content Blocks', ['صفحة', 'بنر', 'قسم', 'سياسة']),
                ],
            ],
            'commissions' => [
                'activeRoute' => 'admin.commissions',
                'title' => 'نظام العمولات',
                'eyebrow' => 'Commissions',
                'summary' => 'تحديد عمولة لكل باقة أو متجر، حساب عمولة المنصة تلقائيا وربطها بالتسويات.',
                'stats' => [
                    ['label' => 'عمولات الشهر', 'value' => '82.4K ر.س'],
                    ['label' => 'متوسط العمولة', 'value' => '4.2%'],
                    ['label' => 'مرتبطة بالتسويات', 'value' => '100%'],
                    ['label' => 'استثناءات نشطة', 'value' => '6'],
                ],
                'sections' => [
                    self::tableSection('قواعد العمولة', ['النطاق', 'القيمة', 'التطبيق', 'الحالة'], [
                        ['Starter', '5%', 'كل المتاجر على الباقة', 'نشط'],
                        ['Growth', '4%', 'كل المتاجر على الباقة', 'نشط'],
                        ['متجر أطلس', '3.5%', 'استثناء متجر', 'نشط'],
                    ]),
                    self::tableSection('تقارير العمولات', ['المتجر', 'المبيعات', 'العمولة', 'التسوية'], [
                        ['متجر أطلس', '418,200 ر.س', '14,637 ر.س', 'مرتبطة'],
                        ['متجر رواء', '276,900 ر.س', '11,076 ر.س', 'مرتبطة'],
                    ]),
                ],
            ],
            'store-health' => [
                'activeRoute' => 'admin.store-health',
                'title' => 'مركز صحة المتجر',
                'eyebrow' => 'Store Health',
                'summary' => 'تقييم أداء المتجر، سرعة الطلبات، إكمال الدفع، المنتجات الناقصة ومشاكل الشحن.',
                'stats' => [
                    ['label' => 'متوسط الصحة', 'value' => '86%'],
                    ['label' => 'مشاكل شحن', 'value' => '12'],
                    ['label' => 'منتجات ناقصة', 'value' => '28'],
                    ['label' => 'إكمال الدفع', 'value' => '72%'],
                ],
                'sections' => [
                    self::tableSection('تقييم المتاجر', ['المتجر', 'الصحة', 'أكبر مشكلة', 'اقتراح التحسين'], [
                        ['متجر أطلس', '92%', 'مخزون منخفض', 'رفع كمية أفضل منتج'],
                        ['متجر رواء', '78%', 'تأخير شحن', 'تفعيل مزود شحن إضافي'],
                    ]),
                    self::cardsSection('اقتراحات تحسين', [
                        ['name' => 'حسن سرعة تجهيز الطلب', 'status' => 'فرصة نمو', 'meta' => 'خفض متوسط التجهيز من 18 إلى 12 ساعة'],
                        ['name' => 'أضف بوابة دفع ثانية', 'status' => 'اقتراح', 'meta' => 'يرفع إكمال الدفع عند فشل بوابة واحدة'],
                    ]),
                ],
            ],
            'moderation' => [
                'activeRoute' => 'admin.moderation',
                'title' => 'البلاغات والمخالفات',
                'eyebrow' => 'Moderation Center',
                'summary' => 'إدارة البلاغات على المتاجر والمنتجات، مراجعة الأدمن، الإيقاف وسجل القرارات.',
                'stats' => [
                    ['label' => 'بلاغات مفتوحة', 'value' => '19'],
                    ['label' => 'قرارات اليوم', 'value' => '7'],
                    ['label' => 'منتجات موقوفة', 'value' => '3'],
                    ['label' => 'متاجر تحت المراجعة', 'value' => '2'],
                ],
                'sections' => [
                    self::tableSection('قائمة البلاغات', ['النوع', 'العنصر', 'الأولوية', 'الحالة'], [
                        ['منتج', 'منتج بدون وصف واضح', 'متوسطة', 'قيد المراجعة'],
                        ['متجر', 'تأخر تسليم متكرر', 'عالية', 'تصعيد'],
                    ]),
                    self::tableSection('سجل القرارات', ['القرار', 'العنصر', 'المراجع', 'التاريخ'], [
                        ['إيقاف منتج', 'RWA-HOS-014', 'مدير الجودة', '12 مايو 2026'],
                        ['طلب مستندات', 'متجر جديد', 'فريق الامتثال', '11 مايو 2026'],
                    ]),
                ],
            ],
            'workspace-tools' => [
                'activeRoute' => 'admin.workspace-tools',
                'title' => 'أدوات تجربة العمل',
                'eyebrow' => 'Workspace Tools',
                'summary' => 'Command Palette، اختصارات، حفظ الفلاتر، Views مخصصة و Drag & Drop لترتيب الأعمدة.',
                'stats' => [
                    ['label' => 'Views محفوظة', 'value' => '34'],
                    ['label' => 'فلاتر محفوظة', 'value' => '61'],
                    ['label' => 'اختصارات مفعلة', 'value' => '12'],
                    ['label' => 'جداول مخصصة', 'value' => '18'],
                ],
                'sections' => [
                    self::builderSection('Command Palette', ['Ctrl + K', 'بحث سريع', 'إجراءات مباشرة', 'نتائج منظمة']),
                    self::tableSection('Views مخصصة', ['الصفحة', 'اسم View', 'الأعمدة', 'آخر استخدام'], [
                        ['الطلبات', 'طلبات تحتاج شحن', '6 أعمدة', 'اليوم'],
                        ['المنتجات', 'مخزون منخفض', '5 أعمدة', 'أمس'],
                    ]),
                ],
            ],
            'production-readiness' => [
                'activeRoute' => 'admin.production-readiness',
                'title' => 'جاهزية الإنتاج',
                'eyebrow' => 'Production Readiness',
                'summary' => 'Environment Config، Backup Strategy، Database Indexing، Logging، Monitoring و Error Boundaries.',
                'stats' => [
                    ['label' => 'جاهزية الإطلاق', 'value' => '88%'],
                    ['label' => 'Backups ناجحة', 'value' => '14/14'],
                    ['label' => 'Indexes مقترحة', 'value' => '9'],
                    ['label' => 'اختبارات حرجة', 'value' => '31'],
                ],
                'sections' => [
                    self::tableSection('Checklist الإنتاج', ['البند', 'الحالة', 'الأثر', 'ملاحظة'], [
                        ['Environment Config', 'مكتمل', 'تشغيل آمن', 'ملفات env مفصولة'],
                        ['Backup Strategy', 'مكتمل', 'استرجاع البيانات', 'نسخ يومي'],
                        ['Database Indexing', 'مراجعة', 'تسريع التقارير', 'إضافة indexes قبل الإنتاج'],
                        ['Monitoring', 'مراجعة', 'رصد الأخطاء', 'ربط مزود مراقبة'],
                    ]),
                    self::builderSection('إطلاق تجاري منظم', ['Config', 'Backup', 'Indexes', 'Monitoring']),
                ],
            ],
        ];
    }

    private static function commerceOsModules(): array
    {
        return [
            'multi-vendor' => [
                'activeRoute' => 'admin.multi-vendor',
                'title' => 'Multi Vendor Marketplace',
                'eyebrow' => 'Marketplace OS',
                'summary' => 'دعم أكثر من بائع داخل نفس المنصة، تقسيم الطلبات حسب البائع، عمولات مستقلة وموافقة الأدمن.',
                'stats' => [
                    ['label' => 'بائعين نشطين', 'value' => '248'],
                    ['label' => 'طلبات مقسمة', 'value' => '1,842'],
                    ['label' => 'بانتظار الموافقة', 'value' => '17'],
                    ['label' => 'متوسط تقييم البائع', 'value' => '4.6'],
                ],
                'sections' => [
                    self::tableSection('إدارة البائعين', ['البائع', 'المتجر', 'العمولة', 'الحالة'], [
                        ['دار أطلس', 'متجر أطلس', '8%', 'نشط'],
                        ['رواء هوم', 'متجر رواء', '7.5%', 'بانتظار موافقة'],
                        ['نخبة العطور', 'Marketplace', '10%', 'تحت المراجعة'],
                    ]),
                    self::tableSection('تقسيم الطلبات', ['الطلب', 'البائع', 'قيمة الجزء', 'الحالة'], [
                        ['SO-01001-A', 'دار أطلس', '820 ر.س', 'جاهز للشحن'],
                        ['SO-01001-B', 'نخبة العطور', '428 ر.س', 'قيد التجهيز'],
                    ]),
                    self::cardsSection('لوحات البائعين', [
                        ['name' => 'لوحة مستقلة لكل بائع', 'status' => 'نشط', 'meta' => 'طلبات ومنتجات وتقييمات حسب vendor_id'],
                        ['name' => 'موافقة الأدمن', 'status' => 'مراجعة', 'meta' => 'تدقيق بيانات البائع قبل النشر'],
                        ['name' => 'تقييمات البائعين', 'status' => 'نشط', 'meta' => 'متوسط تقييم وسجل مراجعات'],
                    ]),
                ],
            ],
            'pos' => [
                'activeRoute' => 'admin.pos',
                'title' => 'نظام POS',
                'eyebrow' => 'Point of Sale',
                'summary' => 'نقطة بيع احترافية تدعم Barcode Scanner، الكاشير، النقدية، فواتير POS والمرتجعات.',
                'stats' => [
                    ['label' => 'جلسات كاشير مفتوحة', 'value' => '8'],
                    ['label' => 'مبيعات POS اليوم', 'value' => '64K ر.س'],
                    ['label' => 'مرتجعات', 'value' => '12'],
                    ['label' => 'مزامنة مخزون', 'value' => 'Live'],
                ],
                'sections' => [
                    self::builderSection('تدفق الكاشير', ['فتح الكاشير', 'Barcode Scan', 'تحصيل الدفع', 'طباعة الفاتورة']),
                    self::tableSection('جلسات POS', ['الفرع', 'الكاشير', 'النقدية', 'الحالة'], [
                        ['فرع الرياض', 'محمد', '12,840 ر.س', 'مفتوحة'],
                        ['فرع جدة', 'سارة', '8,120 ر.س', 'مغلقة'],
                    ]),
                    self::cardsSection('عمليات POS', [
                        ['name' => 'المرتجعات والاستبدال', 'status' => 'نشط', 'meta' => 'يرجع المخزون تلقائيا للفرع'],
                        ['name' => 'طباعة فاتورة POS', 'status' => 'نشط', 'meta' => 'قالب حراري سريع'],
                        ['name' => 'مزامنة المخزون', 'status' => 'نشط', 'meta' => 'خصم فوري من مخزون الفرع'],
                    ]),
                ],
            ],
            'mobile-apps' => [
                'activeRoute' => 'admin.mobile-apps',
                'title' => 'تطبيقات الجوال',
                'eyebrow' => 'Mobile APIs',
                'summary' => 'Mobile-first APIs، Push Notifications، Authentication آمن ودعم Flutter / React Native.',
                'stats' => [
                    ['label' => 'Mobile Endpoints', 'value' => '42'],
                    ['label' => 'Push Sent Today', 'value' => '18.2K'],
                    ['label' => 'Active Tokens', 'value' => '74K'],
                    ['label' => 'API Latency', 'value' => '96ms'],
                ],
                'sections' => [
                    self::tableSection('Mobile API Groups', ['المجموعة', 'الوظيفة', 'الحماية', 'الحالة'], [
                        ['Auth', 'تسجيل دخول وتجديد token', 'Sanctum / Token', 'نشط'],
                        ['Catalog', 'منتجات وتصنيفات', 'Public scoped', 'نشط'],
                        ['Orders', 'طلبات العميل', 'User token', 'نشط'],
                    ]),
                    self::cardsSection('دعم التطبيقات', [
                        ['name' => 'Flutter SDK Ready', 'status' => 'نشط', 'meta' => 'نماذج endpoints للتطبيق'],
                        ['name' => 'React Native Ready', 'status' => 'نشط', 'meta' => 'تهيئة Push وDeep Links'],
                        ['name' => 'Secure Mobile Auth', 'status' => 'نشط', 'meta' => 'Session rotation وrate limits'],
                    ]),
                ],
            ],
            'smart-analytics' => [
                'activeRoute' => 'admin.smart-analytics',
                'title' => 'Analytics ذكي',
                'eyebrow' => 'Executive Analytics',
                'summary' => 'Dashboard تنفيذية، توقع المبيعات، تحليل العملاء والمنتجات، P&L وHeatmaps للمبيعات.',
                'stats' => [
                    ['label' => 'توقع مبيعات الشهر', 'value' => '2.4M ر.س'],
                    ['label' => 'نمو متوقع', 'value' => '+14%'],
                    ['label' => 'أفضل متجر', 'value' => 'أطلس'],
                    ['label' => 'هامش الربح', 'value' => '31%'],
                ],
                'sections' => [
                    self::tableSection('مقارنة أداء المتاجر', ['المتجر', 'المبيعات', 'الربح', 'التوقع'], [
                        ['متجر أطلس', '418K ر.س', '129K ر.س', '+18%'],
                        ['متجر رواء', '276K ر.س', '82K ر.س', '+9%'],
                    ]),
                    self::cardsSection('تحليلات ذكية', [
                        ['name' => 'Heatmaps للمبيعات', 'status' => 'نشط', 'meta' => 'حسب المدينة والفترة'],
                        ['name' => 'تحليل العملاء', 'status' => 'نشط', 'meta' => 'شرائح وقيمة عمر العميل'],
                        ['name' => 'تقارير P&L', 'status' => 'نشط', 'meta' => 'ربح وخسارة لكل متجر'],
                    ]),
                ],
            ],
            'ai-commerce' => [
                'activeRoute' => 'admin.ai-commerce',
                'title' => 'AI Commerce',
                'eyebrow' => 'AI Features',
                'summary' => 'اقتراح أسعار ومنتجات، كتابة وصف المنتجات، تحليل أداء المتجر وChat Assistant داخل اللوحة.',
                'stats' => [
                    ['label' => 'اقتراحات سعر', 'value' => '128'],
                    ['label' => 'أوصاف مولدة', 'value' => '842'],
                    ['label' => 'تنبيهات AI', 'value' => '31'],
                    ['label' => 'دقة التوقع', 'value' => '87%'],
                ],
                'sections' => [
                    self::cardsSection('AI Actions', [
                        ['name' => 'اقتراح أسعار ذكية', 'status' => 'نشط', 'meta' => 'حسب الطلب والمنافسة والهامش'],
                        ['name' => 'كتابة وصف منتجات', 'status' => 'نشط', 'meta' => 'وصف عربي محسّن للتحويل'],
                        ['name' => 'Chat Assistant', 'status' => 'مراجعة', 'meta' => 'مساعد داخلي لسؤال بيانات المتجر'],
                    ]),
                    self::tableSection('تحليل الأداء', ['المتجر', 'المشكلة', 'اقتراح AI', 'الأثر'], [
                        ['متجر أطلس', 'نفاد منتج رابح', 'إعادة طلب 400 قطعة', '+7% مبيعات'],
                        ['متجر رواء', 'تحويل منخفض', 'تبسيط الدفع وإضافة Tabby', '+5% تحويل'],
                    ]),
                ],
            ],
            'workflow-engine' => [
                'activeRoute' => 'admin.workflow-engine',
                'title' => 'Workflow Engine',
                'eyebrow' => 'Workflow Builder',
                'summary' => 'Workflows مخصصة بشروط وإجراءات، Automation Builder بالسحب والإفلات وتشغيل Jobs تلقائيا.',
                'stats' => [
                    ['label' => 'Workflows نشطة', 'value' => '21'],
                    ['label' => 'Jobs اليوم', 'value' => '9,842'],
                    ['label' => 'نجاح التنفيذ', 'value' => '99.2%'],
                    ['label' => 'متوسط الزمن', 'value' => '1.1s'],
                ],
                'sections' => [
                    self::builderSection('Workflow Builder', ['Trigger', 'Condition', 'Action', 'Job Queue']),
                    self::tableSection('Workflows', ['الاسم', 'الشرط', 'الإجراء', 'الحالة'], [
                        ['Order Split', 'طلب متعدد البائعين', 'تقسيم تلقائي', 'نشط'],
                        ['Low Stock Reorder', 'مخزون أقل من 10', 'إنشاء تنبيه شراء', 'نشط'],
                    ]),
                ],
            ],
            'advanced-shipping' => [
                'activeRoute' => 'admin.advanced-shipping',
                'title' => 'إدارة الشحن المتقدم',
                'eyebrow' => 'Advanced Shipping',
                'summary' => 'Multi Shipping Providers، حساب الشحن التلقائي، Tracking مباشر، مرتجعات وقواعد حسب الدولة أو الوزن.',
                'stats' => [
                    ['label' => 'مزودي شحن', 'value' => '12'],
                    ['label' => 'شحنات مباشرة', 'value' => '4,820'],
                    ['label' => 'مرتجعات نشطة', 'value' => '86'],
                    ['label' => 'دقة التسعير', 'value' => '98.1%'],
                ],
                'sections' => [
                    self::tableSection('Shipping Rules', ['المنطقة', 'الوزن', 'المزود', 'السعر'], [
                        ['السعودية', '0-5kg', 'Aramex / SPL', 'تلقائي'],
                        ['الخليج', '0-3kg', 'DHL', 'حسب الدولة'],
                    ]),
                    self::cardsSection('الشحن والمرتجعات', [
                        ['name' => 'Tracking مباشر', 'status' => 'نشط', 'meta' => 'تحديثات webhook للشحن'],
                        ['name' => 'إدارة المرتجعات', 'status' => 'نشط', 'meta' => 'RMA وربط بالمخزون'],
                        ['name' => 'حساب شحن تلقائي', 'status' => 'نشط', 'meta' => 'حسب الوزن والمنطقة'],
                    ]),
                ],
            ],
            'admin-experience' => [
                'activeRoute' => 'admin.admin-experience',
                'title' => 'تجربة الأدمن',
                'eyebrow' => 'Admin Experience',
                'summary' => 'Global Search سريع، Quick Actions، Dashboards قابلة للتخصيص، Widgets وسجلات محفوظة.',
                'stats' => [
                    ['label' => 'Saved Reports', 'value' => '37'],
                    ['label' => 'Widgets مخصصة', 'value' => '68'],
                    ['label' => 'Quick Actions', 'value' => '24'],
                    ['label' => 'زمن البحث', 'value' => '42ms'],
                ],
                'sections' => [
                    self::builderSection('Custom Dashboard', ['Widget', 'Drag', 'Resize', 'Save View']),
                    self::tableSection('Saved Reports', ['التقرير', 'المالك', 'التكرار', 'آخر تشغيل'], [
                        ['مبيعات المتاجر', 'فريق الإدارة', 'يومي', 'اليوم'],
                        ['منتجات منخفضة المخزون', 'العمليات', 'كل ساعة', 'قبل 12 دقيقة'],
                    ]),
                ],
            ],
            'enterprise-security' => [
                'activeRoute' => 'admin.enterprise-security',
                'title' => 'Enterprise Security',
                'eyebrow' => 'Security & Compliance',
                'summary' => 'Audit Logs كاملة، Device Management، Session Monitoring، SSO، Encryption وBackup Recovery.',
                'stats' => [
                    ['label' => 'Audit Events', 'value' => '184K'],
                    ['label' => 'Active Sessions', 'value' => '412'],
                    ['label' => 'SSO Tenants', 'value' => '6'],
                    ['label' => 'Encrypted Data', 'value' => '100%'],
                ],
                'sections' => [
                    self::tableSection('Session Monitoring', ['المستخدم', 'الجهاز', 'آخر نشاط', 'المخاطر'], [
                        ['admin@solve.sa', 'Chrome Windows', 'قبل دقيقة', 'منخفضة'],
                        ['ops@solve.sa', 'Safari iOS', 'قبل 8 دقائق', 'متوسطة'],
                    ]),
                    self::cardsSection('Enterprise Controls', [
                        ['name' => 'SSO Support', 'status' => 'نشط', 'meta' => 'SAML/OIDC للفرق الكبيرة'],
                        ['name' => 'Data Encryption', 'status' => 'نشط', 'meta' => 'تشفير بيانات حساسة'],
                        ['name' => 'Backup & Recovery', 'status' => 'نشط', 'meta' => 'استرجاع حسب نقاط زمنية'],
                    ]),
                ],
            ],
            'technical-architecture' => [
                'activeRoute' => 'admin.technical-architecture',
                'title' => 'البنية التقنية',
                'eyebrow' => 'Technical Architecture',
                'summary' => 'Modular Architecture، Feature-based Structure، APIs قابلة للتوسع، Queues، WebSocket وCaching Layer.',
                'stats' => [
                    ['label' => 'Modules', 'value' => '36'],
                    ['label' => 'Queue Workers', 'value' => '8'],
                    ['label' => 'Cache Hit Rate', 'value' => '94%'],
                    ['label' => 'Realtime Channels', 'value' => '18'],
                ],
                'sections' => [
                    self::tableSection('Architecture Blocks', ['الطبقة', 'المسؤولية', 'الحالة', 'ملاحظة'], [
                        ['Feature Modules', 'تقسيم حسب الميزة', 'جاهز', 'قابل للنقل لحزم مستقلة'],
                        ['Scalable APIs', 'واجهات REST / Mobile', 'جاهز', 'محمي بالـ middleware'],
                        ['Background Services', 'Jobs وRetries', 'جاهز', 'Queue-ready'],
                    ]),
                    self::builderSection('Scalability Path', ['Cache', 'Queues', 'WebSocket', 'CDN Ready']),
                ],
            ],
            'ux-polish' => [
                'activeRoute' => 'admin.ux-polish',
                'title' => 'تحسين تجربة المستخدم',
                'eyebrow' => 'UX Polish',
                'summary' => 'واجهات حديثة، Animations خفيفة، Dark/Light Mode، Accessibility وسرعة عالية.',
                'stats' => [
                    ['label' => 'LCP Target', 'value' => '< 2s'],
                    ['label' => 'A11y Checks', 'value' => '92%'],
                    ['label' => 'Responsive Views', 'value' => '100%'],
                    ['label' => 'Theme Modes', 'value' => '2'],
                ],
                'sections' => [
                    self::cardsSection('UI Enhancements', [
                        ['name' => 'Dark / Light Mode', 'status' => 'مراجعة', 'meta' => 'تجهيز design tokens'],
                        ['name' => 'Micro Animations', 'status' => 'نشط', 'meta' => 'حركات خفيفة بدون إبطاء'],
                        ['name' => 'Accessibility Support', 'status' => 'نشط', 'meta' => 'تباين، focus states، keyboard'],
                    ]),
                    self::builderSection('Performance UX', ['Skeleton', 'Lazy Loading', 'Instant Search', 'Responsive']),
                ],
            ],
            'commercial-launch' => [
                'activeRoute' => 'admin.commercial-launch',
                'title' => 'جاهزية الإطلاق التجاري',
                'eyebrow' => 'Commercial Launch',
                'summary' => 'Landing Pages للإدارة، صفحات تسعير، تسجيل متجر جديد، اختيار باقة، Trial، Billing وEmails.',
                'stats' => [
                    ['label' => 'Trial Stores', 'value' => '92'],
                    ['label' => 'Billing Ready', 'value' => '91%'],
                    ['label' => 'Pricing Pages', 'value' => '4'],
                    ['label' => 'System Emails', 'value' => '18'],
                ],
                'sections' => [
                    self::tableSection('Launch Assets', ['الأصل', 'الحالة', 'المالك', 'ملاحظة'], [
                        ['Landing Page', 'جاهز', 'Marketing', 'يركز على التجار'],
                        ['صفحة التسعير', 'جاهز', 'Growth', 'مقارنة باقات'],
                        ['تسجيل متجر جديد', 'مراجعة', 'Product', 'تجربة Trial'],
                        ['Billing Emails', 'جاهز', 'Finance', 'فواتير وتنبيهات'],
                    ]),
                    self::builderSection('Merchant Signup Flow', ['Landing', 'Pricing', 'Trial', 'Billing']),
                ],
            ],
        ];
    }

    private static function commerceEcosystemModules(): array
    {
        return [
            'commerce-infrastructure' => [
                'activeRoute' => 'admin.commerce-infrastructure',
                'title' => 'Commerce Infrastructure',
                'eyebrow' => 'Global Commerce Core',
                'summary' => 'Multi Region وMulti Currency وMulti Language مع ضرائب وشحن وإعدادات محلية لكل متجر.',
                'stats' => [
                    ['label' => 'Regions', 'value' => '6'],
                    ['label' => 'Currencies', 'value' => '12'],
                    ['label' => 'Languages', 'value' => '4'],
                    ['label' => 'Tax Rules', 'value' => '38'],
                ],
                'sections' => [
                    self::tableSection('الإعدادات المحلية', ['الدولة', 'العملة', 'اللغة', 'الضريبة'], [
                        ['السعودية', 'SAR', 'العربية', 'VAT 15%'],
                        ['الإمارات', 'AED', 'العربية / English', 'VAT 5%'],
                        ['الكويت', 'KWD', 'العربية', 'لا توجد VAT'],
                    ]),
                    self::tableSection('قوانين الشحن حسب المنطقة', ['المنطقة', 'القاعدة', 'المزود', 'الحالة'], [
                        ['الرياض', 'داخل المدينة', 'SPL / Aramex', 'نشط'],
                        ['الخليج', 'حسب الوزن والدولة', 'DHL', 'نشط'],
                    ]),
                    self::cardsSection('إعدادات كل متجر', [
                        ['name' => 'Locale Settings', 'status' => 'نشط', 'meta' => 'لغة وعملة وافتراضات ضريبة لكل store_id'],
                        ['name' => 'Regional Shipping', 'status' => 'نشط', 'meta' => 'قواعد شحن حسب المنطقة والوزن'],
                    ]),
                ],
            ],
            'headless-commerce' => [
                'activeRoute' => 'admin.headless-commerce',
                'title' => 'Headless Commerce',
                'eyebrow' => 'Storefront APIs',
                'summary' => 'REST وGraphQL وStorefront API وSDK للتكاملات الخارجية مع Webhooks متقدمة.',
                'stats' => [
                    ['label' => 'REST Endpoints', 'value' => '86'],
                    ['label' => 'GraphQL Types', 'value' => '42'],
                    ['label' => 'SDKs', 'value' => '3'],
                    ['label' => 'Webhooks Events', 'value' => '27'],
                ],
                'sections' => [
                    self::tableSection('API Surface', ['القناة', 'الاستخدام', 'الحماية', 'الحالة'], [
                        ['Storefront REST', 'المواقع والتطبيقات', 'Public scoped token', 'جاهز'],
                        ['GraphQL', 'واجهات مخصصة', 'Signed token', 'جاهز'],
                        ['Partner SDK', 'تكاملات خارجية', 'API key scopes', 'مراجعة'],
                    ]),
                    self::builderSection('Headless Flow', ['Storefront API', 'Cart', 'Checkout', 'Webhooks']),
                ],
            ],
            'website-builder' => [
                'activeRoute' => 'admin.website-builder',
                'title' => 'Website Builder',
                'eyebrow' => 'Theme & Page Builder',
                'summary' => 'Page Builder بالسحب والإفلات، Sections جاهزة، Themes قابلة للتخصيص وLive Preview.',
                'stats' => [
                    ['label' => 'Themes', 'value' => '18'],
                    ['label' => 'Sections', 'value' => '64'],
                    ['label' => 'Published Pages', 'value' => '1,248'],
                    ['label' => 'Theme Settings', 'value' => '142'],
                ],
                'sections' => [
                    self::builderSection('Page Builder', ['Hero', 'Products Grid', 'Banner', 'Checkout CTA']),
                    self::cardsSection('Theme Marketplace', [
                        ['name' => 'Solve Minimal', 'status' => 'نشط', 'meta' => 'قالب أبيض سريع للتجار'],
                        ['name' => 'Fashion Plus', 'status' => 'نشط', 'meta' => 'مناسب للأزياء والمنتجات المرئية'],
                        ['name' => 'Grocery Fast', 'status' => 'مراجعة', 'meta' => 'قالب طلب سريع'],
                    ]),
                    self::tableSection('Live Preview', ['المتجر', 'القالب', 'آخر تعديل', 'الحالة'], [
                        ['متجر أطلس', 'Fashion Plus', 'قبل 12 دقيقة', 'منشور'],
                        ['متجر رواء', 'Solve Minimal', 'قبل ساعة', 'مسودة'],
                    ]),
                ],
            ],
            'app-ecosystem' => [
                'activeRoute' => 'admin.app-ecosystem',
                'title' => 'App Marketplace',
                'eyebrow' => 'Developer Ecosystem',
                'summary' => 'تطبيقات خارجية، تثبيت وإزالة، صلاحيات للتطبيقات، Revenue Share وDeveloper Portal.',
                'stats' => [
                    ['label' => 'Apps', 'value' => '124'],
                    ['label' => 'Paid Apps', 'value' => '38'],
                    ['label' => 'Developers', 'value' => '72'],
                    ['label' => 'Revenue Share', 'value' => '15%'],
                ],
                'sections' => [
                    self::tableSection('التطبيقات الخارجية', ['التطبيق', 'الفئة', 'الصلاحيات', 'الحالة'], [
                        ['Marketing Booster', 'تسويق', 'orders:read customers:write', 'منشور'],
                        ['Shipping Optimizer', 'شحن', 'shipping:write', 'مراجعة'],
                    ]),
                    self::cardsSection('Developer Portal', [
                        ['name' => 'App Scopes', 'status' => 'نشط', 'meta' => 'صلاحيات دقيقة قبل التثبيت'],
                        ['name' => 'Revenue Share', 'status' => 'نشط', 'meta' => 'نسبة للتطبيقات المدفوعة'],
                        ['name' => 'Install / Uninstall', 'status' => 'نشط', 'meta' => 'إدارة دورة حياة التطبيق'],
                    ]),
                ],
            ],
            'b2b-commerce' => [
                'activeRoute' => 'admin.b2b-commerce',
                'title' => 'B2B Commerce',
                'eyebrow' => 'Wholesale & Companies',
                'summary' => 'أسعار جملة، حسابات مؤسسات، موافقات الطلبات، Credit Limits، Quotations وصلاحيات للمؤسسات.',
                'stats' => [
                    ['label' => 'حسابات مؤسسات', 'value' => '86'],
                    ['label' => 'عروض أسعار', 'value' => '214'],
                    ['label' => 'Credit Limits', 'value' => '4.2M ر.س'],
                    ['label' => 'طلبات موافقة', 'value' => '31'],
                ],
                'sections' => [
                    self::tableSection('حسابات B2B', ['الشركة', 'حد الائتمان', 'الخصم', 'الحالة'], [
                        ['شركة المدار', '250K ر.س', '12%', 'نشط'],
                        ['مجموعة النخبة', '600K ر.س', '18%', 'بانتظار موافقة'],
                    ]),
                    self::builderSection('B2B Order Flow', ['Quotation', 'Approval', 'Credit Check', 'Invoice']),
                ],
            ],
            'subscription-commerce' => [
                'activeRoute' => 'admin.subscription-commerce',
                'title' => 'Subscription Commerce',
                'eyebrow' => 'Recurring Products',
                'summary' => 'منتجات باشتراك دوري، Auto Renewal، Billing Cycles، Pause / Resume وإدارة الاشتراكات.',
                'stats' => [
                    ['label' => 'اشتراكات منتجات', 'value' => '1,842'],
                    ['label' => 'Auto Renewals', 'value' => '92%'],
                    ['label' => 'Paused', 'value' => '74'],
                    ['label' => 'MRR', 'value' => '384K ر.س'],
                ],
                'sections' => [
                    self::tableSection('الاشتراكات الدورية', ['المنتج', 'الدورة', 'المشتركين', 'الحالة'], [
                        ['صندوق القهوة الشهري', 'شهري', '842', 'نشط'],
                        ['باقة العناية', 'كل 45 يوم', '312', 'نشط'],
                    ]),
                    self::builderSection('Subscription Lifecycle', ['Subscribe', 'Auto Renewal', 'Pause', 'Resume']),
                ],
            ],
            'omnichannel' => [
                'activeRoute' => 'admin.omnichannel',
                'title' => 'Omnichannel Commerce',
                'eyebrow' => 'Channels Sync',
                'summary' => 'ربط Instagram وTikTok وFacebook وGoogle Shopping وAmazon/Noon مع مزامنة الطلبات والمخزون.',
                'stats' => [
                    ['label' => 'قنوات متصلة', 'value' => '9'],
                    ['label' => 'طلبات خارجية', 'value' => '4,218'],
                    ['label' => 'مزامنة مخزون', 'value' => '99.4%'],
                    ['label' => 'Catalog Items', 'value' => '82K'],
                ],
                'sections' => [
                    self::cardsSection('قنوات البيع', [
                        ['name' => 'Instagram Shop', 'status' => 'متصل', 'meta' => 'Catalog sync وطلبات'],
                        ['name' => 'TikTok Shop', 'status' => 'يحتاج إعداد', 'meta' => 'يتطلب ربط الحساب'],
                        ['name' => 'Google Shopping', 'status' => 'متصل', 'meta' => 'Feed محدث يوميا'],
                        ['name' => 'Amazon / Noon', 'status' => 'مراجعة', 'meta' => 'Marketplace sync'],
                    ]),
                    self::tableSection('مزامنة القنوات', ['القناة', 'آخر مزامنة', 'الطلبات', 'الحالة'], [
                        ['Instagram', 'قبل 6 دقائق', '842', 'Healthy'],
                        ['Google Shopping', 'قبل 18 دقيقة', '0', 'Catalog Only'],
                    ]),
                ],
            ],
            'ai-suite' => [
                'activeRoute' => 'admin.ai-suite',
                'title' => 'AI Commerce Suite',
                'eyebrow' => 'AI Growth Suite',
                'summary' => 'AI Analytics، توقع المبيعات، اكتشاف المنتجات الرابحة، Smart Campaigns، Chat Support، Fraud Detection.',
                'stats' => [
                    ['label' => 'AI Insights', 'value' => '428'],
                    ['label' => 'Winning Products', 'value' => '26'],
                    ['label' => 'Fraud Alerts', 'value' => '11'],
                    ['label' => 'Smart Campaigns', 'value' => '19'],
                ],
                'sections' => [
                    self::cardsSection('AI Capabilities', [
                        ['name' => 'AI Analytics', 'status' => 'نشط', 'meta' => 'تحليل أداء وتوقعات'],
                        ['name' => 'AI Chat Support', 'status' => 'نشط', 'meta' => 'ردود دعم موجهة'],
                        ['name' => 'AI Fraud Detection', 'status' => 'مراجعة', 'meta' => 'كشف طلبات مشبوهة'],
                    ]),
                    self::tableSection('Customer Insights', ['الشريحة', 'الحجم', 'الفرصة', 'الإجراء'], [
                        ['عملاء خامدون', '1,284', 'عودة شراء', 'Smart Campaign'],
                        ['VIP', '312', 'Upsell', 'عرض خاص'],
                    ]),
                ],
            ],
            'enterprise-operations' => [
                'activeRoute' => 'admin.enterprise-operations',
                'title' => 'Enterprise Operations',
                'eyebrow' => 'Operations Command',
                'summary' => 'SLA Monitoring، Incident Management، Health Dashboard، Real-time Alerts، Audit Center وCompliance.',
                'stats' => [
                    ['label' => 'SLA', 'value' => '99.95%'],
                    ['label' => 'Incidents', 'value' => '2'],
                    ['label' => 'Realtime Alerts', 'value' => '18'],
                    ['label' => 'Compliance Checks', 'value' => '96%'],
                ],
                'sections' => [
                    self::tableSection('Incidents', ['الحادثة', 'الأثر', 'SLA', 'الحالة'], [
                        ['Webhook delays', 'منخفض', 'داخل SLA', 'قيد الحل'],
                        ['Payment retry spike', 'متوسط', 'مراقبة', 'مفتوح'],
                    ]),
                    self::cardsSection('Operations Centers', [
                        ['name' => 'Health Monitoring Dashboard', 'status' => 'نشط', 'meta' => 'مراقبة الخدمات والمتاجر'],
                        ['name' => 'Advanced Audit Center', 'status' => 'نشط', 'meta' => 'سجل عمليات حساس'],
                        ['name' => 'Compliance Tools', 'status' => 'مراجعة', 'meta' => 'سياسات وتدقيق'],
                    ]),
                ],
            ],
            'devops-scalability' => [
                'activeRoute' => 'admin.devops-scalability',
                'title' => 'DevOps & Scalability',
                'eyebrow' => 'Scale Readiness',
                'summary' => 'Docker وCI/CD وAuto Scaling وMonitoring Stack وLogging وReplication وRedis/Queue وCDN Optimization.',
                'stats' => [
                    ['label' => 'CI Checks', 'value' => '31'],
                    ['label' => 'Queue Workers', 'value' => '8'],
                    ['label' => 'Redis Hit Rate', 'value' => '94%'],
                    ['label' => 'CDN Assets', 'value' => 'Ready'],
                ],
                'sections' => [
                    self::tableSection('Scale Checklist', ['البند', 'الغرض', 'الحالة', 'ملاحظة'], [
                        ['Docker Support', 'بيئات متطابقة', 'جاهز', 'Dockerfile/compose لاحقا'],
                        ['CI/CD Pipelines', 'نشر آمن', 'مراجعة', 'ربط GitHub Actions'],
                        ['Database Replication', 'قراءات أسرع', 'مخطط', 'مرحلة الإنتاج'],
                        ['Redis + Queue', 'أداء وخلفية', 'جاهز', 'Queue-ready'],
                    ]),
                    self::builderSection('Deployment Flow', ['Build', 'Test', 'Deploy', 'Monitor']),
                ],
            ],
            'advanced-ux' => [
                'activeRoute' => 'admin.advanced-ux',
                'title' => 'Advanced User Experience',
                'eyebrow' => 'Realtime Workspace',
                'summary' => 'Realtime Updates عبر WebSockets، Offline Support، PWA، Instant Search، Dynamic Tables وWorkspace Personalization.',
                'stats' => [
                    ['label' => 'Realtime Channels', 'value' => '18'],
                    ['label' => 'Saved Dashboards', 'value' => '42'],
                    ['label' => 'PWA Ready', 'value' => '82%'],
                    ['label' => 'Search Latency', 'value' => '42ms'],
                ],
                'sections' => [
                    self::builderSection('Workspace Personalization', ['Dashboard', 'Widgets', 'Filters', 'Saved Views']),
                    self::cardsSection('UX Systems', [
                        ['name' => 'WebSockets Realtime', 'status' => 'مراجعة', 'meta' => 'تحديث الطلبات والتنبيهات'],
                        ['name' => 'Offline Support', 'status' => 'مخطط', 'meta' => 'PWA cache للعمليات الأساسية'],
                        ['name' => 'Dynamic Tables', 'status' => 'نشط', 'meta' => 'فلاتر وأعمدة محفوظة'],
                    ]),
                ],
            ],
            'business-growth' => [
                'activeRoute' => 'admin.business-growth',
                'title' => 'Business Growth System',
                'eyebrow' => 'Growth Engine',
                'summary' => 'Referral وAffiliate وPartner Program وMarketing Center وEmail/SMS Campaigns وFunnel Analytics.',
                'stats' => [
                    ['label' => 'Referrals', 'value' => '1,240'],
                    ['label' => 'Affiliates', 'value' => '86'],
                    ['label' => 'Partner Leads', 'value' => '312'],
                    ['label' => 'Funnel CVR', 'value' => '7.8%'],
                ],
                'sections' => [
                    self::tableSection('Growth Programs', ['النظام', 'المشاركين', 'الإيراد', 'الحالة'], [
                        ['Referral System', '842', '128K ر.س', 'نشط'],
                        ['Affiliate System', '86', '64K ر.س', 'نشط'],
                        ['Partner Program', '42', 'مراجعة', 'قيد التطوير'],
                    ]),
                    self::builderSection('Campaign Builder', ['Email', 'SMS', 'Audience', 'Funnel Analytics']),
                ],
            ],
            'white-label' => [
                'activeRoute' => 'admin.white-label',
                'title' => 'White Label System',
                'eyebrow' => 'Brand Control',
                'summary' => 'تخصيص الهوية والدومينات والألوان والشعارات ورسائل النظام والفواتير والإيميلات لكل متجر.',
                'stats' => [
                    ['label' => 'Custom Domains', 'value' => '184'],
                    ['label' => 'Brand Profiles', 'value' => '248'],
                    ['label' => 'Email Templates', 'value' => '36'],
                    ['label' => 'Invoice Themes', 'value' => '14'],
                ],
                'sections' => [
                    self::tableSection('Brand Profiles', ['المتجر', 'الدومين', 'الألوان', 'الحالة'], [
                        ['متجر أطلس', 'atlas.sa', 'Indigo / White', 'نشط'],
                        ['متجر رواء', 'rowaa.sa', 'Teal / White', 'نشط'],
                    ]),
                    self::cardsSection('White Label Assets', [
                        ['name' => 'System Emails', 'status' => 'نشط', 'meta' => 'قوالب مخصصة لكل متجر'],
                        ['name' => 'Invoices', 'status' => 'نشط', 'meta' => 'شعار وألوان وفوتر'],
                        ['name' => 'Custom Domains', 'status' => 'نشط', 'meta' => 'DNS وSSL جاهز'],
                    ]),
                ],
            ],
            'global-admin' => [
                'activeRoute' => 'admin.global-admin',
                'title' => 'Global Admin Center',
                'eyebrow' => 'Platform Command Center',
                'summary' => 'مراقبة كل المتاجر والأداء العام والأرباح والاشتراكات والباقات والتطبيقات والدعم وAI Services.',
                'stats' => [
                    ['label' => 'Total Stores', 'value' => '1,284'],
                    ['label' => 'Platform GMV', 'value' => '18.4M ر.س'],
                    ['label' => 'Active Apps', 'value' => '8,420'],
                    ['label' => 'AI Requests', 'value' => '2.1M'],
                ],
                'sections' => [
                    self::tableSection('Platform Overview', ['المجال', 'المؤشر', 'الحالة', 'الإجراء'], [
                        ['المتاجر', '98.4% نشطة', 'Healthy', 'متابعة'],
                        ['الدعم', '38 تذكرة مفتوحة', 'مراقبة', 'توزيع'],
                        ['AI Services', '2.1M طلب', 'Healthy', 'ترقية السعة'],
                    ]),
                    self::cardsSection('مراكز التحكم', [
                        ['name' => 'إدارة الباقات', 'status' => 'نشط', 'meta' => 'تسعير وحدود وميزات'],
                        ['name' => 'إدارة التطبيقات', 'status' => 'نشط', 'meta' => 'مراجعة وتثبيت التطبيقات'],
                        ['name' => 'إدارة الدعم الفني', 'status' => 'نشط', 'meta' => 'SLA وتوزيع التذاكر'],
                    ]),
                ],
            ],
            'final-polish' => [
                'activeRoute' => 'admin.final-polish',
                'title' => 'Final Polish',
                'eyebrow' => 'Launch Quality',
                'summary' => 'تحسين UI/UX والأداء والأمان وResponsive Design وتوحيد Components وDesign System وDocumentation.',
                'stats' => [
                    ['label' => 'Tests', 'value' => 'Ready'],
                    ['label' => 'Components', 'value' => 'Unified'],
                    ['label' => 'Security Review', 'value' => 'In Progress'],
                    ['label' => 'Docs', 'value' => 'Updated'],
                ],
                'sections' => [
                    self::tableSection('Final Checklist', ['البند', 'الحالة', 'الأولوية', 'ملاحظة'], [
                        ['UI/UX Review', 'مكتمل', 'عالية', 'نمط موحد للصفحات'],
                        ['Performance Review', 'مراجعة', 'عالية', 'إضافة caching عند الإنتاج'],
                        ['Security Review', 'مراجعة', 'عالية', 'تدقيق الصلاحيات والـ API'],
                        ['Documentation', 'مكتمل', 'متوسطة', 'تم تحديث README/TODO'],
                    ]),
                    self::builderSection('Release Readiness', ['Design System', 'Tests', 'Security', 'Docs']),
                ],
            ],
        ];
    }

    public static function search(string $query): array
    {
        $query = trim(Str::lower($query));
        $match = fn (array $row) => $query === '' || Str::contains(Str::lower(json_encode($row, JSON_UNESCAPED_UNICODE)), $query);

        return [
            'stores' => collect(AdminSectionStore::get('stores', self::defaultStores()))->filter($match)->take(5)->values()->all(),
            'orders' => collect(self::orders())->filter($match)->take(5)->values()->all(),
            'customers' => collect(self::customers())->filter($match)->take(5)->values()->all(),
            'products' => collect(self::products())->filter($match)->take(5)->values()->all(),
            'invoices' => collect(self::invoices()['records'])->filter($match)->take(5)->values()->all(),
            'support' => collect(AdminSectionStore::get('support', self::defaultSupport()))->filter($match)->take(5)->values()->all(),
        ];
    }

    private static function cardsSection(string $title, array $items): array
    {
        return ['type' => 'cards', 'title' => $title, 'items' => $items];
    }

    private static function tableSection(string $title, array $columns, array $rows): array
    {
        return ['type' => 'table', 'title' => $title, 'columns' => $columns, 'rows' => $rows];
    }

    private static function builderSection(string $title, array $steps): array
    {
        return ['type' => 'builder', 'title' => $title, 'steps' => $steps];
    }

    public static function findOrder(string $id): ?array
    {
        return collect(self::orders())->first(fn (array $order) => ($order['id'] ?? null) === $id || ($order['order_number'] ?? null) === $id);
    }

    public static function findCustomer(string $id): ?array
    {
        return collect(self::customers())->first(fn (array $customer) => ($customer['id'] ?? null) === $id || Str::slug($customer['name'] ?? '') === $id);
    }

    private static function platformOrders(): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'orders')
            ->latest()
            ->get()
            ->map(function (PlatformRecord $record) {
                $payload = $record->payload ?? [];
                $status = (string) ($payload['status'] ?? $record->status ?? 'قيد المعالجة');

                $storeName = (string) ($payload['store'] ?? '');
                $storeId = self::normalizeStoreId($record->store_id, $storeName);

                return [
                    'id' => $record->record_id,
                    'order_number' => $payload['order_number'] ?? $record->record_id,
                    'admin_reference' => $payload['admin_reference'] ?? self::adminOrderReference($record->record_id, (string) ($payload['order_number'] ?? $record->record_id)),
                    'store_id' => $storeId,
                    'store' => $payload['store'] ?? self::storeName($storeId),
                    'customer_id' => $payload['customer_id'] ?? null,
                    'customer' => $payload['customer'] ?? '-',
                    'total' => $payload['total'] ?? $payload['amount'] ?? '0 ر.س',
                    'status' => $status,
                    'payment_status' => $payload['payment_status'] ?? 'غير محدد',
                    'shipping_status' => $payload['shipping_status'] ?? 'غير محدد',
                    'invoice_id' => $payload['invoice_id'] ?? ('INV-' . ($payload['order_number'] ?? $record->record_id)),
                    'shipment_id' => $payload['shipment_id'] ?? ($payload['tracking_number'] ?? '-'),
                    'payment_id' => $payload['payment_id'] ?? ('PAY-' . $record->record_id),
                    'created_at' => $payload['created_at'] ?? $payload['date'] ?? $record->created_at?->toDateString(),
                    'source' => $payload['source_channel'] ?? $payload['source'] ?? 'لوحة التاجر',
                    'items' => $payload['items'] ?? [],
                    'custom_statuses' => ['قيد المراجعة', 'بانتظار التجهيز', 'جاهز للشحن', 'تم التسليم', 'مرتجع'],
                    'timeline' => $payload['timeline'] ?? self::orderTimeline($status),
                    'status_history' => $payload['change_log'] ?? self::statusHistory($status),
                    'internal_notes' => collect($payload['notes'] ?? [])->map(fn ($note) => is_array($note) ? ($note['body'] ?? '') : (string) $note)->filter()->values()->all(),
                    'linked' => [
                        'customer' => $payload['customer'] ?? '-',
                        'shipment' => $payload['shipment_id'] ?? '-',
                        'payment' => $payload['payment_id'] ?? '-',
                    ],
                ];
            })
            ->values()
            ->all();
    }

    private static function platformProducts(): array
    {
        if (! Schema::hasTable('platform_records')) {
            return [];
        }

        return PlatformRecord::query()
            ->where('section', 'products')
            ->latest()
            ->get()
            ->map(function (PlatformRecord $record) {
                $payload = $record->payload ?? [];
                $storeName = (string) ($payload['store'] ?? '');
                $storeId = self::normalizeStoreId($record->store_id, $storeName);
                $sku = (string) ($payload['sku'] ?? $record->record_id);

                return [
                    'id' => $record->record_id,
                    'store_id' => $storeId,
                    'store' => $payload['store'] ?? self::storeName($storeId),
                    'name' => $payload['name'] ?? $payload['product'] ?? $record->record_id,
                    'sku' => $sku,
                    'type' => $payload['type'] ?? $payload['product_type'] ?? 'simple',
                    'status' => $payload['status'] ?? $record->status ?? 'active',
                    'price' => $payload['price'] ?? '0 SAR',
                    'stock' => (int) ($payload['stock'] ?? $payload['quantity'] ?? 0),
                    'low_stock_threshold' => (int) ($payload['low_stock_threshold'] ?? 10),
                    'categories' => $payload['categories'] ?? array_filter([$payload['category'] ?? null]),
                    'tags' => $payload['tags'] ?? [],
                    'images' => $payload['images'] ?? [],
                    'variants' => $payload['variants'] ?? [],
                    'branch_inventory' => $payload['branch_inventory'] ?? [],
                    'created_at' => $payload['created_at'] ?? $record->created_at?->toDateString(),
                    'updated_at' => $payload['updated_at'] ?? $record->updated_at?->toDateString(),
                ];
            })
            ->values()
            ->all();
    }

    private static function adminStores(): array
    {
        if (Schema::hasTable('partner_stores') && PartnerStore::query()->exists()) {
            return PartnerStore::query()
                ->orderBy('name')
                ->get()
                ->map(fn (PartnerStore $store) => [
                    'id' => $store->store_id,
                    'partner_id' => $store->partner_id,
                    'name' => $store->name,
                    'owner' => $store->owner_name,
                    'status' => $store->status,
                    'plan' => $store->plan,
                    'domain' => $store->domain,
                ])
                ->values()
                ->all();
        }

        return collect(AdminSectionStore::get('stores', self::defaultStores()))
            ->map(fn (array $store) => [
                'id' => $store['id'] ?? self::storeIdFromName((string) ($store['name'] ?? '')),
                'partner_id' => Str::after((string) ($store['id'] ?? ''), 'store-'),
                'name' => $store['name'] ?? 'متجر',
                'owner' => $store['owner'] ?? $store['owner_name'] ?? '-',
                'status' => $store['status'] ?? 'نشط',
                'plan' => $store['plan'] ?? 'Starter',
                'domain' => $store['domain'] ?? null,
            ])
            ->values()
            ->all();
    }

    private static function normalizeStoreId(?string $storeId, string $storeName = ''): string
    {
        if (trim($storeName) !== '') {
            $store = collect(self::adminStores())->first(fn (array $store) => ($store['name'] ?? null) === $storeName);

            if ($store) {
                return (string) $store['id'];
            }
        }

        return $storeId ?: self::storeIdFromName($storeName);
    }

    private static function adminOrderReference(string $id, string $orderNumber): string
    {
        if (preg_match('/(\d+)/', $id, $matches)) {
            return 'ORD-' . $matches[1];
        }

        return $orderNumber;
    }

    private static function storeName(string $storeId): string
    {
        $store = collect(self::adminStores())->firstWhere('id', $storeId);

        return $store['name'] ?? $storeId;
    }

    private static function storeIdFromName(string $name): string
    {
        $store = collect(self::adminStores())->first(fn (array $store) => ($store['name'] ?? null) === $name);

        return $store['id'] ?? (Str::slug($name) ?: 'store-atlas');
    }

    private static function moneyToNumber(mixed $value): float
    {
        $normalized = preg_replace('/[^\d.]/', '', str_replace(',', '', (string) $value));

        return $normalized === '' ? 0.0 : (float) $normalized;
    }

    private static function isActiveProduct(array $product): bool
    {
        return Str::contains(Str::lower((string) ($product['status'] ?? '')), ['active', 'published', 'نشط', 'منشور']);
    }

    private static function isLowStockProduct(array $product): bool
    {
        $quantity = (int) ($product['stock'] ?? 0);
        $threshold = (int) ($product['low_stock_threshold'] ?? 10);

        return $quantity > 0 && $quantity <= $threshold;
    }

    private static function formatMoney(float|int $amount): string
    {
        return number_format((float) $amount) . ' ر.س';
    }

    private static function orderTimeline(string $status): array
    {
        return [
            ['label' => 'تم إنشاء الطلب', 'time' => '09:15', 'state' => 'done'],
            ['label' => 'تم تأكيد الدفع', 'time' => '09:17', 'state' => 'done'],
            ['label' => 'التجهيز في المستودع', 'time' => '10:40', 'state' => $status === 'ملغي' ? 'blocked' : 'done'],
            ['label' => 'تسليم لشركة الشحن', 'time' => '13:00', 'state' => in_array($status, ['تم التسليم', 'جاهز للشحن'], true) ? 'done' : 'pending'],
        ];
    }

    private static function statusHistory(string $status): array
    {
        return [
            ['from' => 'جديد', 'to' => 'قيد المراجعة', 'user' => 'Super Admin', 'date' => '12 مايو 2026 09:16'],
            ['from' => 'قيد المراجعة', 'to' => $status, 'user' => 'مدير العمليات', 'date' => '12 مايو 2026 10:39'],
        ];
    }

    private static function defaultOrders(): array
    {
        return [
            ['id' => 'order-1001', 'order_number' => 'SO-01001', 'store' => 'متجر أطلس', 'customer' => 'نورة السالم', 'customer_id' => 'customer-noura', 'status' => 'جاهز للشحن', 'total' => '1,248 ر.س'],
            ['id' => 'order-1002', 'order_number' => 'SO-01002', 'store' => 'متجر رواء', 'customer' => 'سلمان العتيبي', 'customer_id' => 'customer-salman', 'status' => 'قيد المعالجة', 'total' => '879 ر.س'],
        ];
    }

    private static function defaultProducts(): array
    {
        return [
            ['id' => 'product-atlas-1', 'name' => 'عباية أطلس الفاخرة', 'sku' => 'ATL-ABY-001', 'stock' => 24, 'price' => '349 ر.س'],
            ['id' => 'product-rowaa-1', 'name' => 'طقم ضيافة رواء', 'sku' => 'RWA-HOS-014', 'stock' => 7, 'price' => '189 ر.س'],
        ];
    }

    private static function defaultCustomers(): array
    {
        return [
            ['id' => 'customer-noura', 'name' => 'نورة السالم', 'email' => 'noura@example.sa', 'phone' => '+966500000001'],
            ['id' => 'customer-salman', 'name' => 'سلمان العتيبي', 'email' => 'salman@example.sa', 'phone' => '+966500000002'],
        ];
    }

    private static function defaultStores(): array
    {
        return [
            ['id' => 'store-atlas', 'name' => 'متجر أطلس', 'owner' => 'سارة الحربي', 'status' => 'نشط'],
            ['id' => 'store-rowaa', 'name' => 'متجر رواء', 'owner' => 'نواف القحطاني', 'status' => 'نشط'],
        ];
    }

    private static function defaultSupport(): array
    {
        return [
            ['id' => 'support-1', 'title' => 'مشكلة في بوابة الدفع', 'store' => 'متجر أطلس', 'status' => 'مفتوحة'],
            ['id' => 'support-2', 'title' => 'تحديث بيانات الشحن', 'store' => 'متجر رواء', 'status' => 'قيد الحل'],
        ];
    }
}
