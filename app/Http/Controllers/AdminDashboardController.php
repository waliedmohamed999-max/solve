<?php

namespace App\Http\Controllers;

use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function dashboard(): View
    {
        $data = $this->sharedData();

        return view('admin.dashboard', array_merge($data, [
            'activeRoute' => 'admin.dashboard',
            'pageTitle' => 'الرئيسية',
            'pageDescription' => 'متابعة مؤشرات منصة Solve التشغيلية ونمو المتاجر من مكان واحد.',
        ]));
    }

    public function stores(): View
    {
        $data = $this->sharedData();

        return $this->renderSection(
            'admin.stores',
            'إدارة المتاجر',
            'عرض وإدارة المتاجر والتجار وحالات التشغيل والباقات.',
            [
                ['label' => 'إجمالي المتاجر', 'value' => '12,480', 'change' => '+18.4%'],
                ['label' => 'المتاجر النشطة', 'value' => '10,932', 'change' => '+12.1%'],
                ['label' => 'المتاجر المعلقة', 'value' => '274', 'change' => '-4.3%'],
                ['label' => 'متاجر تحتاج مراجعة', 'value' => '67', 'change' => '+2.1%'],
            ],
            [
                [
                    'title' => 'قائمة المتاجر',
                    'kind' => 'table',
                    'span' => 'full',
                    'columns' => ['اسم المتجر', 'اسم التاجر', 'الحالة', 'الباقة', 'المبيعات', 'الطلبات', 'تاريخ الإنشاء'],
                    'rows' => array_map(fn ($store) => [
                        $store['name'],
                        $store['owner'],
                        $store['status'],
                        $store['plan'],
                        $store['sales'],
                        $store['orders'],
                        $store['created_at'],
                    ], $data['stores']),
                ],
                [
                    'title' => 'آخر الإجراءات',
                    'kind' => 'list',
                    'items' => [
                        ['title' => 'مراجعة متجر شهد بوتيك', 'meta' => 'حالة المتجر: مراجعة', 'value' => 'الآن'],
                        ['title' => 'تعليق متجر أبعاد بيوتي', 'meta' => 'مشكلة دفع معلقة', 'value' => 'منذ 30 دقيقة'],
                        ['title' => 'ترقية متجر أطلس', 'meta' => 'Enterprise', 'value' => 'اليوم'],
                    ],
                ],
                [
                    'title' => 'فلاتر سريعة',
                    'kind' => 'tags',
                    'items' => ['نشط', 'معلق', 'تحت المراجعة', 'Starter', 'Growth', 'Enterprise'],
                ],
            ]
        );
    }

    public function subscriptions(): View
    {
        $data = $this->sharedData();

        return $this->renderSection(
            'admin.subscriptions',
            'الاشتراكات',
            'إدارة الباقات والاشتراكات النشطة والمنتهية وعمليات الترقية.',
            [
                ['label' => 'الاشتراكات النشطة', 'value' => '8,740', 'change' => '+11.3%'],
                ['label' => 'الاشتراكات المنتهية', 'value' => '412', 'change' => '-3.6%'],
                ['label' => 'عمليات الترقية', 'value' => '186', 'change' => '+18.0%'],
                ['label' => 'إيراد الباقات', 'value' => '2.91M ر.س', 'change' => '+9.2%'],
            ],
            [
                [
                    'title' => 'الباقات المتاحة',
                    'kind' => 'list',
                    'span' => 'full',
                    'items' => array_map(fn ($plan) => [
                        'title' => $plan['name'],
                        'meta' => $plan['subs'] . ' اشتراك نشط',
                        'value' => $plan['revenue'],
                    ], $data['plans']),
                ],
                [
                    'title' => 'مؤشرات التجديد',
                    'kind' => 'metrics',
                    'items' => [
                        ['label' => 'التجديد التلقائي', 'value' => '81%', 'width' => '81%'],
                        ['label' => 'الاحتفاظ بالعملاء', 'value' => '92%', 'width' => '92%'],
                        ['label' => 'الترقيات', 'value' => '34%', 'width' => '34%'],
                    ],
                ],
            ]
        );
    }

    public function payments(): View
    {
        $data = $this->sharedData();

        return $this->renderSection(
            'admin.payments',
            'المدفوعات',
            'متابعة بوابات الدفع وسجل العمليات والنجاح والفشل والاسترجاعات.',
            [
                ['label' => 'المدفوعات الناجحة', 'value' => '97.6%', 'change' => '+1.1%'],
                ['label' => 'العمليات الفاشلة', 'value' => '2.4%', 'change' => '-0.4%'],
                ['label' => 'طلبات الاسترجاع', 'value' => '131', 'change' => '+3.9%'],
                ['label' => 'إجمالي حجم المدفوعات', 'value' => '12.8M ر.س', 'change' => '+16.2%'],
            ],
            [
                [
                    'title' => 'بوابات الدفع',
                    'kind' => 'list',
                    'items' => array_map(fn ($payment) => [
                        'title' => $payment['name'],
                        'meta' => 'فاشلة: ' . $payment['failed'] . ' - استرجاعات: ' . $payment['refunds'],
                        'value' => $payment['success'],
                    ], $data['payments']),
                ],
                [
                    'title' => 'سجل العمليات الأخيرة',
                    'kind' => 'table',
                    'columns' => ['رقم العملية', 'المتجر', 'البوابة', 'الحالة', 'القيمة'],
                    'rows' => [
                        ['TX-8452', 'متجر أطلس', 'مدى', 'ناجحة', '4,200 ر.س'],
                        ['TX-8451', 'رواء هوم', 'Visa', 'ناجحة', '870 ر.س'],
                        ['TX-8450', 'أبعاد بيوتي', 'Apple Pay', 'فاشلة', '129 ر.س'],
                        ['TX-8449', 'شهد بوتيك', 'Mastercard', 'استرجاع', '640 ر.س'],
                    ],
                ],
            ]
        );
    }

    public function shipping(): View
    {
        $data = $this->sharedData();

        return $this->renderSection(
            'admin.shipping',
            'الشحن',
            'إدارة شركات الشحن، الأداء، والتأخيرات وإعدادات الربط.',
            [
                ['label' => 'الشركات المتصلة', 'value' => '3', 'change' => 'مفعلة'],
                ['label' => 'الشحنات هذا الشهر', 'value' => '29,357', 'change' => '+8.6%'],
                ['label' => 'الشحنات المتأخرة', 'value' => '814', 'change' => '-2.1%'],
                ['label' => 'متوسط الأداء', 'value' => '4.5/5', 'change' => '+0.2'],
            ],
            [
                [
                    'title' => 'أداء شركات الشحن',
                    'kind' => 'table',
                    'span' => 'full',
                    'columns' => ['الشركة', 'الشحنات', 'التأخير', 'التقييم'],
                    'rows' => array_map(fn ($partner) => [
                        $partner['name'],
                        $partner['deliveries'],
                        $partner['delay'],
                        $partner['score'],
                    ], $data['shippingPartners']),
                ],
                [
                    'title' => 'إعدادات سريعة',
                    'kind' => 'tags',
                    'items' => ['تفعيل الدفع عند الاستلام', 'الشحن الدولي', 'رسوم ثابتة', 'زمن تجهيز الطلب'],
                ],
            ]
        );
    }

    public function analytics(): View
    {
        return $this->renderSection(
            'admin.analytics',
            'التحليلات',
            'لوحة تحليلات للمبيعات والطلبات ونمو المتاجر وأداء الدفع والشحن.',
            [
                ['label' => 'نمو المتاجر', 'value' => '24.6%', 'change' => '+3.1%'],
                ['label' => 'نمو المبيعات', 'value' => '14.8%', 'change' => '+1.4%'],
                ['label' => 'تحويل الزيارات', 'value' => '6.8%', 'change' => '+0.6%'],
                ['label' => 'أداء الشحن', 'value' => '94.1%', 'change' => '+0.9%'],
            ],
            [
                [
                    'title' => 'المؤشرات الرئيسية',
                    'kind' => 'metrics',
                    'span' => 'full',
                    'items' => [
                        ['label' => 'المبيعات', 'value' => '89%', 'width' => '89%'],
                        ['label' => 'الطلبات', 'value' => '73%', 'width' => '73%'],
                        ['label' => 'أفضل المتاجر', 'value' => '66%', 'width' => '66%'],
                        ['label' => 'أداء الدفع', 'value' => '97%', 'width' => '97%'],
                    ],
                ],
                [
                    'title' => 'أفضل المتاجر',
                    'kind' => 'list',
                    'items' => [
                        ['title' => 'شهد بوتيك', 'meta' => 'أعلى مبيعات هذا الربع', 'value' => '684K ر.س'],
                        ['title' => 'متجر أطلس', 'meta' => 'أفضل معدل نمو', 'value' => '641K ر.س'],
                        ['title' => 'Solar Free', 'meta' => 'أفضل أداء مدفوعات', 'value' => '530K ر.س'],
                    ],
                ],
            ]
        );
    }

    public function partners(): View
    {
        $data = $this->sharedData();

        return $this->renderSection(
            'admin.partners',
            'خدمات الشركاء',
            'إدارة خدمات التصميم، التسويق، التغليف والتصوير وطلبات التجار.',
            [
                ['label' => 'الطلبات المفتوحة', 'value' => '190', 'change' => '+7.2%'],
                ['label' => 'خدمة التصميم', 'value' => '86', 'change' => 'طلب'],
                ['label' => 'خدمة التسويق', 'value' => '51', 'change' => 'طلب'],
                ['label' => 'متوسط SLA', 'value' => '2.8 يوم', 'change' => '-0.4 يوم'],
            ],
            [
                [
                    'title' => 'الخدمات الحالية',
                    'kind' => 'list',
                    'span' => 'full',
                    'items' => array_map(fn ($service) => [
                        'title' => $service['name'],
                        'meta' => $service['requests'],
                        'value' => $service['sla'],
                    ], $data['services']),
                ],
            ]
        );
    }

    public function support(): View
    {
        $data = $this->sharedData();

        return $this->renderSection(
            'admin.support',
            'الدعم الفني',
            'متابعة التذاكر المفتوحة وتحديد الأولوية وتعيين الموظفين وخطوات الحل.',
            [
                ['label' => 'التذاكر المفتوحة', 'value' => '92', 'change' => '-11.5%'],
                ['label' => 'حرجة', 'value' => '7', 'change' => '+2'],
                ['label' => 'قيد المعالجة', 'value' => '31', 'change' => '-4'],
                ['label' => 'متوسط وقت الإغلاق', 'value' => '5.4 س', 'change' => '-0.8 س'],
            ],
            [
                [
                    'title' => 'التذاكر الحالية',
                    'kind' => 'table',
                    'span' => 'full',
                    'columns' => ['التذكرة', 'النوع', 'الأولوية', 'المسؤول', 'الحالة'],
                    'rows' => array_map(fn ($ticket) => [
                        $ticket['title'],
                        $ticket['type'],
                        $ticket['priority'],
                        $ticket['assignee'],
                        $ticket['status'],
                    ], $data['tickets']),
                ],
            ]
        );
    }

    public function apps(): View
    {
        return $this->renderSection(
            'admin.apps',
            'التطبيقات',
            'متابعة تطبيق المتجر وعدد التحميلات والمستخدمين النشطين وأداء النسخ.',
            [
                ['label' => 'عدد التحميلات', 'value' => '84K', 'change' => '+12.4%'],
                ['label' => 'المستخدمون النشطون', 'value' => '21.4K', 'change' => '+8.9%'],
                ['label' => 'معدل التحويل', 'value' => '31%', 'change' => '+4.2%'],
                ['label' => 'تقييم التطبيق', 'value' => '4.8/5', 'change' => '+0.1'],
            ],
            [
                [
                    'title' => 'مؤشرات التطبيق',
                    'kind' => 'metrics',
                    'items' => [
                        ['label' => 'Android', 'value' => '74%', 'width' => '74%'],
                        ['label' => 'iOS', 'value' => '68%', 'width' => '68%'],
                        ['label' => 'Retention', 'value' => '57%', 'width' => '57%'],
                    ],
                ],
                [
                    'title' => 'نسخ التطبيق',
                    'kind' => 'list',
                    'items' => [
                        ['title' => 'نسخة iOS 3.4.0', 'meta' => 'آخر تحديث', 'value' => 'مستقرة'],
                        ['title' => 'نسخة Android 3.4.0', 'meta' => 'آخر تحديث', 'value' => 'مستقرة'],
                        ['title' => 'Crash-free sessions', 'meta' => 'صحة التطبيق', 'value' => '99.2%'],
                    ],
                ],
            ]
        );
    }

    public function settings(): View
    {
        return $this->renderSection(
            'admin.settings',
            'الإعدادات',
            'إعدادات المنصة والدفع والشحن والمستخدمين والصلاحيات.',
            [
                ['label' => 'المستخدمون', 'value' => '24', 'change' => '+2'],
                ['label' => 'الأدوار', 'value' => '6', 'change' => 'مفعلة'],
                ['label' => 'إعدادات الدفع', 'value' => '4', 'change' => 'مربوطة'],
                ['label' => 'إعدادات الشحن', 'value' => '3', 'change' => 'مربوطة'],
            ],
            [
                [
                    'title' => 'وحدات الإعدادات',
                    'kind' => 'settings',
                    'span' => 'full',
                    'items' => [
                        ['title' => 'إعدادات المنصة العامة', 'description' => 'اسم المنصة، النطاق، الهوية، البريد والإشعارات.'],
                        ['title' => 'إعدادات الدفع', 'description' => 'بوابات الدفع والمفاتيح ورسوم المعالجة والاسترجاع.'],
                        ['title' => 'إعدادات الشحن', 'description' => 'شركات الشحن، الأسعار، أوقات التوصيل وقواعد المناطق.'],
                        ['title' => 'المستخدمون والصلاحيات', 'description' => 'الأدوار، الصلاحيات، الوصول وإدارة الفرق الداخلية.'],
                        ['title' => 'مفاتيح التكامل', 'description' => 'API keys و webhooks وربط الشركاء والتطبيقات.'],
                        ['title' => 'قواعد الإشعارات', 'description' => 'البريد، الرسائل، التنبيهات الفورية وإعدادات SLA.'],
                    ],
                ],
            ]
        );
    }

    public function siteContent(): View
    {
        return view('admin.site-content', array_merge($this->sharedData(), [
            'activeRoute' => 'admin.site-content',
            'pageTitle' => 'إدارة محتوى الموقع',
            'pageDescription' => 'تحكم بالنصوص، الصور، الأقسام والباقات المعروضة في الصفحة الرئيسية من مكان واحد.',
            'content' => SiteContent::get(),
            'availableImages' => [
                'منصة_متاجر.png',
                'ما يُميز متاجر.png',
                'الدفع.png',
                'الشحن.png',
                'خدمات الشُركاء.png',
                'تطبيق_متاجر.png',
            ],
            'toneOptions' => [
                'bg-sky-500',
                'bg-amber-300 text-slate-900',
                'bg-violet-500',
                'bg-rose-400',
                'bg-emerald-500',
                'bg-brand-600',
            ],
        ]));
    }

    public function updateSiteContent(Request $request): RedirectResponse
    {
        SiteContent::put($request->except(['_token']));

        return redirect()->route('admin.site-content')->with('status', 'تم حفظ محتوى الموقع وربط الصور بنجاح.');
    }

    private function renderSection(string $activeRoute, string $pageTitle, string $pageDescription, array $summaryCards, array $panels): View
    {
        return view('admin.section', array_merge($this->sharedData(), [
            'activeRoute' => $activeRoute,
            'pageTitle' => $pageTitle,
            'pageDescription' => $pageDescription,
            'summaryCards' => $summaryCards,
            'panels' => $panels,
        ]));
    }

    private function sharedData(): array
    {
        return [
            'stats' => [
                ['label' => 'إجمالي المتاجر', 'value' => '12,480', 'change' => '+18.4%', 'tone' => 'primary'],
                ['label' => 'المتاجر النشطة', 'value' => '10,932', 'change' => '+12.1%', 'tone' => 'success'],
                ['label' => 'المتاجر المتوقفة', 'value' => '274', 'change' => '-4.3%', 'tone' => 'danger'],
                ['label' => 'التجار الجدد', 'value' => '486', 'change' => '+21.8%', 'tone' => 'info'],
                ['label' => 'إجمالي الطلبات', 'value' => '248K', 'change' => '+16.7%', 'tone' => 'primary'],
                ['label' => 'الإيرادات الشهرية', 'value' => '3.84M ر.س', 'change' => '+9.2%', 'tone' => 'success'],
                ['label' => 'معدل النمو', 'value' => '24.6%', 'change' => '+3.1%', 'tone' => 'info'],
                ['label' => 'تذاكر الدعم المفتوحة', 'value' => '92', 'change' => '-11.5%', 'tone' => 'warning'],
            ],
            'stores' => [
                ['name' => 'متجر أطلس', 'owner' => 'سارة الحربي', 'status' => 'نشط', 'plan' => 'Enterprise', 'sales' => '418,200 ر.س', 'orders' => '2,418', 'created_at' => '15 يناير 2026'],
                ['name' => 'أبعاد بيوتي', 'owner' => 'هدى القحطاني', 'status' => 'معلق', 'plan' => 'Growth', 'sales' => '126,540 ر.س', 'orders' => '1,109', 'created_at' => '09 فبراير 2026'],
                ['name' => 'رواء هوم', 'owner' => 'محمد الشهري', 'status' => 'نشط', 'plan' => 'Starter', 'sales' => '88,200 ر.س', 'orders' => '704', 'created_at' => '21 ديسمبر 2025'],
                ['name' => 'شهد بوتيك', 'owner' => 'نوف العتيبي', 'status' => 'مراجعة', 'plan' => 'Growth', 'sales' => '213,900 ر.س', 'orders' => '1,680', 'created_at' => '03 مارس 2026'],
            ],
            'plans' => [
                ['name' => 'Starter', 'price' => '79 ر.س', 'subs' => '2,140', 'revenue' => '169K ر.س'],
                ['name' => 'Growth', 'price' => '299 ر.س', 'subs' => '5,320', 'revenue' => '1.59M ر.س'],
                ['name' => 'Enterprise', 'price' => '899 ر.س', 'subs' => '1,280', 'revenue' => '1.15M ر.س'],
            ],
            'payments' => [
                ['name' => 'مدى', 'success' => '98.2%', 'failed' => '1.8%', 'refunds' => '48 طلب'],
                ['name' => 'Apple Pay', 'success' => '97.4%', 'failed' => '2.6%', 'refunds' => '27 طلب'],
                ['name' => 'Visa', 'success' => '96.9%', 'failed' => '3.1%', 'refunds' => '34 طلب'],
                ['name' => 'Mastercard', 'success' => '96.3%', 'failed' => '3.7%', 'refunds' => '22 طلب'],
            ],
            'shippingPartners' => [
                ['name' => 'أرامكس', 'deliveries' => '12,840', 'delay' => '2.4%', 'score' => '4.8/5'],
                ['name' => 'سمسا', 'deliveries' => '9,412', 'delay' => '3.1%', 'score' => '4.5/5'],
                ['name' => 'البريد السعودي', 'deliveries' => '7,105', 'delay' => '4.2%', 'score' => '4.2/5'],
            ],
            'tickets' => [
                ['title' => 'تأخير مزامنة المخزون', 'type' => 'تقني', 'priority' => 'عالية', 'assignee' => 'فريق المنصة', 'status' => 'قيد المعالجة'],
                ['title' => 'فشل استرجاع مبلغ', 'type' => 'دفع', 'priority' => 'حرجة', 'assignee' => 'فريق المدفوعات', 'status' => 'تصعيد'],
                ['title' => 'خطأ في صفحة المنتج', 'type' => 'متجر', 'priority' => 'متوسطة', 'assignee' => 'فريق الدعم', 'status' => 'بانتظار التاجر'],
                ['title' => 'خلل في تكامل ERP', 'type' => 'تكاملات', 'priority' => 'عالية', 'assignee' => 'فريق الشركاء', 'status' => 'قيد التنفيذ'],
            ],
            'services' => [
                ['name' => 'التصميم', 'requests' => '86 طلب', 'sla' => '2.1 يوم'],
                ['name' => 'التسويق', 'requests' => '51 طلب', 'sla' => '1.7 يوم'],
                ['name' => 'التغليف', 'requests' => '34 طلب', 'sla' => '3.4 يوم'],
                ['name' => 'التصوير', 'requests' => '19 طلب', 'sla' => '4.0 يوم'],
            ],
        ];
    }
}