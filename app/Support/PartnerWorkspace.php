<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PartnerWorkspace
{
    public static function sections(): array
    {
        return [
            self::section('dashboard', 'لوحة التحكم', 'layout-dashboard', [
                self::item('home', 'الرئيسية', 'partner.dashboard', 'view-dashboard'),
                self::item('sales-summary', 'ملخص المبيعات', null, 'view-dashboard'),
                self::item('latest-orders', 'آخر الطلبات', null, 'view-orders'),
                self::item('activities', 'آخر النشاطات', 'partner.activities', 'view-dashboard'),
                self::item('notifications', 'الإشعارات', 'partner.notifications', 'view-dashboard'),
            ]),
            self::section('orders', 'الطلبات', 'shopping-bag', [
                self::item('all', 'جميع الطلبات', 'partner.orders', 'view-orders'),
                self::item('manual', 'الطلبات اليدوية', 'partner.orders.manual', 'view-orders'),
                self::item('abandoned-carts', 'السلات المتروكة', 'partner.orders.abandoned-carts', 'view-orders'),
                self::item('returns', 'المرتجعات', 'partner.orders.returns', 'view-orders'),
                self::item('shipments', 'الشحنات', 'partner.orders.shipments', 'view-orders'),
            ]),
            self::section('products', 'المنتجات', 'package', [
                self::item('all', 'جميع المنتجات', 'partner.products', 'view-products'),
                self::item('categories', 'التصنيفات', 'partner.products.categories', 'view-products'),
                self::item('stock', 'المخزون', 'partner.products.inventory', 'view-products'),
                self::item('inventory-management', 'إدارة المخزون', 'partner.products.inventory', 'view-products'),
                self::item('filters', 'معايير التصفية', 'partner.products.filters', 'view-products'),
                self::item('custom-fields', 'الحقول المخصصة', 'partner.products.custom-fields', 'view-products'),
                self::item('options-library', 'مكتبة الخيارات', 'partner.products.options', 'view-products'),
            ]),
            self::section('customers', 'العملاء', 'users', [
                self::item('all', 'جميع العملاء', 'partner.customers', 'view-customers'),
                self::item('groups', 'مجموعات العملاء', 'partner.customers.groups', 'view-customers', 'Growth'),
                self::item('reviews', 'التقييمات', 'partner.customers.reviews', 'view-customers'),
                self::item('questions', 'الأسئلة', 'partner.customers.questions', 'view-customers'),
                self::item('stock-notifications', 'إشعارات المخزون', 'partner.customers.back-in-stock', 'view-customers', 'Growth'),
            ]),
            self::section('marketing', 'التسويق', 'megaphone', [
                self::item('overview', 'ملخص التسويق', 'partner.marketing', 'view-marketing'),
                self::item('affiliate', 'التسويق بالعمولة', 'partner.marketing.affiliate', 'view-marketing', 'Enterprise'),
                self::item('bundles', 'الحزم التسويقية', 'partner.marketing.bundles', 'view-marketing', 'Growth'),
                self::item('campaigns', 'الحملات التسويقية', 'partner.marketing.campaigns', 'view-marketing', 'Growth'),
                self::item('discounts', 'الخصومات', 'partner.marketing.coupons', 'view-marketing'),
                self::item('loyalty', 'برامج الولاء', 'partner.marketing.loyalty', 'view-marketing', 'Growth'),
                self::item('abandoned-carts', 'السلات المتروكة', 'partner.marketing.abandoned-carts', 'view-marketing'),
                self::item('solve-ads', 'إعلانات زد', 'partner.marketing.ads', 'view-marketing', 'Enterprise'),
                self::item('online-store', 'المتجر الإلكتروني', 'partner.storefront', 'manage-storefront'),
            ]),
            self::section('storefront', 'المتجر الإلكتروني', 'store', [
                self::item('overview', 'الرئيسية', 'partner.storefront', 'manage-storefront'),
                self::item('themes', 'القوالب', 'partner.storefront.themes', 'manage-storefront'),
                self::item('customize', 'تعديل الواجهة', 'partner.storefront.customize', 'manage-storefront'),
                self::item('pages', 'الصفحات', 'partner.storefront.pages', 'manage-storefront'),
                self::item('banners', 'البنرات والعروض', 'partner.storefront.banners', 'manage-storefront'),
                self::item('navigation', 'القوائم والتنقل', 'partner.storefront.navigation', 'manage-storefront'),
                self::item('domain', 'الدومين', 'partner.storefront.domain', 'manage-storefront'),
                self::item('seo', 'SEO', 'partner.storefront.seo', 'manage-storefront'),
                self::item('settings', 'إعدادات المتجر', 'partner.storefront.settings', 'manage-storefront'),
            ]),
            self::section('analytics', 'التحليلات', 'bar-chart', [
                self::item('live', 'التحليلات المباشرة', null, 'view-analytics', 'Growth'),
                self::item('sales', 'تقارير المبيعات', null, 'view-analytics'),
                self::item('inventory', 'تقارير المخزون', null, 'view-analytics', 'Growth'),
                self::item('customers', 'تقارير العملاء', null, 'view-analytics', 'Growth'),
                self::item('finance', 'المالية', null, 'view-payments'),
                self::item('marketing', 'التسويق', null, 'view-marketing', 'Growth'),
                self::item('operations', 'العمليات', null, 'view-analytics', 'Enterprise'),
                self::item('products', 'المنتجات', null, 'view-analytics'),
                self::item('payments', 'المدفوعات', null, 'view-payments'),
            ]),
            self::section('finance', 'المالية', 'wallet', [
                self::item('invoices', 'الفواتير', null, 'view-payments'),
                self::item('wallet', 'المحفظة', null, 'view-payments'),
                self::item('payments', 'المدفوعات', 'partner.payments', 'view-payments'),
                self::item('settlements', 'التسويات', null, 'view-payments'),
            ]),
            self::section('subscription', 'الباقة والاشتراك', 'wallet', [
                self::item('overview', 'الباقة الحالية', 'partner.subscription', 'view-subscription'),
                self::item('plans', 'الباقات', 'partner.subscription.plans', 'view-subscription'),
                self::item('billing', 'الفوترة', 'partner.subscription.billing', 'view-subscription'),
                self::item('invoices', 'فواتير الاشتراك', 'partner.subscription.invoices', 'view-subscription'),
                self::item('payment-methods', 'طرق الدفع', 'partner.subscription.payment-methods', 'view-subscription'),
            ]),
            self::section('services', 'الخدمات', 'plug', [
                self::item('overview', 'ملخص الخدمات', 'partner.services', 'manage-services'),
                self::item('logistics', 'اللوجستيات', 'partner.services.logistics', 'manage-services'),
                self::item('payment-gateways', 'بوابات الدفع', 'partner.services.payment-gateways', 'manage-services'),
                self::item('whatsapp', 'واتساب', 'partner.services.whatsapp', 'manage-services', 'Growth'),
                self::item('financing', 'التمويل', 'partner.services.financing', 'manage-services', 'Enterprise'),
                self::item('growth', 'النمو', 'partner.services.growth', 'manage-services', 'Growth'),
            ]),
            self::section('channels', 'القنوات', 'store', [
                self::item('online-store', 'المتجر الإلكتروني', 'partner.channels.storefront', 'manage-channels'),
                self::item('marketplaces', 'منصات البيع', 'partner.channels.marketplaces', 'manage-channels', 'Growth'),
                self::item('mobile-app', 'تطبيق الجوال', 'partner.channels.mobile-app', 'manage-channels', 'Enterprise'),
                self::item('pos', 'نقاط البيع POS', 'partner.channels.pos', 'manage-channels', 'Enterprise'),
            ]),
            self::section('apps', 'التطبيقات', 'grid', [
                self::item('installed', 'التطبيقات المثبتة', 'partner.apps.installed', 'manage-apps'),
                self::item('marketplace', 'متجر التطبيقات', 'partner.apps.marketplace', 'manage-apps'),
                self::item('solve-ai', 'ذكاء Solve', 'partner.apps.solve-ai', 'manage-apps', 'Enterprise'),
                self::item('ai', 'الذكاء الاصطناعي', 'partner.apps.ai', 'manage-apps', 'Enterprise'),
                self::item('automation', 'الأتمتة', 'partner.apps.automations', 'manage-apps', 'Growth'),
            ]),
            self::section('settings', 'الإعدادات', 'settings', [
                self::item('account', 'إعدادات الحساب', 'partner.settings.section', 'view-settings'),
                self::item('store', 'إعدادات المتجر', 'partner.settings.section', 'view-settings'),
                self::item('identity', 'الهوية', 'partner.settings.section', 'view-settings'),
                self::item('domain', 'الدومين', 'partner.settings.section', 'view-settings'),
                self::item('shipping', 'خيارات الشحن', 'partner.settings.section', 'view-settings'),
                self::item('payments', 'خيارات الدفع', 'partner.settings.section', 'view-settings'),
                self::item('checkout', 'صفحة الدفع', 'partner.settings.section', 'view-settings'),
                self::item('taxes', 'الضرائب', 'partner.settings.section', 'view-settings'),
                self::item('bank-accounts', 'الحسابات البنكية', 'partner.settings.section', 'view-settings'),
                self::item('api', 'التطبيقات وواجهة API', 'partner.settings.section', 'view-settings'),
                self::item('order-settings', 'إعدادات الطلبات', 'partner.settings.section', 'view-settings'),
                self::item('zatca', 'الربط مع ZATCA', 'partner.settings.section', 'view-settings'),
                self::item('maintenance', 'حالة المتجر', 'partner.settings.section', 'view-settings'),
                self::item('contacts', 'بيانات التواصل', 'partner.settings.section', 'view-settings'),
                self::item('messages', 'رسائل الطلبات', 'partner.settings.section', 'view-settings'),
                self::item('review-messages', 'رسائل التقييمات', 'partner.settings.section', 'view-settings'),
                self::item('notifications', 'الإشعارات', 'partner.settings.section', 'view-settings'),
                self::item('social', 'وسائل التواصل', 'partner.settings.section', 'view-settings'),
                self::item('languages', 'اللغات والترجمة', 'partner.settings.section', 'view-settings'),
                self::item('storefront', 'واجهة المتجر', 'partner.storefront.customize', 'manage-storefront'),
                self::item('categories-display', 'التصنيفات المتعددة', 'partner.settings.section', 'view-settings'),
                self::item('working-hours', 'أوقات العمل', 'partner.settings.section', 'view-settings'),
                self::item('staff', 'الموظفين والصلاحيات', 'partner.settings.section', 'manage-settings'),
                self::item('permissions', 'مجموعات الصلاحيات', 'partner.settings.section', 'view-settings'),
                self::item('branches', 'الفروع والمخازن', 'partner.settings.section', 'view-settings'),
                self::item('legal', 'سياسات المتجر', 'partner.settings.section', 'view-settings'),
                self::item('pos', 'نقاط البيع', 'partner.settings.section', 'view-settings'),
            ]),
        ];
    }

    public static function visibleSections(array $user, array $partner): array
    {
        return collect(self::sections())
            ->map(function (array $section) use ($user, $partner) {
                $section['items'] = collect($section['items'])
                    ->filter(fn (array $item) => self::isAllowed($item, $user, $partner) || self::shouldShowLocked($item, $user, $partner))
                    ->map(function (array $item) use ($user, $partner) {
                        $item['locked'] = ! self::isAllowed($item, $user, $partner);
                        $item['lock_reason'] = $item['locked'] ? 'Upgrade required for this feature.' : null;

                        return $item;
                    })
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section) => count($section['items']) > 0)
            ->values()
            ->all();
    }

    public static function findPage(string $sectionKey, string $pageKey): ?array
    {
        $section = collect(self::sections())->firstWhere('key', $sectionKey);
        $item = collect($section['items'] ?? [])->firstWhere('key', $pageKey);

        if (! $section || ! $item) {
            return null;
        }

        return ['section' => $section, 'item' => $item];
    }

    public static function isAllowed(array $item, array $user, array $partner): bool
    {
        if (! PartnerTenantStore::can($user, $item['ability'])) {
            return false;
        }

        return self::planRank($partner['plan'] ?? 'Starter') >= self::planRank($item['plan'] ?? 'Starter');
    }

    private static function shouldShowLocked(array $item, array $user, array $partner): bool
    {
        return ($partner['plan'] ?? null) === 'Free' && PartnerTenantStore::can($user, $item['ability']);
    }

    public static function pagePayload(array $partner, string $sectionKey, string $pageKey): array
    {
        $definition = self::findPage($sectionKey, $pageKey);
        abort_unless($definition, 404);

        $rows = self::rowsFor($partner, $sectionKey, $pageKey);
        $stats = self::statsFor($partner, $sectionKey, $pageKey, $rows);

        return [
            'title' => $definition['item']['label'],
            'sectionTitle' => $definition['section']['label'],
            'description' => self::descriptionFor($partner, $sectionKey, $pageKey),
            'apiUrl' => route('partner.api.page', ['section' => $sectionKey, 'page' => $pageKey]),
            'rows' => $rows,
            'columns' => self::columnsFor($rows),
            'stats' => $stats,
            'quickActions' => self::quickActionsFor($sectionKey, $pageKey, $partner),
            'emptyState' => self::emptyStateFor($definition['item']['label']),
            'storeScope' => [
                'store_id' => $partner['store_id'],
                'store_name' => $partner['name'],
                'plan' => $partner['plan'],
            ],
            'breadcrumbs' => [
                ['label' => 'لوحة التحكم', 'url' => route('partner.dashboard')],
                ['label' => $definition['section']['label'], 'url' => null],
                ['label' => $definition['item']['label'], 'url' => null],
            ],
        ];
    }

    public static function dashboardPayload(array $partner, array $user = []): array
    {
        $orders = $partner['orders'] ?? [];
        $products = $partner['products'] ?? [];
        $alerts = $partner['alerts'] ?? [];

        return [
            'kpis' => [
                ['label' => 'المبيعات', 'value' => $partner['metrics']['sales'] ?? '-', 'hint' => 'حسب متجر التاجر', 'tone' => 'violet'],
                ['label' => 'الطلبات', 'value' => $partner['metrics']['orders'] ?? count($orders), 'hint' => 'إجمالي الطلبات', 'tone' => 'emerald'],
                ['label' => 'العملاء', 'value' => $partner['metrics']['customers'] ?? '-', 'hint' => 'عملاء المتجر', 'tone' => 'sky'],
                ['label' => 'التحويل', 'value' => $partner['metrics']['conversion'] ?? '-', 'hint' => 'آخر 30 يوم', 'tone' => 'amber'],
            ],
            'latestOrders' => array_slice($orders, 0, 5),
            'lowStock' => collect($products)->filter(fn (array $product) => (int) ($product['stock'] ?? 0) <= 12)->values()->all(),
            'alerts' => $alerts,
            'quickActions' => self::dashboardQuickActions($partner, $user),
            'setup' => [
                ['label' => 'بيانات المتجر', 'done' => true],
                ['label' => 'الدفع والشحن', 'done' => ! empty($partner['payment_provider']) && ! empty($partner['shipping_provider'])],
                ['label' => 'الدومين', 'done' => ! empty($partner['domain'])],
                ['label' => 'التطبيقات', 'done' => ($partner['plan'] ?? 'Starter') !== 'Starter'],
            ],
        ];
    }

    private static function section(string $key, string $label, string $icon, array $items): array
    {
        return compact('key', 'label', 'icon', 'items');
    }

    private static function dashboardQuickActions(array $partner, array $user): array
    {
        $actions = [
            ['label' => 'طلب يدوي', 'section' => 'orders', 'page' => 'manual', 'route' => 'partner.orders.manual'],
            ['label' => 'منتج جديد', 'section' => 'products', 'page' => 'all', 'route' => 'partner.products.new'],
            ['label' => 'خصم جديد', 'section' => 'marketing', 'page' => 'discounts'],
            ['label' => 'إعدادات الشحن', 'section' => 'settings', 'page' => 'shipping', 'route' => 'partner.settings.section', 'parameters' => ['section' => 'shipping']],
        ];

        return collect($actions)
            ->filter(function (array $action) use ($partner, $user) {
                if ($user === []) {
                    return true;
                }

                $definition = self::findPage($action['section'], $action['page']);

                return $definition && self::isAllowed($definition['item'], $user, $partner);
            })
            ->map(fn (array $action) => [
                'label' => $action['label'],
                'url' => self::dashboardActionUrl($action),
            ])
            ->values()
            ->all();
    }

    private static function dashboardActionUrl(array $action): string
    {
        if (isset($action['route']) && app('router')->has($action['route'])) {
            return route($action['route'], $action['parameters'] ?? []);
        }

        return route('partner.pages.show', ['section' => $action['section'], 'page' => $action['page']]);
    }

    private static function item(string $key, string $label, ?string $legacyRoute, string $ability, string $plan = 'Starter'): array
    {
        $legacyRoute ??= [
            'live' => 'partner.analytics.live',
            'sales' => 'partner.analytics.sales',
            'inventory' => 'partner.analytics.inventory',
            'customers' => 'partner.analytics.customers',
            'finance' => 'partner.analytics.finance',
            'marketing' => 'partner.analytics.marketing',
            'operations' => 'partner.analytics.operations',
            'products' => 'partner.analytics.products',
            'payments' => 'partner.analytics.payments',
        ][$key] ?? null;

        return compact('key', 'label', 'legacyRoute', 'ability', 'plan');
    }

    private static function rowsFor(array $partner, string $sectionKey, string $pageKey): array
    {
        $storeId = $partner['store_id'];
        $baseRows = match ($sectionKey) {
            'dashboard' => self::dashboardRows($partner, $pageKey),
            'orders' => self::orderRows($partner, $pageKey),
            'products' => self::productRows($partner, $pageKey),
            'customers' => self::customerRows($partner, $pageKey),
            'finance' => self::financeRows($partner, $pageKey),
            'settings' => self::settingsRows($partner, $pageKey),
            'analytics' => self::analyticsRows($partner, $pageKey),
            'marketing' => self::marketingRows($partner, $pageKey),
            'services' => self::serviceRows($partner, $pageKey),
            'channels' => self::channelRows($partner, $pageKey),
            'apps' => self::appRows($partner, $pageKey),
            default => [],
        };

        return collect($baseRows)
            ->map(fn (array $row) => ['store_id' => $storeId] + $row)
            ->values()
            ->all();
    }

    private static function dashboardRows(array $partner, string $pageKey): array
    {
        return match ($pageKey) {
            'sales-summary' => [
                ['المؤشر' => 'إجمالي المبيعات', 'القيمة' => $partner['metrics']['sales'] ?? '-', 'الحالة' => 'نشط'],
                ['المؤشر' => 'نسبة التحويل', 'القيمة' => $partner['metrics']['conversion'] ?? '-', 'الحالة' => 'متابعة'],
                ['المؤشر' => 'المرتجعات', 'القيمة' => $partner['metrics']['returns'] ?? '-', 'الحالة' => 'ضمن الطبيعي'],
            ],
            'latest-orders' => self::orderRows($partner, 'all'),
            'notifications' => collect($partner['alerts'] ?? [])->map(fn ($alert) => [
                'العنوان' => $alert['title'],
                'التفاصيل' => $alert['body'],
                'الأولوية' => $alert['tone'] ?? 'info',
            ])->all(),
            default => [],
        };
    }

    private static function orderRows(array $partner, string $pageKey): array
    {
        $orders = $partner['orders'] ?? [];

        return match ($pageKey) {
            'manual' => [
                ['رقم الطلب' => 'MAN-' . Str::upper($partner['id']) . '-01', 'العميل' => $partner['owner'], 'الحالة' => 'مسودة', 'القيمة' => '0 ر.س', 'القناة' => 'يدوي'],
            ],
            'abandoned-carts' => [
                ['السلة' => 'CART-' . Str::upper($partner['id']) . '-7', 'العميل' => $partner['owner'], 'القيمة' => '430 ر.س', 'الإجراء' => 'تذكير واتساب'],
            ],
            'returns' => collect($orders)->take(2)->map(fn ($order) => [
                'رقم الطلب' => $order['id'],
                'العميل' => $order['customer'],
                'الحالة' => 'قيد المراجعة',
                'القيمة' => $order['amount'] ?? '-',
            ])->all(),
            'shipments' => collect($partner['shipments'] ?? [])->map(fn ($row) => [
                'الشحنة' => $row['id'],
                'الناقل' => $row['carrier'],
                'الحالة' => $row['status'],
                'المدينة' => $row['city'],
                'الوصول المتوقع' => $row['eta'],
            ])->all(),
            default => collect($orders)->map(fn ($row) => [
                'رقم الطلب' => $row['id'],
                'العميل' => $row['customer'],
                'الحالة' => $row['status'],
                'القيمة' => $row['amount'] ?? '-',
                'التاريخ' => $row['date'] ?? '-',
            ])->all(),
        };
    }

    private static function productRows(array $partner, string $pageKey): array
    {
        $products = $partner['products'] ?? [];

        return match ($pageKey) {
            'categories' => [
                ['التصنيف' => 'الأكثر مبيعاً', 'المنتجات' => count($products), 'الحالة' => 'منشور'],
                ['التصنيف' => 'وصل حديثاً', 'المنتجات' => max(count($products) - 1, 0), 'الحالة' => 'منشور'],
            ],
            'stock', 'inventory-management' => collect($products)->map(fn ($row) => [
                'SKU' => $row['sku'],
                'المنتج' => $row['name'],
                'المخزون' => $row['stock'],
                'الحالة' => (int) $row['stock'] <= 12 ? 'مخزون منخفض' : 'متوفر',
            ])->all(),
            'filters' => [
                ['المعيار' => 'السعر', 'القيم' => 'منخفض، متوسط، مرتفع', 'الحالة' => 'نشط'],
                ['المعيار' => 'التوفر', 'القيم' => 'متوفر، منخفض، نافد', 'الحالة' => 'نشط'],
            ],
            'custom-fields' => [
                ['الحقل' => 'مقاس', 'النوع' => 'اختيار', 'الحالة' => 'نشط'],
                ['الحقل' => 'لون', 'النوع' => 'قائمة', 'الحالة' => 'نشط'],
            ],
            'options-library' => [
                ['المكتبة' => 'الألوان', 'الخيارات' => '12', 'الحالة' => 'نشط'],
                ['المكتبة' => 'المقاسات', 'الخيارات' => '8', 'الحالة' => 'نشط'],
            ],
            default => collect($products)->map(fn ($row) => [
                'SKU' => $row['sku'],
                'المنتج' => $row['name'],
                'المخزون' => $row['stock'],
                'السعر' => $row['price'],
                'الحالة' => $row['status'],
            ])->all(),
        };
    }

    private static function customerRows(array $partner, string $pageKey): array
    {
        $customers = $partner['customers'] ?? [];

        return match ($pageKey) {
            'groups' => [
                ['المجموعة' => 'VIP', 'العملاء' => max(count($customers) - 1, 1), 'الحالة' => 'نشط'],
                ['المجموعة' => 'عملاء جدد', 'العملاء' => count($customers), 'الحالة' => 'نشط'],
            ],
            'reviews' => collect($customers)->map(fn ($row) => [
                'العميل' => $row['name'],
                'التقييم' => '5/4',
                'الحالة' => 'منشور',
            ])->all(),
            'questions' => collect($customers)->map(fn ($row) => [
                'العميل' => $row['name'],
                'السؤال' => 'استفسار عن المنتج أو الشحن',
                'الحالة' => 'تم الرد',
            ])->all(),
            'stock-notifications' => collect($customers)->map(fn ($row) => [
                'العميل' => $row['name'],
                'القناة' => 'بريد / واتساب',
                'الحالة' => 'مشترك',
            ])->all(),
            default => collect($customers)->map(fn ($row) => [
                'العميل' => $row['name'],
                'البريد' => $row['email'],
                'الطلبات' => $row['orders'] ?? '-',
                'الإنفاق' => $row['spent'] ?? '-',
            ])->all(),
        };
    }

    private static function financeRows(array $partner, string $pageKey): array
    {
        $payments = $partner['payments'] ?? [];

        return match ($pageKey) {
            'invoices' => collect($payments)->map(fn ($row) => [
                'الفاتورة' => 'INV-' . $row['id'],
                'البوابة' => $row['gateway'],
                'القيمة' => $row['amount'],
                'الحالة' => $row['status'],
            ])->all(),
            'wallet' => [
                ['البند' => 'الرصيد المتاح', 'القيمة' => $partner['metrics']['sales'] ?? '-', 'الحالة' => 'قابل للسحب'],
                ['البند' => 'قيد التسوية', 'القيمة' => $partner['metrics']['payments'] ?? '-', 'الحالة' => 'تسوية'],
            ],
            'settlements' => collect($payments)->map(fn ($row) => [
                'العملية' => $row['id'],
                'القيمة' => $row['amount'],
                'التسوية' => $row['settlement'],
                'الحالة' => $row['status'],
            ])->all(),
            default => collect($payments)->map(fn ($row) => [
                'العملية' => $row['id'],
                'البوابة' => $row['gateway'],
                'الحالة' => $row['status'],
                'القيمة' => $row['amount'],
                'التسوية' => $row['settlement'],
            ])->all(),
        };
    }

    private static function settingsRows(array $partner, string $pageKey): array
    {
        return match ($pageKey) {
            'staff' => collect($partner['users'] ?? [])->map(fn ($row) => [
                'الموظف' => $row['name'],
                'البريد' => $row['email'],
                'الدور' => $row['role'],
                'النطاق' => $partner['store_id'],
            ])->all(),
            default => [
                ['الإعداد' => 'اسم المتجر', 'القيمة' => $partner['name'], 'الحالة' => 'نشط'],
                ['الإعداد' => 'الدومين', 'القيمة' => $partner['domain'], 'الحالة' => 'نشط'],
                ['الإعداد' => 'الدفع', 'القيمة' => $partner['payment_provider'], 'الحالة' => $partner['payment_status']],
                ['الإعداد' => 'الشحن', 'القيمة' => $partner['shipping_provider'], 'الحالة' => 'نشط'],
            ],
        };
    }

    private static function analyticsRows(array $partner, string $pageKey): array
    {
        return collect($partner['metrics'] ?? [])->map(fn ($value, $key) => [
            'التقرير' => self::metricLabel((string) $key),
            'القيمة' => $value,
            'الفترة' => $pageKey === 'live' ? 'مباشر' : 'آخر 30 يوم',
            'المصدر' => $partner['store_id'],
        ])->values()->all();
    }

    private static function marketingRows(array $partner, string $pageKey): array
    {
        return [
            ['النشاط' => self::titleFromKey($pageKey), 'الجمهور' => $partner['metrics']['customers'] ?? '-', 'الحالة' => 'نشط', 'العائد' => $partner['metrics']['sales'] ?? '-'],
            ['النشاط' => 'حملة استرجاع العملاء', 'الجمهور' => count($partner['customers'] ?? []), 'الحالة' => 'مجدولة', 'العائد' => 'قيد القياس'],
        ];
    }

    private static function serviceRows(array $partner, string $pageKey): array
    {
        return [
            ['الخدمة' => self::titleFromKey($pageKey), 'المزود' => $pageKey === 'payment-gateways' ? $partner['payment_provider'] : $partner['shipping_provider'], 'الحالة' => 'متصل'],
            ['الخدمة' => 'دعم المتجر', 'المزود' => 'Solve', 'الحالة' => 'نشط'],
        ];
    }

    private static function channelRows(array $partner, string $pageKey): array
    {
        return [
            ['القناة' => self::titleFromKey($pageKey), 'الرابط' => $partner['store_url'], 'الحالة' => $partner['status']],
            ['القناة' => 'كتالوج المنتجات', 'الرابط' => $partner['domain'], 'الحالة' => 'متزامن'],
        ];
    }

    private static function appRows(array $partner, string $pageKey): array
    {
        return [
            ['التطبيق' => self::titleFromKey($pageKey), 'الباقة' => $partner['plan'], 'الحالة' => 'متاح'],
            ['التطبيق' => 'Solve Analytics', 'الباقة' => $partner['plan'], 'الحالة' => 'مثبت'],
        ];
    }

    private static function columnsFor(array $rows): array
    {
        return array_values(array_unique(collect($rows)->flatMap(fn ($row) => array_keys($row))->all()));
    }

    private static function statsFor(array $partner, string $sectionKey, string $pageKey, array $rows): array
    {
        return [
            ['label' => 'السجلات', 'value' => (string) count($rows)],
            ['label' => 'المتجر', 'value' => $partner['store_id']],
            ['label' => 'الباقة', 'value' => $partner['plan'] ?? 'Starter'],
            ['label' => 'API', 'value' => 'نشط'],
        ];
    }

    private static function quickActionsFor(string $sectionKey, string $pageKey, array $partner): array
    {
        $apiUrl = route('partner.api.page', ['section' => $sectionKey, 'page' => $pageKey]);

        return match ($sectionKey) {
            'orders' => [
                self::action('إنشاء طلب يدوي', route('partner.orders.manual')),
                self::action('تصدير الطلبات', route('partner.orders.export')),
                self::action('جميع الطلبات', route('partner.orders')),
                self::action('فتح API', $apiUrl),
            ],
            'products' => [
                self::action('إضافة منتج', route('partner.products.new')),
                self::action('إدارة المخزون', route('partner.products.inventory')),
                self::action('جميع المنتجات', route('partner.products')),
                self::action('فتح API', $apiUrl),
            ],
            'customers' => [
                self::action('قائمة العملاء', route('partner.customers')),
                self::action('إرسال حملة', route('partner.pages.show', ['section' => 'marketing', 'page' => 'campaigns'])),
                self::action('السلات المتروكة', route('partner.orders.abandoned-carts')),
                self::action('فتح API', $apiUrl),
            ],
            'finance' => [
                self::action('المدفوعات', route('partner.payments')),
                self::action('تقارير المدفوعات', route('partner.analytics.payments')),
                self::action('الفواتير', route('partner.pages.show', ['section' => 'finance', 'page' => 'invoices'])),
                self::action('فتح API', $apiUrl),
            ],
            'marketing' => [
                self::action('الخصومات', route('partner.pages.show', ['section' => 'marketing', 'page' => 'discounts'])),
                self::action('الحملات', route('partner.pages.show', ['section' => 'marketing', 'page' => 'campaigns'])),
                self::action('الولاء', route('partner.pages.show', ['section' => 'marketing', 'page' => 'loyalty'])),
                self::action('فتح API', $apiUrl),
            ],
            'services' => [
                self::action('بوابات الدفع', route('partner.pages.show', ['section' => 'services', 'page' => 'payment-gateways'])),
                self::action('اللوجستيات', route('partner.pages.show', ['section' => 'services', 'page' => 'logistics'])),
                self::action('إعدادات الشحن', route('partner.settings.section', ['section' => 'shipping'])),
                self::action('فتح API', $apiUrl),
            ],
            'channels' => [
                self::action('المتجر الإلكتروني', route('partner.pages.show', ['section' => 'channels', 'page' => 'online-store'])),
                self::action('منصات البيع', route('partner.pages.show', ['section' => 'channels', 'page' => 'marketplaces'])),
                self::action('إعدادات الدومين', route('partner.settings.section', ['section' => 'domain'])),
                self::action('فتح API', $apiUrl),
            ],
            'apps' => [
                self::action('التطبيقات المثبتة', route('partner.pages.show', ['section' => 'apps', 'page' => 'installed'])),
                self::action('متجر التطبيقات', route('partner.pages.show', ['section' => 'apps', 'page' => 'marketplace'])),
                self::action('الأتمتة', route('partner.pages.show', ['section' => 'apps', 'page' => 'automation'])),
                self::action('فتح API', $apiUrl),
            ],
            'settings' => [
                self::action('إعدادات المتجر', route('partner.settings.section', ['section' => $pageKey === 'staff' ? 'store' : $pageKey])),
                self::action('الموظفون', route('partner.staff')),
                self::action('الإشعارات', route('partner.settings.section', ['section' => 'notifications'])),
                self::action('فتح API', $apiUrl),
            ],
            default => [
                self::action('تحديث البيانات', route('partner.pages.show', ['section' => $sectionKey, 'page' => $pageKey])),
                self::action('لوحة التحكم', route('partner.dashboard')),
                self::action('فتح API', $apiUrl),
                self::action('المتجر الحالي', route('partner.dashboard') . '#store-' . urlencode((string) $partner['store_id'])),
            ],
        };
    }

    private static function action(string $label, string $url): array
    {
        return compact('label', 'url');
    }

    private static function descriptionFor(array $partner, string $sectionKey, string $pageKey): string
    {
        return 'صفحة مرتبطة ببيانات ' . $partner['name'] . ' فقط عبر store_id: ' . $partner['store_id'] . '. تعرض البيانات من مصدر النظام نفسه المستخدم في لوحة الإدارة.';
    }

    private static function emptyStateFor(string $label): array
    {
        return [
            'title' => 'لا توجد بيانات في ' . $label,
            'body' => 'ستظهر البيانات هنا فور إضافتها من لوحة التاجر أو من لوحة الإدارة الرئيسية.',
        ];
    }

    private static function planRank(string $plan): int
    {
        if (isset(['Free' => 0, 'Starter' => 1, 'Basic' => 1, 'Growth' => 2, 'Pro' => 2, 'Enterprise' => 3][$plan])) {
            return ['Free' => 0, 'Starter' => 1, 'Basic' => 1, 'Growth' => 2, 'Pro' => 2, 'Enterprise' => 3][$plan];
        }

        $managed = SubscriptionManager::plan($plan);

        return ((int) ($managed['sort_order'] ?? 10)) >= 30 ? 3 : (((int) ($managed['sort_order'] ?? 10)) >= 20 ? 2 : 1);
    }

    private static function metricLabel(string $key): string
    {
        return [
            'orders' => 'الطلبات',
            'sales' => 'المبيعات',
            'products' => 'المنتجات',
            'customers' => 'العملاء',
            'payments' => 'نجاح المدفوعات',
            'shipments' => 'الشحنات',
            'conversion' => 'نسبة التحويل',
            'returns' => 'المرتجعات',
        ][$key] ?? Str::headline($key);
    }

    private static function titleFromKey(string $key): string
    {
        return Str::of($key)->replace('-', ' ')->headline()->toString();
    }
}
