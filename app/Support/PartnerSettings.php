<?php

namespace App\Support;

use App\Models\StoreSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class PartnerSettings
{
    public static function ensure(array $partner): ?StoreSetting
    {
        PartnerDashboardSummary::ensureStoreData($partner);

        if (! Schema::hasTable('store_settings')) {
            return null;
        }

        return StoreSetting::query()->firstOrCreate(
            ['store_id' => $partner['store_id']],
            self::defaults($partner),
        );
    }

    public static function groups(array $partner): array
    {
        $settings = self::ensure($partner);
        $sections = self::definitions();

        return collect(self::groupLayout())
            ->map(function (array $group) use ($sections, $partner, $settings) {
                $group['items'] = collect($group['items'])
                    ->map(function (string $key) use ($sections, $partner, $settings) {
                        $definition = $sections[$key];
                        $data = self::sectionValues($partner, $settings, $key);
                        $filled = collect($data)->filter(fn ($value) => filled($value))->count();
                        $total = max(count($definition['fields']), 1);

                        return [
                            'key' => $key,
                            'title' => $definition['title'],
                            'body' => $definition['description'],
                            'icon' => $definition['icon'],
                            'url' => route('partner.settings.section', ['section' => $key]),
                            'status' => self::statusFor($definition, $data),
                            'progress' => min(100, (int) round(($filled / $total) * 100)),
                            'editable' => $definition['editable'],
                        ];
                    })
                    ->values()
                    ->all();

                return $group;
            })
            ->values()
            ->all();
    }

    public static function section(array $partner, string $section): array
    {
        $settings = self::ensure($partner);
        $definitions = self::definitions();
        $definition = $definitions[$section] ?? null;

        abort_unless($definition !== null, 404);

        $values = self::sectionValues($partner, $settings, $section);
        $fields = collect($definition['fields'])
            ->map(fn (array $field) => $field + ['value' => $values[$field['key']] ?? ($field['default'] ?? '')])
            ->values()
            ->all();

        return [
            'key' => $section,
            'title' => $definition['title'],
            'description' => $definition['description'],
            'icon' => $definition['icon'],
            'editable' => $definition['editable'],
            'bucket' => $definition['bucket'],
            'fields' => $fields,
            'data' => $values,
            'tools' => self::toolsFor($partner, $settings, $section, $definition, $values),
            'summary' => self::summaryFor($partner, $settings, $section, $definition, $values),
            'apiUrl' => route('partner.api.settings.section', ['section' => $section]),
            'updatedAt' => $settings?->updated_at?->diffForHumans() ?? 'غير محفوظ بعد',
            'storeScope' => [
                'store_id' => $partner['store_id'],
                'store_name' => $partner['name'],
                'plan' => $partner['plan'],
            ],
            'breadcrumbs' => [
                ['label' => 'لوحة التحكم', 'url' => route('partner.dashboard')],
                ['label' => 'الإعدادات', 'url' => route('partner.settings')],
                ['label' => $definition['title'], 'url' => null],
            ],
        ];
    }

    public static function update(array $partner, string $section, array $data): StoreSetting
    {
        $settings = self::ensure($partner);

        abort_if($settings === null, 503, 'store_settings table is not available. Run the platform migrations before editing settings.');

        $definitions = self::definitions();
        $definition = $definitions[$section] ?? null;

        abort_unless($definition !== null, 404);
        abort_unless($definition['editable'], 403);

        $current = self::bucketData($settings, $definition['bucket']);
        $allowed = collect($definition['fields'])->keyBy('key');
        $clean = [];

        foreach ($data as $key => $value) {
            if (! $allowed->has($key)) {
                continue;
            }

            $field = $allowed[$key];

            if (($field['readonly'] ?? false) === true) {
                continue;
            }

            $clean[$key] = self::normalize((string) $value, $field);
        }

        self::fillBucket($settings, $definition['bucket'], array_merge($current, $clean));
        $settings->save();

        return $settings;
    }

    public static function api(array $partner, string $section): array
    {
        $payload = self::section($partner, $section);

        return [
            'store' => $payload['storeScope'],
            'section' => Arr::only($payload, ['key', 'title', 'description', 'editable', 'bucket', 'data', 'summary', 'tools', 'updatedAt']),
            'meta' => [
                'store_scoped' => true,
                'source_tables' => ['store_settings'],
                'generated_at' => now()->toIso8601String(),
            ],
        ];
    }

    private static function definitions(): array
    {
        return [
            'account' => self::definition('إعدادات الحساب', 'بيانات حساب التاجر والمالك والمستخدم الرئيسي.', 'users', 'identity', true, [
                self::field('merchant_name', 'اسم مالك الحساب', 'text', 'مثال: نورة أحمد'),
                self::field('email', 'بريد الحساب', 'email', 'merchant@example.com'),
                self::field('phone', 'جوال الحساب', 'text', '9665xxxxxxxx'),
                self::field('plan', 'الباقة', 'select', '', ['Starter', 'Growth', 'Enterprise'], true),
            ]),
            'store' => self::definition('إعدادات المتجر', 'اسم المتجر وبيانات ظهوره داخل لوحة التاجر والواجهة.', 'store', 'identity', true, [
                self::field('name', 'اسم المتجر', 'text', 'اسم المتجر'),
                self::field('owner', 'المالك', 'text', 'اسم المالك'),
                self::field('email', 'بريد المتجر', 'email', 'store@example.com'),
                self::field('phone', 'رقم التواصل', 'text', '9665xxxxxxxx'),
                self::field('description', 'وصف المتجر', 'textarea', 'نبذة مختصرة عن المتجر'),
                self::field('address', 'العنوان', 'text', 'الرياض، المملكة العربية السعودية'),
                self::field('city', 'المدينة', 'text', 'الرياض'),
                self::field('country', 'الدولة', 'text', 'السعودية'),
                self::field('currency', 'العملة', 'select', '', ['SAR', 'USD', 'AED']),
                self::field('language', 'اللغة', 'select', '', ['ar', 'en']),
                self::field('working_hours', 'أوقات العمل', 'text', '09:00 - 22:00'),
                self::field('store_status', 'حالة المتجر', 'select', '', ['open', 'temporarily_closed']),
                self::field('commercial_registration', 'السجل التجاري', 'text', 'اختياري'),
                self::field('business_type', 'نوع النشاط', 'select', '', ['تجزئة', 'جملة', 'خدمات', 'منتجات رقمية']),
            ]),
            'identity' => self::definition('الهوية العامة', 'الشعار والألوان ونمط واجهة المتجر.', 'settings', 'branding', true, [
                self::field('logo', 'الشعار', 'text', 'solve-logo.png'),
                self::field('favicon', 'الفافيكون', 'text', 'favicon.png'),
                self::field('social_image', 'صورة المشاركة الاجتماعية', 'text', 'social-share.png'),
                self::field('primary_color', 'اللون الأساسي', 'color', '#5b21b6'),
                self::field('accent_color', 'لون مساعد', 'color', '#06b6d4'),
                self::field('theme', 'قالب المتجر', 'select', '', ['Solve Minimal', 'Solve Retail', 'Solve Premium']),
                self::field('font', 'الخط', 'select', '', ['Tajawal', 'Cairo', 'IBM Plex Sans Arabic']),
            ]),
            'domain' => self::definition('الدومين الخاص', 'نطاق المتجر وروابط النشر وحالة SSL.', 'plug', 'identity', true, [
                self::field('domain', 'الدومين الأساسي', 'text', 'example.com'),
                self::field('store_url', 'رابط المتجر', 'url', 'https://example.com'),
                self::field('custom_domain', 'دومين مخصص', 'text', 'shop.example.com'),
                self::field('ssl', 'SSL', 'select', '', ['نشط', 'قيد الربط', 'غير مفعل']),
                self::field('dns_status', 'حالة DNS', 'text', 'pending'),
                self::field('domain_status', 'حالة الدومين', 'select', '', ['active', 'disabled']),
            ]),
            'shipping' => self::definition('خيارات الشحن', 'مزود الشحن ومدينة الانطلاق وسياسات التسليم.', 'shopping-bag', 'shipping', true, [
                self::field('provider', 'مزود الشحن', 'select', '', ['Solve Logistics', 'سمسا', 'أرامكس', 'ناقل', 'شحن يدوي']),
                self::field('regions', 'مناطق الشحن', 'textarea', 'الرياض، جدة، الدمام'),
                self::field('shipping_rates', 'قواعد أسعار الشحن', 'textarea', 'الرياض:25، فوق 300 مجاني'),
                self::field('api_key', 'مفتاح الربط', 'text', '********'),
                self::field('city_from', 'مدينة الانطلاق', 'text', 'الرياض'),
                self::field('default_fee', 'رسوم الشحن الافتراضية', 'number', '25'),
                self::field('free_shipping_min', 'شحن مجاني عند', 'number', '300'),
                self::field('same_day', 'الشحن في نفس اليوم', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('return_policy', 'سياسة الإرجاع للشحن', 'textarea', 'اكتب السياسة المختصرة'),
            ]),
            'payments' => self::definition('خيارات الدفع', 'بوابة الدفع وحالة التحصيل وروابط الدفع.', 'wallet', 'payments', true, [
                self::field('provider', 'بوابة الدفع', 'select', '', ['مدى', 'Apple Pay', 'STC Pay', 'تحويل بنكي', 'دفع عند الاستلام']),
                self::field('mode', 'وضع التشغيل', 'select', '', ['test', 'production']),
                self::field('api_key', 'مفتاح الربط', 'text', '********'),
                self::field('bank_account', 'حساب التحويل البنكي', 'text', 'SA0000000000000000000000'),
                self::field('status', 'حالة التحصيل', 'select', '', ['نشط', 'قيد التفعيل', 'متوقف']),
                self::field('payment_link', 'روابط الدفع', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('cod_enabled', 'الدفع عند الاستلام', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('minimum_order', 'حد الطلب الأدنى', 'number', '0'),
            ]),
            'checkout' => self::definition('صفحة الدفع', 'حقول صفحة الدفع وسلوك إتمام الطلب.', 'layout-dashboard', 'branding', true, [
                self::field('guest_checkout', 'الشراء كزائر', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('required_phone', 'الجوال إلزامي', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('required_email', 'البريد إلزامي', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('address_notes', 'ملاحظات العنوان', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('success_message', 'رسالة نجاح الطلب', 'textarea', 'تم استلام طلبك بنجاح.'),
            ]),
            'taxes' => self::definition('ضريبة القيمة المضافة', 'الرقم الضريبي ونسبة الضريبة وإعدادات الفوترة.', 'grid', 'taxes', true, [
                self::field('enabled', 'تفعيل الضريبة', 'select', '', ['enabled', 'disabled']),
                self::field('vat', 'نسبة الضريبة', 'select', '', ['15%', '0%', 'معفي']),
                self::field('tax_number', 'الرقم الضريبي', 'text', '300000000000003'),
                self::field('invoice_tax_label', 'مسمى الضريبة في الفاتورة', 'text', 'VAT'),
                self::field('prices_include_tax', 'الأسعار تشمل الضريبة', 'select', '', ['نعم', 'لا']),
            ]),
            'bank-accounts' => self::definition('الحسابات البنكية', 'حسابات التحويل والتسويات الخاصة بالمتجر.', 'wallet', 'payments', true, [
                self::field('bank_transfer', 'اسم البنك', 'text', 'اسم البنك'),
                self::field('iban', 'IBAN', 'text', 'SA0000000000000000000000'),
                self::field('settlement_account', 'حساب التسوية', 'text', 'الحساب الرئيسي'),
                self::field('settlement_cycle', 'دورة التسوية', 'select', '', ['يومي', 'أسبوعي', 'شهري']),
            ]),
            'api' => self::definition('التطبيقات وواجهة برمجة التطبيقات (API)', 'مفاتيح الربط وحالة الويب هوك والتكاملات.', 'plug', 'branding', false, [
                self::field('api_status', 'حالة API', 'text', '', [], true),
                self::field('webhook_url', 'Webhook URL', 'url', '', [], true),
                self::field('store_scope', 'Store Scope', 'text', '', [], true),
            ]),
            'order-settings' => self::definition('إعدادات الطلبات', 'ترقيم الطلبات وحالات المعالجة والتنبيهات.', 'shopping-bag', 'identity', true, [
                self::field('order_prefix', 'بادئة الطلب', 'text', 'SO'),
                self::field('auto_confirm', 'تأكيد الطلب تلقائيًا', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('reserve_stock', 'حجز المخزون عند الطلب', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('cancel_unpaid_after', 'إلغاء غير المدفوع بعد ساعات', 'number', '24'),
                self::field('invoice_prefix', 'بادئة الفاتورة', 'text', 'INV'),
            ]),
            'zatca' => self::definition('الربط مع هيئة الزكاة (ZATCA)', 'حالة الربط الضريبي والفوترة الإلكترونية.', 'grid', 'taxes', false, [
                self::field('tax_number', 'الرقم الضريبي', 'text', '', [], true),
                self::field('vat', 'الضريبة', 'text', '', [], true),
                self::field('integration_status', 'حالة الربط', 'text', '', [], true),
            ]),
            'maintenance' => self::definition('حالة المتجر', 'تشغيل المتجر ووضع الصيانة وحالة الظهور.', 'bolt', 'branding', true, [
                self::field('status', 'حالة المتجر', 'select', '', ['نشط', 'متوقف', 'تجريبي']),
                self::field('maintenance_mode', 'وضع الصيانة', 'select', '', ['متوقف', 'مفعل']),
                self::field('maintenance_message', 'رسالة الصيانة', 'textarea', 'المتجر تحت الصيانة حاليًا.'),
                self::field('visibility', 'الظهور للعملاء', 'select', '', ['متاح للعملاء', 'مخفي مؤقتًا']),
            ]),
            'contacts' => self::definition('بيانات التواصل', 'البريد والجوال وروابط الدعم الرسمية.', 'users', 'identity', true, [
                self::field('support_email', 'بريد الدعم', 'email', 'support@example.com'),
                self::field('support_phone', 'جوال الدعم', 'text', '9665xxxxxxxx'),
                self::field('whatsapp', 'واتساب', 'text', '9665xxxxxxxx'),
                self::field('support_url', 'رابط الدعم', 'url', 'https://example.com/contact'),
            ]),
            'messages' => self::definition('رسائل الطلبات', 'قوالب رسائل الطلبات والشحن والإلغاء.', 'megaphone', 'identity', true, [
                self::field('order_created', 'رسالة إنشاء الطلب', 'textarea', 'شكرًا لطلبك من متجرنا.'),
                self::field('order_shipped', 'رسالة الشحن', 'textarea', 'طلبك في الطريق.'),
                self::field('order_cancelled', 'رسالة الإلغاء', 'textarea', 'تم إلغاء طلبك.'),
            ]),
            'review-messages' => self::definition('رسائل التقييمات', 'رسائل طلب تقييم المنتج وتجربة العميل.', 'megaphone', 'identity', true, [
                self::field('review_request', 'طلب التقييم', 'textarea', 'شاركنا تقييمك للمنتج.'),
                self::field('review_reminder', 'تذكير التقييم', 'textarea', 'رأيك يساعدنا على تحسين التجربة.'),
            ]),
            'notifications' => self::definition('إعدادات الإشعارات', 'قنوات التنبيه للطلبات والعملاء والمخزون.', 'megaphone', 'branding', true, [
                self::field('channels', 'قنوات الإشعار', 'select', '', ['لوحة التحكم', 'بريد وواتساب', 'كل القنوات']),
                self::field('email_enabled', 'إشعارات البريد', 'select', '', ['enabled', 'disabled']),
                self::field('sms_enabled', 'إشعارات SMS', 'select', '', ['enabled', 'disabled']),
                self::field('whatsapp_enabled', 'إشعارات WhatsApp', 'select', '', ['enabled', 'disabled']),
                self::field('template_order_created', 'قالب طلب جديد', 'textarea', 'تم استلام طلب جديد من متجرك.'),
                self::field('orders', 'تنبيهات الطلبات', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('stock', 'تنبيهات المخزون', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('payments', 'تنبيهات المدفوعات', 'select', '', ['مفعل', 'غير مفعل']),
            ]),
            'social' => self::definition('وسائل التواصل', 'روابط حسابات المتجر في الشبكات الاجتماعية.', 'grid', 'identity', true, [
                self::field('instagram', 'Instagram', 'url', 'https://instagram.com/store'),
                self::field('x', 'X', 'url', 'https://x.com/store'),
                self::field('snapchat', 'Snapchat', 'url', 'https://snapchat.com/add/store'),
                self::field('tiktok', 'TikTok', 'url', 'https://tiktok.com/@store'),
            ]),
            'languages' => self::definition('اللغات والترجمة', 'لغة المتجر الأساسية وخيارات التعريب.', 'settings', 'branding', true, [
                self::field('default_language', 'اللغة الأساسية', 'select', '', ['العربية', 'English']),
                self::field('secondary_language', 'لغة إضافية', 'select', '', ['English', 'لا توجد']),
                self::field('direction', 'اتجاه الواجهة', 'select', '', ['RTL', 'LTR']),
            ]),
            'storefront' => self::definition('واجهة المتجر', 'إعدادات الواجهة والقوالب والصفحة الرئيسية.', 'layout-dashboard', 'branding', true, [
                self::field('theme', 'القالب', 'select', '', ['Solve Minimal', 'Solve Retail', 'Solve Premium']),
                self::field('homepage', 'الصفحة الرئيسية', 'select', '', ['مفعلة', 'قيد التصميم', 'مخفية']),
                self::field('hero_title', 'عنوان الواجهة', 'text', 'مرحبًا بكم في متجرنا'),
                self::field('show_featured_products', 'عرض المنتجات المميزة', 'select', '', ['مفعل', 'غير مفعل']),
            ]),
            'categories-display' => self::definition('التصنيفات المتعددة', 'طريقة عرض التصنيفات داخل واجهة المتجر.', 'grid', 'branding', true, [
                self::field('display_mode', 'طريقة العرض', 'select', '', ['شبكي', 'قائمة', 'صور كبيرة']),
                self::field('show_counts', 'إظهار عدد المنتجات', 'select', '', ['مفعل', 'غير مفعل']),
                self::field('featured_category', 'تصنيف مميز', 'text', 'الأكثر مبيعًا'),
            ]),
            'working-hours' => self::definition('أوقات العمل الرسمية', 'ساعات العمل والاستقبال والتنفيذ.', 'settings', 'identity', true, [
                self::field('timezone', 'المنطقة الزمنية', 'select', '', ['Asia/Riyadh', 'UTC']),
                self::field('weekdays', 'أيام الأسبوع', 'text', '09:00 - 22:00'),
                self::field('weekend', 'نهاية الأسبوع', 'text', '12:00 - 22:00'),
                self::field('holiday_message', 'رسالة الإجازة', 'textarea', 'سيتم تنفيذ الطلب بعد انتهاء الإجازة.'),
            ]),
            'staff' => self::definition('فريق العمل', 'مستخدمو المتجر وأدوارهم وصلاحياتهم.', 'users', 'identity', false, []),
            'permissions' => self::definition('مجموعات فريق العمل', 'مجموعات الصلاحيات داخل المتجر.', 'grid', 'identity', false, [
                self::field('role', 'الدور الحالي', 'text', '', [], true),
                self::field('staff_count', 'عدد الموظفين', 'text', '', [], true),
                self::field('scope', 'نطاق الصلاحية', 'text', '', [], true),
            ]),
            'security' => self::definition('الأمان', 'الجلسات النشطة وتسجيل الدخول والمصادقة الثنائية.', 'shield', 'identity', false, [
                self::field('two_factor_enabled', 'المصادقة الثنائية', 'text', '', [], true),
                self::field('trusted_devices', 'الأجهزة الموثوقة', 'text', '', [], true),
                self::field('login_history', 'سجل الدخول', 'text', '', [], true),
            ]),
            'branches' => self::definition('الفروع والمخازن', 'إدارة الفروع ومصادر المخزون.', 'store', 'shipping', true, [
                self::field('main', 'الفرع الرئيسي', 'text', 'المستودع الرئيسي'),
                self::field('inventory_source', 'مصدر المخزون', 'select', '', ['لوحة Solve', 'مستودع خارجي', 'POS']),
                self::field('pickup_enabled', 'الاستلام من الفرع', 'select', '', ['مفعل', 'غير مفعل']),
            ]),
            'legal' => self::definition('سياسات المتجر', 'الاستبدال والاسترجاع والخصوصية والشروط.', 'settings', 'branding', true, [
                self::field('returns_policy', 'سياسة الاسترجاع', 'textarea', 'الاسترجاع خلال 7 أيام.'),
                self::field('privacy_policy', 'سياسة الخصوصية', 'textarea', 'سياسة الخصوصية مفعلة.'),
                self::field('terms', 'الشروط والأحكام', 'textarea', 'الشروط والأحكام مفعلة.'),
            ]),
            'pos' => self::definition('نقاط البيع', 'حالة الربط مع نقاط البيع الخاصة بالمتجر.', 'wallet', 'payments', false, [
                self::field('status', 'حالة POS', 'text', '', [], true),
                self::field('plan', 'الباقة', 'text', '', [], true),
                self::field('store_id', 'Store ID', 'text', '', [], true),
            ]),
        ];
    }

    private static function groupLayout(): array
    {
        return [
            ['title' => 'عام', 'items' => ['account', 'domain', 'shipping', 'payments', 'checkout', 'store', 'taxes', 'bank-accounts', 'api', 'identity', 'order-settings', 'zatca', 'maintenance']],
            ['title' => 'التواصل', 'items' => ['contacts', 'messages', 'review-messages', 'notifications', 'social', 'languages']],
            ['title' => 'المتجر', 'items' => ['storefront', 'categories-display', 'working-hours', 'staff', 'permissions', 'security', 'branches', 'legal', 'pos']],
        ];
    }

    private static function sectionValues(array $partner, ?StoreSetting $settings, string $section): array
    {
        $definition = self::definitions()[$section] ?? null;
        $bucket = $definition ? self::bucketData($settings, $definition['bucket']) : [];

        $computed = [
            'account' => ['merchant_name' => $partner['owner'] ?? '', 'email' => $partner['email'] ?? '', 'phone' => $partner['phone'] ?? '', 'plan' => $partner['plan'] ?? ''],
            'domain' => ['domain' => $partner['domain'] ?? '', 'store_url' => $partner['store_url'] ?? '', 'ssl' => 'نشط'],
            'api' => ['webhook_url' => 'https://' . ($partner['domain'] ?? 'store.solve.sa') . '/webhooks/solve', 'api_status' => 'نشط', 'store_scope' => $partner['store_id'] ?? ''],
            'zatca' => ['tax_number' => $bucket['tax_number'] ?? '', 'vat' => $bucket['vat'] ?? '15%', 'integration_status' => 'جاهز للربط'],
            'permissions' => ['role' => 'Partner Admin', 'staff_count' => count($partner['users'] ?? []), 'scope' => $partner['store_id'] ?? ''],
            'security' => ['two_factor_enabled' => ! empty($bucket['two_factor_enabled']) ? 'enabled' : 'disabled', 'trusted_devices' => '1', 'login_history' => 'active'],
            'pos' => ['status' => 'غير مفعل', 'plan' => $partner['plan'] ?? '', 'store_id' => $partner['store_id'] ?? ''],
        ][$section] ?? [];

        $values = array_merge($bucket, $computed);

        if ($definition) {
            foreach ($definition['fields'] as $field) {
                $values[$field['key']] ??= $field['default'] ?? '';
            }
        }

        return $values;
    }

    private static function toolsFor(array $partner, ?StoreSetting $settings, string $section, array $definition, array $values): array
    {
        return [
            ['label' => 'نطاق البيانات', 'value' => $partner['store_id'], 'tone' => 'solve'],
            ['label' => 'مصدر الحفظ', 'value' => 'store_settings.' . $definition['bucket'], 'tone' => 'slate'],
            ['label' => 'حالة القسم', 'value' => self::statusFor($definition, $values), 'tone' => $definition['editable'] ? 'emerald' : 'amber'],
            ['label' => 'آخر تحديث', 'value' => $settings?->updated_at?->diffForHumans() ?? 'غير محفوظ', 'tone' => 'slate'],
            ['label' => 'API', 'value' => route('partner.api.settings.section', ['section' => $section]), 'tone' => 'solve'],
        ];
    }

    private static function summaryFor(array $partner, ?StoreSetting $settings, string $section, array $definition, array $values): array
    {
        $filled = collect($definition['fields'])->filter(fn (array $field) => filled($values[$field['key']] ?? null))->count();
        $total = max(count($definition['fields']), 1);

        return [
            ['label' => 'اكتمال القسم', 'value' => min(100, (int) round(($filled / $total) * 100)) . '%'],
            ['label' => 'الحقول', 'value' => $filled . ' / ' . $total],
            ['label' => 'الباقة', 'value' => $partner['plan'] ?? 'Starter'],
        ];
    }

    private static function defaults(array $partner): array
    {
        return [
            'identity' => [
                'name' => $partner['name'] ?? '',
                'owner' => $partner['owner'] ?? '',
                'email' => $partner['email'] ?? '',
                'phone' => $partner['phone'] ?? '',
                'domain' => $partner['domain'] ?? '',
                'store_url' => $partner['store_url'] ?? '',
                'business_type' => 'تجزئة',
                'order_prefix' => 'SO',
                'auto_confirm' => 'غير مفعل',
                'timezone' => 'Asia/Riyadh',
            ],
            'branding' => [
                'logo' => $partner['logo'] ?? 'solve-logo.png',
                'primary_color' => '#5b21b6',
                'accent_color' => '#06b6d4',
                'theme' => 'Solve Minimal',
                'guest_checkout' => 'مفعل',
                'required_phone' => 'مفعل',
                'channels' => 'لوحة التحكم',
                'orders' => 'مفعل',
                'stock' => 'مفعل',
                'status' => $partner['status'] ?? 'نشط',
                'maintenance_mode' => 'متوقف',
            ],
            'payments' => [
                'provider' => $partner['payment_provider'] ?? '',
                'status' => $partner['payment_status'] ?? 'قيد التفعيل',
                'payment_link' => 'مفعل',
                'settlement_account' => 'الحساب الرئيسي',
                'settlement_cycle' => 'أسبوعي',
            ],
            'shipping' => [
                'provider' => $partner['shipping_provider'] ?? '',
                'city_from' => 'الرياض',
                'default_fee' => '25',
                'free_shipping_min' => '300',
                'same_day' => 'غير مفعل',
                'main' => 'المستودع الرئيسي',
                'inventory_source' => 'لوحة Solve',
            ],
            'taxes' => [
                'vat' => '15%',
                'tax_number' => '',
                'prices_include_tax' => 'لا',
            ],
            'invoices' => [
                'prefix' => 'INV',
                'template' => 'Solve Classic',
            ],
        ];
    }

    private static function bucketData(?StoreSetting $settings, string $bucket): array
    {
        if (! $settings) {
            return [];
        }

        return $settings->{$bucket} ?? [];
    }

    private static function fillBucket(StoreSetting $settings, string $bucket, array $data): void
    {
        $settings->{$bucket} = $data;
    }

    private static function normalize(string $value, array $field): string
    {
        if (($field['type'] ?? 'text') === 'number') {
            return (string) max(0, (float) preg_replace('/[^\d.]/', '', $value));
        }

        return trim($value);
    }

    private static function definition(string $title, string $description, string $icon, string $bucket, bool $editable, array $fields): array
    {
        return compact('title', 'description', 'icon', 'bucket', 'editable', 'fields');
    }

    private static function field(string $key, string $label, string $type = 'text', string $placeholder = '', array $options = [], bool $readonly = false): array
    {
        return compact('key', 'label', 'type', 'placeholder', 'options', 'readonly');
    }

    private static function statusFor(array $definition, array $data): string
    {
        if (! $definition['editable']) {
            return 'مرتبط بالنظام';
        }

        return collect($definition['fields'])->filter(fn (array $field) => filled($data[$field['key']] ?? null))->isNotEmpty()
            ? 'جاهز'
            : 'يحتاج إعداد';
    }
}
