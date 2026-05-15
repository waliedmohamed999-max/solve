@extends('layouts.site')

@section('title', 'Solve | منصة إنشاء المتاجر الإلكترونية')

@section('content')
<div
    x-data="{
        testimonialPage: 0,
        billingCycle: 'monthly',
        testimonials: @js($testimonials),
        get testimonialPages() {
            const size = 3;
            const pages = [];
            for (let i = 0; i < this.testimonials.length; i += size) {
                pages.push(this.testimonials.slice(i, i + size));
            }
            return pages;
        }
    }"
>
    <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-8 lg:px-10">
        <div class="flex items-center gap-3">
            <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-24 w-auto max-w-[240px] object-contain lg:h-32 lg:max-w-[320px]">
        </div>
        <nav class="hidden items-center gap-8 text-lg text-slate-600 md:flex">
            <a href="#pricing" class="transition hover:text-brand-600">الباقات</a>
            @foreach ($navLinks as $link)
                <a href="{{ $link['href'] }}" class="transition hover:text-brand-600">{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('merchant.register') }}" class="rounded-full border border-brand-200 px-8 py-3 font-bold text-brand-600 transition hover:bg-brand-50">انضم كتاجر</a>
            <a href="{{ $hero['primary_href'] }}" class="rounded-full bg-brand-600 px-8 py-3 text-white shadow-soft transition hover:bg-brand-700">{{ $hero['primary_label'] }}</a>
        </nav>
    </header>

    <section class="mx-auto grid max-w-7xl gap-10 px-6 pb-24 pt-10 lg:grid-cols-2 lg:px-10 lg:pb-32 lg:pt-16 lg:[direction:ltr]">
        <div class="order-1 flex items-center justify-center lg:order-1">
            <img src="{{ asset($hero['image']) }}" alt="{{ $hero['title'] }}" class="w-full max-w-2xl drop-shadow-[0_35px_60px_rgba(91,95,202,0.16)]">
        </div>
        <div class="order-2 flex flex-col items-start justify-center text-right lg:order-2 lg:pr-8 lg:[direction:rtl]">
            <h1 class="text-4xl font-extrabold leading-[1.8] text-brand-600 lg:text-6xl">{{ $hero['title'] }}</h1>
            <p class="mt-6 max-w-2xl text-2xl leading-[2.1] text-slate-600 lg:text-3xl">{{ $hero['description'] }}</p>
            <div class="mt-10 flex flex-wrap gap-4">
                <a href="{{ $hero['primary_href'] }}" class="rounded-full bg-brand-600 px-12 py-4 text-xl font-bold text-white shadow-soft hover:bg-brand-700">{{ $hero['primary_label'] }}</a>
                <a href="{{ route('merchant.register') }}" class="rounded-full bg-slate-950 px-12 py-4 text-xl font-bold text-white shadow-soft hover:bg-slate-800">ابدأ مجاناً</a>
                <a href="{{ route('merchant.register') }}" class="rounded-full border border-slate-300 px-10 py-4 text-xl font-bold text-slate-700">جرّب لوحة التحكم</a>
                <a href="{{ $hero['secondary_href'] }}" class="rounded-full border border-brand-200 px-10 py-4 text-xl font-bold text-brand-600">{{ $hero['secondary_label'] }}</a>
            </div>
        </div>
    </section>

    @php
        $solvePublicPlans = [
            [
                'key' => 'free',
                'name' => 'البداية',
                'icon' => '♧',
                'monthly' => 'مجانا',
                'yearly' => 'مجانا',
                'suffix' => 'مدى الحياة',
                'description' => 'أول خطوة تنطلق فيها بتجارتك',
                'cta' => 'ابدأ مجانا',
                'href' => route('merchant.register'),
                'featured' => false,
                'dark' => false,
                'badge' => '',
                'features' => [
                    'إضافة منتجاتك في سوق مزيد لمدة 5 مليون عميل',
                    'عدد غير محدود من المنتجات والطلبات والعملاء',
                    'إضافة كوبونات خصم',
                    'استقبال أسئلة وتقييمات العملاء',
                    'طرق دفع أساسية: تحويل بنكي، دفع عند الاستلام',
                    'الربط مع شركات الشحن عبر خدمة اللوجستيات',
                ],
            ],
            [
                'key' => 'launch',
                'name' => 'الانطلاقة',
                'icon' => '▣',
                'monthly' => '99',
                'yearly' => '83',
                'old_monthly' => '',
                'old_yearly' => '99',
                'suffix' => 'ريال شهريا',
                'description' => 'لكل من تجاوز البداية، وجاهز يصنع علامته',
                'cta' => 'ابدأ الآن',
                'href' => route('merchant.register'),
                'featured' => false,
                'dark' => false,
                'badge' => '9 ريال لأول شهر',
                'features' => [
                    'كل خصائص باقة البداية بالإضافة إلى:',
                    'استرداد نقدي 50% على الباقة السنوية فقط',
                    'واتساب مجاني',
                    'بوابة زر دفع لمدى، فيزا وماستركارد',
                    'تفعيل إلى باي لزيادة سرعة الدفع',
                    'الوصول لمتجر التجار لأكثر من 50 ثيم احترافي',
                    'ربط دومين خاص لتعزيز ثقة عملائك',
                    'الشحن يبدأ من 11 ريال للشحنة',
                    'واتساب مجاني على الباقة الأساسية',
                    'زر كاشير POS مجانا على الباقة الأساسية',
                ],
            ],
            [
                'key' => 'growth',
                'name' => 'النمو',
                'icon' => '▦',
                'monthly' => '299',
                'yearly' => '249',
                'old_monthly' => '',
                'old_yearly' => '299',
                'suffix' => 'ريال شهريا',
                'description' => 'للي جاهز يتوسع، ويحتاج متجر متكامل',
                'cta' => 'ابدأ الآن',
                'href' => route('merchant.register'),
                'featured' => true,
                'dark' => false,
                'badge' => 'الأكثر طلبا',
                'features' => [
                    'كل خصائص باقة الانطلاقة بالإضافة إلى:',
                    'استرداد نقدي 50% على الباقة السنوية فقط',
                    'واتساب مجاني على الباقة الأساسية',
                    'سنة مجانا على زر كاشير للباقة الأساسية',
                    'احتساب وعرض ضريبة القيمة المضافة VAT',
                    'صلاحية إدارة المتجر لـ 5 أشخاص إضافيين',
                    'إدارة المخزون عبر فرعين أو مستودعين',
                    'تقسيم العملاء لعروض حصرية',
                    'حماية من العمليات الاحتيالية لتجنب الخسائر',
                    'تخصيص الواجهة CSS',
                    'الشحن يبدأ من 10.5 ريال للشحنة',
                ],
            ],
            [
                'key' => 'enterprise',
                'name' => 'الإحترافية',
                'icon' => '▥',
                'monthly' => 'قيمة مخصصة',
                'yearly' => 'قيمة مخصصة',
                'suffix' => 'تدفع سنويا',
                'description' => 'حل متكامل للمنشآت الكبيرة',
                'cta' => 'تواصل مع فريقنا الآن',
                'href' => '#footer',
                'featured' => false,
                'dark' => true,
                'badge' => '',
                'features' => [
                    'أولوية الدعم 24/7',
                    'مدير علاقات تجار مخصص لدعم نموك وتحسين أدائك',
                    'استرداد نقدي 50% للباقة السنوية فقط',
                    'الشحن يبدأ من 8.5 ريال للشحنة',
                    'واتساب مجاني بالكامل',
                    'زر كاشير POS مجاني بالكامل - كاشيرين',
                    'صلاحية إدارة المتجر لـ 20 شخص إضافي',
                    'تقارير الربحية والتكاليف لتحسين هوامش الربح',
                    'إدارة المخزون عبر 5 فروع أو مستودعات',
                    'ربط متجرك بأنظمة خارجية عبر API',
                    'جر ونقل وعرض المخزون حسب مدينة العميل',
                ],
            ],
        ];

        $comparisonRows = [
            'الدفع' => ['طرق دفع أساسية', 'بوابات دفع حقيقية', 'Apple Pay / مدى', 'استرداد ومدفوعات متقدمة'],
            'إدارة المنتجات والمخزون' => ['منتجات وطلبات غير محدودة', 'ربط الشحن والطلبات', 'مخزون فروع ومستودعات', 'إدارة مخزون متعددة المدن'],
            'إدارة الطلبات' => ['طلبات وفواتير أساسية', 'شحن وتتبع', 'مرتجعات وتحديثات', 'تشغيل متقدم وأولوية دعم'],
            'أدوات التسويق والنمو' => ['كوبونات وتقييمات', 'دومين وثيمات', 'شرائح عملاء وعروض', 'مدير نجاح ونمو مخصص'],
            'التقارير والتحليلات' => ['تقارير أساسية', 'تقارير مبيعات', 'تحليلات وربحية', 'تقارير تشغيل وربحية متقدمة'],
            'تصميم المتجر' => ['واجهة أساسية', 'ثيمات جاهزة', 'تخصيص CSS', 'تجربة مخصصة بالكامل'],
        ];
    @endphp

    <section id="pricing" class="relative overflow-hidden bg-[#260033] px-6 py-20 text-white lg:px-10">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-36 bg-[radial-gradient(circle_at_50%_0%,rgba(178,101,255,.36),transparent_42%)]"></div>
        <div class="mx-auto max-w-7xl">
            <div class="mb-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="text-right">
                    <span class="inline-flex rounded-full bg-white/10 px-4 py-2 text-sm font-black text-violet-100">باقات Solve الرسمية</span>
                    <h2 class="mt-4 text-4xl font-black leading-tight lg:text-6xl">اختر الباقة المناسبة لنمو متجرك</h2>
                    <p class="mt-4 max-w-2xl text-lg font-bold leading-8 text-violet-100/80">هذه هي الباقات والأسعار المعتمدة في Solve، وتظهر قبل خدمات ومنتجات Solve حتى يبدأ التاجر بالاشتراك المناسب مباشرة.</p>
                </div>
                <div class="flex items-center gap-3 self-start rounded-full border border-white/10 bg-white/10 p-2 text-sm font-black shadow-2xl shadow-black/20 lg:self-auto">
                    <button type="button" @click="billingCycle='monthly'" class="rounded-full px-5 py-2 transition" :class="billingCycle === 'monthly' ? 'bg-white text-[#260033]' : 'text-white/80'">شهري</button>
                    <button type="button" @click="billingCycle='yearly'" class="rounded-full px-5 py-2 transition" :class="billingCycle === 'yearly' ? 'bg-white text-[#260033]' : 'text-white/80'">سنوي</button>
                    <span class="rounded-full bg-emerald-300 px-3 py-2 text-xs font-black text-emerald-950">كاش باك %50</span>
                </div>
            </div>

            <div class="grid gap-5 lg:grid-cols-4">
                @foreach ($solvePublicPlans as $plan)
                    <article class="relative overflow-hidden rounded-[26px] border p-6 shadow-[0_22px_70px_rgba(0,0,0,.22)] {{ $plan['dark'] ? 'border-white/10 bg-[#4a2466] text-white' : 'border-slate-200 bg-white text-[#21002f]' }} {{ $plan['featured'] ? 'ring-2 ring-violet-200' : '' }}">
                        @if ($plan['dark'])
                            <div class="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-[55px] bg-violet-400/35 blur-sm"></div>
                            <div class="pointer-events-none absolute right-0 top-0 text-[170px] font-black leading-none text-white/8">∞</div>
                        @endif
                        <div class="relative flex items-start justify-between gap-4">
                            <div class="grid h-14 w-14 place-items-center rounded-2xl {{ $plan['dark'] ? 'bg-white/10 text-white' : 'bg-violet-50 text-[#4b2565]' }} text-3xl">{{ $plan['icon'] }}</div>
                            @if ($plan['badge'])
                                <span class="rounded-full {{ $plan['featured'] ? 'bg-violet-100 text-violet-700' : 'bg-violet-50 text-violet-600' }} px-3 py-1 text-xs font-black">{{ $plan['badge'] }}</span>
                            @endif
                        </div>
                        <div class="relative mt-8 text-right">
                            <h3 class="text-3xl font-black">{{ $plan['name'] }}</h3>
                            <div class="mt-6 min-h-[76px]">
                                @if ($plan['key'] === 'enterprise' || $plan['key'] === 'free')
                                    <strong class="block text-4xl font-black">{{ $plan['monthly'] }}</strong>
                                    <span class="mt-1 block text-sm font-bold {{ $plan['dark'] ? 'text-white/80' : 'text-slate-500' }}">{{ $plan['suffix'] }}</span>
                                @else
                                    <div x-show="billingCycle === 'monthly'">
                                        <strong class="text-4xl font-black">{{ $plan['monthly'] }}</strong>
                                        @if (! empty($plan['old_monthly']))
                                            <span class="mr-2 text-2xl font-black text-slate-400 line-through">{{ $plan['old_monthly'] }}</span>
                                        @endif
                                        <span class="mr-2 text-xs font-bold">ريال شهريا</span>
                                    </div>
                                    <div x-show="billingCycle === 'yearly'" x-cloak>
                                        <strong class="text-4xl font-black">{{ $plan['yearly'] }}</strong>
                                        @if (! empty($plan['old_yearly']))
                                            <span class="mr-2 text-2xl font-black text-slate-400 line-through">{{ $plan['old_yearly'] }}</span>
                                        @endif
                                        <span class="mr-2 text-xs font-bold">ريال شهريا</span>
                                    </div>
                                @endif
                            </div>
                            <p class="mt-4 min-h-[54px] text-sm font-bold leading-7 {{ $plan['dark'] ? 'text-white/90' : 'text-slate-600' }}">{{ $plan['description'] }}</p>
                            <a href="{{ $plan['href'] }}" class="mt-7 flex w-full items-center justify-center rounded-full px-5 py-3 text-sm font-black transition {{ $plan['dark'] ? 'bg-violet-400 text-white hover:bg-violet-300' : 'bg-[#3b1850] text-white hover:bg-[#2b103c]' }}">
                                {{ $plan['cta'] }}
                            </a>
                        </div>
                        <div class="relative my-6 h-px {{ $plan['dark'] ? 'bg-white/15' : 'bg-slate-200' }}"></div>
                        <div class="relative text-right">
                            <p class="mb-4 text-sm font-black {{ $plan['dark'] ? 'text-white' : 'text-slate-500' }}">أبرز الخصائص</p>
                            <ul class="space-y-3 text-sm font-bold leading-7 {{ $plan['dark'] ? 'text-white/95' : 'text-slate-700' }}">
                                @foreach ($plan['features'] as $feature)
                                    <li class="flex items-start gap-2">
                                        <span class="mt-1 text-violet-300">✓</span>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-12 rounded-[28px] border border-white/10 bg-white p-5 text-[#260033] shadow-[0_26px_80px_rgba(0,0,0,.24)]">
                <div class="flex flex-col gap-3 border-b border-slate-100 pb-5 text-right lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h3 class="text-3xl font-black">قارن بين الباقات</h3>
                        <p class="mt-2 text-sm font-bold text-slate-500">مقارنة مختصرة لأهم المزايا، مع تفاصيل قابلة للتوسع حسب احتياج التاجر.</p>
                    </div>
                    <a href="{{ route('merchant.register') }}" class="rounded-full bg-[#3b1850] px-6 py-3 text-sm font-black text-white">جرّب الباقة المناسبة</a>
                </div>
                <div class="mt-5 overflow-x-auto">
                    <table class="w-full min-w-[860px] border-separate border-spacing-0 text-right text-sm">
                        <thead>
                            <tr class="text-slate-500">
                                <th class="rounded-r-2xl bg-slate-50 px-4 py-4 font-black">المجال</th>
                                <th class="bg-slate-50 px-4 py-4 font-black">البداية</th>
                                <th class="bg-slate-50 px-4 py-4 font-black">الانطلاقة</th>
                                <th class="bg-slate-50 px-4 py-4 font-black">النمو</th>
                                <th class="rounded-l-2xl bg-slate-50 px-4 py-4 font-black">الإحترافية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($comparisonRows as $group => $values)
                                <tr>
                                    <td class="border-b border-slate-100 px-4 py-4 font-black">{{ $group }}</td>
                                    @foreach ($values as $value)
                                        <td class="border-b border-slate-100 px-4 py-4 font-bold text-slate-600">
                                            <span class="ml-2 inline-grid h-5 w-5 place-items-center rounded-full bg-violet-100 text-xs text-violet-700">✓</span>{{ $value }}
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 space-y-3" x-data="{ openPlanFaq: 'payment' }">
                    @foreach (['payment' => 'الدفع', 'inventory' => 'إدارة المنتجات والمخزون', 'orders' => 'إدارة الطلبات', 'growth' => 'أدوات التسويق والنمو', 'reports' => 'التقارير والتحليلات', 'design' => 'تصميم المتجر', 'customers' => 'إدارة العملاء', 'other' => 'أخرى'] as $key => $label)
                        <div class="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50">
                            <button type="button" class="flex w-full items-center justify-between px-5 py-4 text-right text-base font-black" @click="openPlanFaq = openPlanFaq === '{{ $key }}' ? '' : '{{ $key }}'">
                                <span>{{ $label }}</span>
                                <span class="text-xl text-slate-400" x-text="openPlanFaq === '{{ $key }}' ? '−' : '+'"></span>
                            </button>
                            <div x-show="openPlanFaq === '{{ $key }}'" x-transition class="border-t border-slate-100 bg-white px-5 py-4 text-sm font-bold leading-8 text-slate-600">
                                يتم تفعيل مزايا {{ $label }} حسب الباقة المختارة، وتظهر الحدود والصلاحيات داخل لوحة التاجر مباشرة.
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @php
        $serviceVisuals = [
            ['image' => 'services/store-profile.svg', 'description' => 'صياغة تعريف واضح يعكس هوية المتجر ويقنع العميل من أول زيارة.'],
            ['image' => 'services/category-optimization.svg', 'description' => 'ترتيب التصنيفات والمنتجات بطريقة تسهّل البحث وتزيد فرص الشراء.'],
            ['image' => 'services/interface-design.svg', 'description' => 'تصميم واجهة متناسقة وسريعة القراءة تناسب تجربة المتجر على كل الأجهزة.'],
            ['image' => 'services/store-structure.svg', 'description' => 'بناء هيكلة صفحات ومسارات واضحة تساعد العميل ومحركات البحث.'],
            ['image' => 'services/sitemap.svg', 'description' => 'تجهيز خريطة Sitemap وربطها لضمان فهرسة صفحات المتجر بدقة.'],
            ['image' => 'services/analytics.svg', 'description' => 'إعداد Google Analytics لمتابعة الزيارات والسلوك ومصادر التحويل.'],
            ['image' => 'services/domain.svg', 'description' => 'حجز وربط الدومين مع ضبط الإعدادات الأساسية لتشغيل المتجر.'],
            ['image' => 'services/technical-seo.svg', 'description' => 'تهيئة تقنية لتحسين سرعة الفهرسة وجودة ظهور صفحات المتجر.'],
        ];
    @endphp

    <section id="services" class="px-6 py-20 text-slate-700 lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold text-slate-800 lg:text-5xl">{{ $servicesHeading }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-500">{{ $servicesSubheading }}</p>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($serviceCards as $card)
                    @php
                        $visual = $serviceVisuals[$loop->index] ?? $serviceVisuals[0];
                    @endphp
                    <article class="group overflow-hidden rounded-[28px] border border-brand-100 bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-[0_24px_60px_rgba(91,95,202,0.16)]">
                        <div class="relative aspect-[16/10] overflow-hidden bg-slate-50">
                            <img src="{{ asset($visual['image']) }}" alt="{{ $card['title'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]">
                            <div class="absolute right-4 top-4 rounded-full border border-white/70 bg-white/80 px-3 py-1 text-xs font-extrabold text-brand-700 shadow-sm backdrop-blur-sm">
                                {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </div>
                        </div>
                        <div class="p-6 text-right">
                            <h3 class="text-2xl font-extrabold leading-[1.5] text-slate-900">{{ $card['title'] }}</h3>
                            <p class="mt-3 min-h-[84px] text-base leading-8 text-slate-500">{{ $visual['description'] }}</p>
                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-extrabold text-brand-600">
                                تفاصيل الخدمة
                                <span class="transition group-hover:-translate-x-1">←</span>
                            </span>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="px-6 py-16 lg:px-10">
        <div class="mx-auto max-w-7xl text-center">
            <h2 class="text-5xl font-medium text-slate-700">{{ $featuresHeading }}</h2>
            <div class="mx-auto mt-5 h-1 w-24 rounded-full bg-brand-500"></div>
        </div>
        <div class="mx-auto mt-20 max-w-7xl space-y-24 lg:space-y-32">
            @foreach ($featureSections as $section)
                <div class="grid items-center gap-12 lg:grid-cols-[1.05fr_1fr] lg:[direction:ltr]">
                    <div class="relative flex justify-center section-glow">
                        <img src="{{ asset($section['image']) }}" alt="{{ $section['title'] }}" class="relative z-10 w-full max-w-2xl">
                    </div>
                    <div class="text-center lg:text-right lg:[direction:rtl]">
                        <h3 class="text-5xl font-medium text-brand-600">{{ $section['title'] }}</h3>
                        <p class="mx-auto mt-6 max-w-2xl text-2xl leading-[2] text-slate-600 lg:mx-0">{{ $section['description'] }}</p>
                        <a href="#catalog" class="mt-6 inline-flex items-center gap-2 text-2xl text-brand-500 hover:text-brand-700">{{ $section['link'] }} <span>‹</span></a>
                    </div>
                </div>
            @endforeach
        </div>
    </section>


    @php
        $partnerLogos = array_values(array_filter($showcaseStores, fn ($store) => trim((string) ($store['name'] ?? '')) !== ''));
    @endphp

    <section class="overflow-hidden px-6 py-16 lg:px-10">
        <div class="text-center">
            <h2 class="text-4xl font-extrabold text-slate-800 lg:text-5xl">{{ $showcaseHeading }}</h2>
            <div class="mx-auto mt-5 h-1 w-24 rounded-full bg-brand-500"></div>
            <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-500">شركاء ومتاجر موثوقون، بلوجوهات حقيقية قابلة للتحديث من لوحة التحكم.</p>
        </div>

        <div class="relative mx-auto mt-12 max-w-[1500px]">
            <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-20 bg-gradient-to-r from-[#fbfbfe] to-transparent"></div>
            <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-20 bg-gradient-to-l from-[#fbfbfe] to-transparent"></div>

            <div class="showcase-marquee flex w-max gap-4 py-4">
                @foreach (array_merge($partnerLogos, $partnerLogos) as $store)
                    <a href="{{ $store['url'] ?? '#' }}" class="group flex w-[180px] shrink-0 flex-col items-center rounded-[24px] border border-brand-100 bg-white px-5 py-5 text-center shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-[0_18px_42px_rgba(91,95,202,0.14)]">
                        <span class="flex h-20 w-full items-center justify-center overflow-hidden rounded-[18px] border border-slate-100 bg-slate-50">
                            @if (! empty($store['image']))
                                <img src="{{ asset($store['image']) }}" alt="{{ $store['name'] }}" class="max-h-14 max-w-[128px] object-contain">
                            @else
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl {{ $store['tone'] ?? 'bg-brand-600' }} text-base font-extrabold text-white">{{ $store['badge'] ?? '' }}</span>
                            @endif
                        </span>
                        <strong class="mt-4 line-clamp-1 text-base font-extrabold text-slate-800">{{ $store['name'] }}</strong>
                        <span class="mt-2 rounded-full bg-brand-50 px-3 py-1 text-xs font-bold text-brand-700">{{ $store['category'] ?? 'Partner' }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
            <span class="rounded-full bg-brand-600 px-5 py-2 text-sm font-extrabold text-white shadow-soft">+{{ count($partnerLogos) }} شريك</span>
            <span class="rounded-full border border-brand-200 bg-white px-5 py-2 text-sm font-bold text-brand-700">لوجوهات قابلة للتعديل</span>
            <span class="rounded-full border border-brand-200 bg-white px-5 py-2 text-sm font-bold text-brand-700">رفع صور من الداشبورد</span>
        </div>
    </section>

    <section id="catalog" class="bg-[#fbfbfe] px-6 py-20 text-slate-700 lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold text-slate-800 lg:text-5xl">{{ $catalogHeading }}</h2>
                <p class="mx-auto mt-4 max-w-3xl text-lg leading-8 text-slate-300">حلول جاهزة للتشغيل والنمو، مصممة بلغة بصرية أقرب لهوية Solve وتفتح صفحة منتج كاملة عند الضغط.</p>
            </div>
            <div class="mt-16 space-y-16">
                @foreach ($catalogSections as $section)
                    <div>
                        <div class="mb-6 flex items-center justify-between">
                            <h3 class="text-3xl font-extrabold text-brand-700">{{ $section['title'] }}</h3>
                            <a href="#footer" class="text-sm font-bold text-slate-400 hover:text-white">اطلب خدمة مخصصة</a>
                        </div>
                        <div class="grid gap-5 lg:grid-cols-3 xl:grid-cols-5">
                            @foreach ($section['items'] as $product)
                                <a href="{{ route('site.products.show', $product['slug']) }}" class="group rounded-[28px] border border-brand-200 bg-white p-4 shadow-card transition duration-300 hover:-translate-y-1 hover:border-brand-300 hover:shadow-[0_20px_50px_rgba(91,95,202,0.14)]">
                                    <div class="relative overflow-hidden rounded-[24px] bg-slate-100 p-4">
                                        @if ($product['badge'])
                                            <span class="absolute left-3 top-3 rounded-full bg-brand-500 px-3 py-1 text-xs font-bold text-white">{{ $product['badge'] }}</span>
                                        @endif
                                        <div class="rounded-[22px] bg-gradient-to-br {{ $product['accent'] }} p-[1px]">
                                            <div class="flex aspect-[4/5] flex-col justify-between rounded-[21px] bg-[#181a32] p-5">
                                                <div class="flex items-center justify-between text-xs text-slate-300">
                                                    <span class="rounded-full bg-white/10 px-3 py-1">Solve</span>
                                                    <span class="text-lg font-extrabold text-white">{{ $product['code'] }}</span>
                                                </div>
                                                <div class="space-y-3 text-right">
                                                    <h4 class="text-xl font-extrabold leading-8 text-white">{{ $product['title'] }}</h4>
                                                    <p class="text-sm leading-7 text-slate-300">{{ $product['subtitle'] }}</p>
                                                </div>
                                                <div class="grid grid-cols-3 gap-2 opacity-80">
                                                    <span class="h-2 rounded-full bg-fuchsia-400"></span>
                                                    <span class="h-2 rounded-full bg-sky-400"></span>
                                                    <span class="h-2 rounded-full bg-brand-400"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="px-2 pb-2 pt-5 text-right">
                                        <h4 class="text-xl font-extrabold leading-8 text-slate-800 transition group-hover:text-brand-600">{{ $product['title'] }}</h4>
                                        <p class="mt-2 min-h-[56px] text-sm leading-7 text-slate-500">{{ $product['description'] }}</p>
                                        <div class="mt-4 flex items-end justify-between gap-3">
                                            <span class="text-sm font-bold text-brand-200">عرض التفاصيل</span>
                                            <div class="text-left">
                                                @if ($product['old_price'])
                                                    <p class="text-sm text-slate-500 line-through">{{ $product['old_price'] }}</p>
                                                @endif
                                                <p class="text-2xl font-extrabold text-slate-800">{{ $product['price'] }}</p>
                                                @if ($product['price_note'])
                                                    <p class="text-xs text-slate-500">{{ $product['price_note'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#fbfbfe] px-6 py-20 text-slate-700 lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-brand-200">
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-300/40 bg-white/5 text-2xl" @click="testimonialPage = testimonialPage === 0 ? testimonialPages.length - 1 : testimonialPage - 1">‹</button>
                    <button type="button" class="flex h-12 w-12 items-center justify-center rounded-full border border-brand-300/40 bg-white/5 text-2xl" @click="testimonialPage = testimonialPage === testimonialPages.length - 1 ? 0 : testimonialPage + 1">›</button>
                </div>
                <div class="text-right">
                    <h2 class="text-4xl font-extrabold text-brand-200">{{ $testimonialsHeading }}</h2>
                    <p class="mt-3 text-lg text-slate-400">{{ $testimonialsSubheading }}</p>
                </div>
            </div>
            <template x-for="(page, pageIndex) in testimonialPages" :key="pageIndex">
                <div x-show="testimonialPage === pageIndex" x-transition class="mt-12">
                    <div class="grid gap-6 lg:grid-cols-3">
                        <template x-for="item in page" :key="item.name">
                            <div class="rounded-[30px] bg-[#1c1d33] p-8 text-center shadow-[0_20px_60px_rgba(0,0,0,0.3)]">
                                <div class="text-right text-6xl text-white/10">“</div>
                                <p class="-mt-4 text-sm font-bold uppercase tracking-[0.18em] text-brand-200" x-text="item.brand"></p>
                                <h3 class="mt-6 text-3xl font-extrabold" x-text="item.name"></h3>
                                <p class="mt-3 text-2xl text-amber-300">★★★★★</p>
                                <p class="mt-6 text-xl leading-[2] text-slate-200" x-text="item.quote"></p>
                            </div>
                        </template>
                    </div>
                    <div class="mt-8 flex justify-center gap-3">
                        <template x-for="(dot, dotIndex) in testimonialPages" :key="dotIndex">
                            <button
                                type="button"
                                class="h-1.5 w-16 rounded-full transition"
                                :class="testimonialPage === dotIndex ? 'bg-brand-300' : 'bg-brand-900'"
                                @click="testimonialPage = dotIndex"
                                :aria-label="`??? ???? ????????? ${dotIndex + 1}`"
                            ></button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </section>

    <section class="bg-[#fbfbfe] px-6 py-20 text-slate-700 lg:px-10">
        <div class="mx-auto max-w-7xl">
            <div class="text-center">
                <h2 class="text-4xl font-extrabold text-brand-200 lg:text-5xl">{{ $faqHeading }}</h2>
                <p class="mt-4 text-lg text-slate-400">{{ $faqSubheading }}</p>
            </div>
            <div class="mt-12 grid gap-6 lg:grid-cols-2">
                @foreach ($faqs as $faq)
                    <div x-data="{ open: true }" class="rounded-[28px] border border-brand-300/50 bg-[#1c1d33] p-6 shadow-[0_20px_60px_rgba(0,0,0,0.28)]">
                        <button type="button" class="flex w-full items-start justify-between gap-4 text-right" @click="open = !open">
                            <div>
                                <h3 class="text-2xl font-extrabold text-brand-100">{{ $faq['question'] }}</h3>
                            </div>
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-brand-300/20 text-3xl text-brand-100" x-text="open ? '−' : '+'"></span>
                        </button>
                        <p x-show="open" x-transition class="mt-6 text-xl leading-[2] text-slate-200">{{ $faq['answer'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-[#fbfbfe] px-6 py-20 text-slate-700 lg:px-10">
        <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-2 lg:[direction:ltr]">
        <div class="text-center lg:text-right lg:[direction:rtl]">
            <h2 class="text-5xl font-medium text-brand-600">{{ $appSection['title'] }}</h2>
            <p class="mt-6 text-2xl leading-[2] text-slate-600">{{ $appSection['description'] }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-4 lg:justify-start">
                <span class="rounded-xl bg-brand-900 px-6 py-3 text-xl text-white">{{ $appSection['google_label'] }}</span>
                <span class="rounded-xl bg-brand-900 px-6 py-3 text-xl text-white">{{ $appSection['appstore_label'] }}</span>
            </div>
        </div>
        <div class="flex justify-center">
            <img src="{{ asset($appSection['image']) }}" alt="{{ $appSection['title'] }}" class="w-full max-w-xl">
        </div>
        </div>
    </section>

    <section class="mt-10 bg-brand-500 px-6 py-20 text-center text-white lg:px-10">
        <h2 class="text-4xl font-bold lg:text-6xl">{{ $ctaSection['title'] }}</h2>
        <a href="{{ $ctaSection['button_href'] }}" class="mx-auto mt-10 inline-flex rounded-full bg-white px-12 py-4 text-2xl font-bold text-brand-600">{{ $ctaSection['button_label'] }}</a>
    </section>

    <footer id="footer" class="bg-[#fbfbfe] px-6 pb-8 pt-16 text-slate-700 lg:px-10">
        <div class="mx-auto grid max-w-7xl gap-12 lg:grid-cols-[1.2fr_1fr_1fr]">
            <div class="text-center lg:text-right">
                <h3 class="text-3xl font-medium text-slate-700">{{ $footer['about_title'] }}</h3>
                <div class="mt-8 space-y-4 text-xl text-brand-500">
                    @foreach ($footer['about_links'] as $item)
                        <p>{{ $item }}</p>
                    @endforeach
                </div>
            </div>
            <div class="text-center">
                <h3 class="text-3xl font-medium text-slate-700">{{ $footer['links_title'] }}</h3>
                <div class="mt-8 space-y-4 text-xl text-brand-500">
                    @foreach ($footer['links'] as $item)
                        <p>{{ $item }}</p>
                    @endforeach
                </div>
            </div>
            <div class="text-center lg:text-left">
                <div class="flex items-center justify-center gap-3 lg:justify-start">
                    <img src="{{ asset('solve-logo.png') }}" alt="Solve Logo" class="h-14 w-auto object-contain">
                    <div>
                        <h3 class="text-3xl font-medium text-slate-700">{{ $footer['contact_title'] }}</h3>
                        <p class="mt-2 text-lg text-slate-500">{{ $footer['contact_description'] }}</p>
                    </div>
                </div>
                <div class="mt-8 flex justify-center gap-4 lg:justify-start">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl text-slate-600">▶</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl text-slate-600">f</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl text-slate-600">in</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl text-slate-600">◎</span>
                    <span class="flex h-12 w-12 items-center justify-center rounded-full border border-slate-300 text-xl text-slate-600">𝕏</span>
                </div>
                <div class="mt-10 space-y-2 text-lg text-slate-500">
                    <p>السجل التجاري: {{ $footer['commercial_register'] }}</p>
                    <p>الرقم الضريبي: {{ $footer['tax_number'] }}</p>
                </div>
            </div>
        </div>
        <div class="mt-14 border-t border-slate-100 pt-6 text-center text-lg text-slate-500">{{ $footer['copyright'] }}</div>
    </footer>
</div>
@endsection
