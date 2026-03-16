@extends('layouts.admin')

@section('title', 'Solve Admin Dashboard')

@section('content')
<div class="min-h-screen p-4 lg:p-6" x-data="{ quickOpen: false, mobileNav: false }">
    <div class="mx-auto flex max-w-[1800px] gap-4 lg:gap-6">
        <aside class="fixed inset-y-4 right-4 z-40 w-[300px] rounded-[32px] border border-white/60 bg-white/90 p-5 shadow-soft backdrop-blur-xl lg:static lg:block"
            :class="mobileNav ? 'block' : 'hidden lg:block'">
            <div class="flex items-center justify-between border-b border-slate-100 pb-5">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-xl font-extrabold text-white shadow-card">S</div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.3em] text-brand-500">Solve</p>
                            <h1 class="text-2xl font-extrabold text-slate-900">Admin Hub</h1>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-500">مركز إدارة عمليات المنصة، المتاجر، الاشتراكات، الدعم والتحليلات.</p>
                </div>
                <button class="rounded-2xl border border-slate-200 p-2 text-slate-500 lg:hidden" @click="mobileNav = false">✕</button>
            </div>

            <nav class="mt-6 space-y-2 text-sm font-bold">
                @php
                    $menu = ['الرئيسية', 'المتاجر', 'الاشتراكات', 'المدفوعات', 'الشحن', 'التحليلات', 'خدمات الشركاء', 'الدعم الفني', 'التطبيقات', 'الإعدادات'];
                    $icons = ['⌂', '▣', '◫', '◌', '◭', '◔', '✦', '◎', '◍', '⚙'];
                @endphp
                @foreach ($menu as $index => $item)
                    <a href="#section-{{ $index }}"
                        class="{{ $index === 0 ? 'bg-brand-600 text-white shadow-card' : 'text-slate-600 hover:bg-slate-50' }} flex items-center justify-between rounded-2xl px-4 py-3 transition">
                        <span>{{ $item }}</span>
                        <span class="{{ $index === 0 ? 'bg-white/20 text-white' : 'bg-brand-50 text-brand-600' }} flex h-10 w-10 items-center justify-center rounded-xl text-base">
                            {{ $icons[$index] }}
                        </span>
                    </a>
                @endforeach
            </nav>

            <div class="mt-6 rounded-[28px] bg-gradient-to-br from-brand-700 via-brand-600 to-sky-500 p-5 text-white shadow-soft">
                <p class="text-xs font-bold uppercase tracking-[0.35em] text-white/70">Solve Insight</p>
                <h2 class="mt-4 text-2xl font-extrabold leading-10">ركز على نمو التجار واترك لنا تعقيد التشغيل.</h2>
                <p class="mt-3 text-sm leading-7 text-white/85">واجهة إدارية مصممة لإدارة كميات كبيرة من البيانات دون ازدحام، مع رؤية واضحة للحالة التشغيلية.</p>
                <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl bg-white/15 p-3">
                        <p class="text-white/70">SLA الدعم</p>
                        <p class="mt-2 text-xl font-extrabold">94%</p>
                    </div>
                    <div class="rounded-2xl bg-white/15 p-3">
                        <p class="text-white/70">صحة المنصة</p>
                        <p class="mt-2 text-xl font-extrabold">99.98%</p>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <header class="grid-pattern sticky top-4 z-30 rounded-[32px] border border-white/60 bg-white/85 p-4 shadow-card backdrop-blur-xl">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center gap-3">
                        <button class="rounded-2xl border border-slate-200 p-3 text-slate-600 lg:hidden" @click="mobileNav = true">☰</button>
                        <div class="relative w-full max-w-xl">
                            <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">⌕</span>
                            <input type="text" placeholder="ابحث عن متجر، تذكرة، دفعة أو تقرير"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pr-11 pl-4 text-sm outline-none transition focus:border-brand-300 focus:bg-white">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600 shadow-sm">العربية | EN</button>
                        <button class="relative rounded-2xl border border-slate-200 bg-white p-3 text-slate-600 shadow-sm">
                            🔔
                            <span class="absolute left-2 top-2 h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                        </button>
                        <div class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-brand-600 text-sm font-extrabold text-white">SV</div>
                            <div>
                                <p class="text-sm font-extrabold text-slate-900">فريق الإدارة</p>
                                <p class="text-xs text-slate-500">admin@solve.sa</p>
                            </div>
                        </div>
                        <div class="relative" @click.outside="quickOpen = false">
                            <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-card" @click="quickOpen = !quickOpen">إجراءات سريعة +</button>
                            <div x-show="quickOpen" x-transition class="absolute left-0 top-16 w-72 rounded-3xl border border-slate-100 bg-white p-3 shadow-soft">
                                <a href="#" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">إضافة متجر جديد</a>
                                <a href="#" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">إنشاء كوبون باقة</a>
                                <a href="#" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">تعيين تذكرة لفريق الدعم</a>
                                <a href="#" class="block rounded-2xl px-4 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">ربط شريك شحن جديد</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <section id="section-0" class="mt-6">
                <div class="grid gap-6 xl:grid-cols-[1.6fr_1fr]">
                    <div class="overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-brand-900 to-brand-600 p-7 text-white shadow-soft">
                        <div class="flex flex-col gap-8 xl:flex-row xl:items-center xl:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-sm font-bold text-brand-100">Solve Platform Control Center</p>
                                <h2 class="mt-4 text-3xl font-extrabold leading-[1.6] lg:text-5xl">لوحة تحكم احترافية لإدارة المنصة، حل المشاكل، ودفع نمو المتاجر.</h2>
                                <p class="mt-4 max-w-xl text-sm leading-8 text-slate-200 lg:text-base">تصميم SaaS نظيف مستلهم من لوحات Shopify و Stripe، مع تدفق بصري واضح، أرقام بارزة، وواجهة عربية RTL جاهزة للتشغيل.</p>
                                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                                    <span class="rounded-full bg-white/10 px-4 py-2">12.4K متجر</span>
                                    <span class="rounded-full bg-white/10 px-4 py-2">248K طلب هذا الشهر</span>
                                    <span class="rounded-full bg-emerald-400/20 px-4 py-2 text-emerald-100">نمو +24.6%</span>
                                </div>
                            </div>
                            <div class="relative mx-auto w-full max-w-sm">
                                <div class="absolute inset-0 rounded-full bg-white/10 blur-3xl"></div>
                                <img src="{{ url('/منصة_متاجر.png') }}" alt="منصة متاجر" class="relative mx-auto w-full max-w-xs drop-shadow-2xl">
                            </div>
                        </div>
                    </div>

                    <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-bold text-brand-600">ملخص اليوم</p>
                                <h3 class="mt-2 text-2xl font-extrabold text-slate-900">صحة التشغيل العامة</h3>
                            </div>
                            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-extrabold text-emerald-600">ممتازة</div>
                        </div>
                        <div class="mt-6 space-y-4">
                            @foreach ([
                                ['label' => 'توفر المنصة', 'value' => '99.98%', 'width' => '99%', 'tone' => 'from-emerald-400 to-brand-500'],
                                ['label' => 'أداء المدفوعات', 'value' => '97.6%', 'width' => '97%', 'tone' => 'from-sky-400 to-brand-500'],
                                ['label' => 'الالتزام بزمن الدعم', 'value' => '94%', 'width' => '94%', 'tone' => 'from-amber-400 to-brand-500'],
                            ] as $health)
                                <div class="rounded-3xl bg-slate-50 p-4">
                                    <div class="flex items-center justify-between text-sm text-slate-500">
                                        <span>{{ $health['label'] }}</span>
                                        <span>{{ $health['value'] }}</span>
                                    </div>
                                    <div class="mt-3 h-2 rounded-full bg-slate-200">
                                        <div class="h-2 rounded-full bg-gradient-to-l {{ $health['tone'] }}" style="width: {{ $health['width'] }}"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                @foreach ($stats as $stat)
                    @php
                        $toneMap = [
                            'primary' => 'bg-brand-50 text-brand-600',
                            'success' => 'bg-emerald-50 text-emerald-600',
                            'danger' => 'bg-rose-50 text-rose-600',
                            'info' => 'bg-sky-50 text-sky-600',
                            'warning' => 'bg-amber-50 text-amber-600',
                        ];
                    @endphp
                    <div class="rounded-[28px] border border-white/70 bg-white p-5 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold text-slate-500">{{ $stat['label'] }}</p>
                                <p class="mt-4 text-3xl font-extrabold text-slate-900">{{ $stat['value'] }}</p>
                            </div>
                            <span class="rounded-2xl px-3 py-2 text-sm font-bold {{ $toneMap[$stat['tone']] }}">{{ $stat['change'] }}</span>
                        </div>
                        <div class="mt-5 h-2 rounded-full bg-slate-100">
                            <div class="h-2 rounded-full bg-gradient-to-l from-brand-400 to-sky-400" style="width: {{ 62 + ($loop->index * 4) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </section>

            <section class="mt-6 grid gap-6 2xl:grid-cols-[1.5fr_1fr]">
                <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">لوحة التحكم الرئيسية</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">نمو المتاجر والمبيعات والطلبات</h3>
                        </div>
                        <div class="rounded-2xl bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600">آخر 30 يوم</div>
                    </div>
                    <div class="mt-8 grid gap-4 lg:grid-cols-3">
                        <div class="rounded-[28px] bg-slate-950 p-5 text-white">
                            <p class="text-sm text-slate-300">نمو المتاجر</p>
                            <div class="mt-6 flex h-48 items-end gap-3">
                                @foreach ([28, 34, 36, 48, 57, 64, 72, 78] as $height)
                                    <div class="flex-1 rounded-t-2xl bg-gradient-to-t from-brand-500 to-sky-300" style="height: {{ $height }}%"></div>
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-[28px] bg-slate-50 p-5 lg:col-span-2">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-bold text-slate-500">المبيعات والطلبات</p>
                                <span class="text-sm font-bold text-emerald-600">+14.8%</span>
                            </div>
                            <div class="relative mt-6 h-48 overflow-hidden rounded-[24px] bg-white p-4">
                                <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(99,102,241,0.12),_transparent_40%)]"></div>
                                <svg viewBox="0 0 600 220" class="relative h-full w-full">
                                    <polyline fill="none" stroke="#94a3b8" stroke-width="4" points="0,180 80,165 160,170 240,140 320,132 400,110 480,94 560,76" />
                                    <polyline fill="none" stroke="#4f46e5" stroke-width="6" points="0,168 80,148 160,152 240,120 320,112 400,88 480,72 560,42" />
                                    <polyline fill="none" stroke="#06b6d4" stroke-width="6" points="0,186 80,180 160,172 240,154 320,140 400,132 480,110 560,94" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">أفضل المتاجر أداء</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">Top Stores</h3>
                        </div>
                        <span class="rounded-2xl bg-brand-50 px-3 py-2 text-sm font-bold text-brand-600">Q1 2026</span>
                    </div>
                    <div class="mt-6 space-y-4">
                        @foreach ([
                            ['name' => 'شهد بوتيك', 'revenue' => '684K ر.س', 'orders' => '4,120'],
                            ['name' => 'متجر أطلس', 'revenue' => '641K ر.س', 'orders' => '3,980'],
                            ['name' => 'Solar Free', 'revenue' => '530K ر.س', 'orders' => '2,906'],
                            ['name' => 'عطر الفنادق', 'revenue' => '410K ر.س', 'orders' => '2,104'],
                        ] as $topStore)
                            <div class="flex items-center justify-between rounded-3xl bg-slate-50 p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-lg font-extrabold text-white">
                                        {{ mb_substr($topStore['name'], 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-extrabold text-slate-900">{{ $topStore['name'] }}</p>
                                        <p class="text-sm text-slate-500">{{ $topStore['orders'] }} طلب</p>
                                    </div>
                                </div>
                                <p class="text-sm font-extrabold text-slate-900">{{ $topStore['revenue'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="section-1" class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                    <div>
                        <p class="text-sm font-bold text-brand-600">إدارة المتاجر</p>
                        <h3 class="mt-2 text-2xl font-extrabold text-slate-900">قاعدة بيانات المتاجر وإجراءات الإدارة</h3>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <input type="text" placeholder="ابحث عن متجر أو تاجر" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-300">
                        <button class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600">كل الحالات</button>
                        <button class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600">كل الباقات</button>
                        <button class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">إضافة متجر</button>
                    </div>
                </div>
                <div class="mt-6 overflow-x-auto scrollbar-hidden">
                    <table class="min-w-full text-right text-sm">
                        <thead>
                            <tr class="text-slate-400">
                                <th class="px-4 py-4 font-bold">اسم المتجر</th>
                                <th class="px-4 py-4 font-bold">اسم التاجر</th>
                                <th class="px-4 py-4 font-bold">الحالة</th>
                                <th class="px-4 py-4 font-bold">نوع الباقة</th>
                                <th class="px-4 py-4 font-bold">المبيعات</th>
                                <th class="px-4 py-4 font-bold">عدد الطلبات</th>
                                <th class="px-4 py-4 font-bold">تاريخ الإنشاء</th>
                                <th class="px-4 py-4 font-bold">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stores as $store)
                                <tr class="border-t border-slate-100">
                                    <td class="px-4 py-5 font-extrabold text-slate-900">{{ $store['name'] }}</td>
                                    <td class="px-4 py-5 text-slate-600">{{ $store['owner'] }}</td>
                                    <td class="px-4 py-5">
                                        @php
                                            $statusClass = match ($store['status']) {
                                                'نشط' => 'bg-emerald-50 text-emerald-600',
                                                'معلق' => 'bg-rose-50 text-rose-600',
                                                default => 'bg-amber-50 text-amber-600',
                                            };
                                        @endphp
                                        <span class="rounded-full px-3 py-2 text-xs font-bold {{ $statusClass }}">{{ $store['status'] }}</span>
                                    </td>
                                    <td class="px-4 py-5 text-slate-600">{{ $store['plan'] }}</td>
                                    <td class="px-4 py-5 text-slate-600">{{ $store['sales'] }}</td>
                                    <td class="px-4 py-5 text-slate-600">{{ $store['orders'] }}</td>
                                    <td class="px-4 py-5 text-slate-600">{{ $store['created_at'] }}</td>
                                    <td class="px-4 py-5">
                                        <div class="flex justify-end gap-2">
                                            <button class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-bold text-slate-700">تفاصيل</button>
                                            <button class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-600">تعديل</button>
                                            <button class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600">إيقاف</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-2" class="mt-6 grid gap-6 xl:grid-cols-2">
                <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">الاشتراكات والباقات</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">أداء الباقات والإيرادات</h3>
                        </div>
                        <span class="rounded-2xl bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-600">ترقيات +18%</span>
                    </div>
                    <div class="mt-6 space-y-4">
                        @foreach ($plans as $plan)
                            <div class="flex items-center justify-between rounded-[28px] border border-slate-100 p-5">
                                <div>
                                    <p class="text-xl font-extrabold text-slate-900">{{ $plan['name'] }}</p>
                                    <p class="mt-2 text-sm text-slate-500">{{ $plan['subs'] }} اشتراك نشط</p>
                                </div>
                                <div class="text-left">
                                    <p class="text-lg font-extrabold text-brand-600">{{ $plan['price'] }}</p>
                                    <p class="mt-2 text-sm text-slate-500">{{ $plan['revenue'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="section-3" class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">المدفوعات</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">بوابات الدفع وسجل الأداء</h3>
                        </div>
                        <img src="{{ url('/feature_3.png') }}" alt="وسائل الدفع" class="h-20 rounded-3xl object-cover">
                    </div>
                    <div class="mt-6 grid gap-4">
                        @foreach ($payments as $payment)
                            <div class="rounded-[28px] bg-slate-50 p-5">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-lg font-extrabold text-slate-900">{{ $payment['name'] }}</p>
                                        <p class="mt-2 text-sm text-slate-500">استرجاعات: {{ $payment['refunds'] }}</p>
                                    </div>
                                    <div class="flex gap-2 text-xs font-bold">
                                        <span class="rounded-full bg-emerald-50 px-3 py-2 text-emerald-600">ناجحة {{ $payment['success'] }}</span>
                                        <span class="rounded-full bg-rose-50 px-3 py-2 text-rose-600">فاشلة {{ $payment['failed'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="section-4" class="mt-6 grid gap-6 xl:grid-cols-[1.1fr_1fr]">
                <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">الشحن</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">شركات الشحن وإعدادات الأداء</h3>
                        </div>
                        <img src="{{ url('/feature_4.png') }}" alt="الشحن" class="h-20 rounded-3xl object-cover">
                    </div>
                    <div class="mt-6 space-y-4">
                        @foreach ($shippingPartners as $partner)
                            <div class="grid gap-3 rounded-[28px] border border-slate-100 p-5 md:grid-cols-4 md:items-center">
                                <div>
                                    <p class="text-lg font-extrabold text-slate-900">{{ $partner['name'] }}</p>
                                    <p class="mt-2 text-sm text-slate-500">شركة متصلة</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-400">الشحنات</p>
                                    <p class="mt-1 font-bold text-slate-800">{{ $partner['deliveries'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-400">التأخير</p>
                                    <p class="mt-1 font-bold text-amber-600">{{ $partner['delay'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-400">التقييم</p>
                                    <p class="mt-1 font-bold text-emerald-600">{{ $partner['score'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="section-5" class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">مركز حل المشاكل والدعم</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">التذاكر، الأولوية، والمتابعة</h3>
                        </div>
                        <span class="rounded-2xl bg-rose-50 px-4 py-2 text-sm font-bold text-rose-600">92 مفتوحة</span>
                    </div>
                    <div class="mt-6 space-y-4">
                        @foreach ($tickets as $ticket)
                            <div class="rounded-[28px] bg-slate-50 p-5">
                                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                    <div>
                                        <p class="text-lg font-extrabold text-slate-900">{{ $ticket['title'] }}</p>
                                        <p class="mt-2 text-sm text-slate-500">{{ $ticket['type'] }} · {{ $ticket['assignee'] }}</p>
                                    </div>
                                    <div class="flex gap-2 text-xs font-bold">
                                        <span class="rounded-full bg-amber-50 px-3 py-2 text-amber-600">{{ $ticket['priority'] }}</span>
                                        <span class="rounded-full bg-brand-50 px-3 py-2 text-brand-600">{{ $ticket['status'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="section-6" class="mt-6 grid gap-6 xl:grid-cols-3">
                <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card xl:col-span-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">التحليلات</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">أداء المبيعات، الطلبات، الدفع والشحن</h3>
                        </div>
                        <button class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600">تصدير تقرير</button>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @foreach ([
                            ['title' => 'أداء الدفع', 'value' => '97.6%', 'bar' => 'w-[97%]'],
                            ['title' => 'أداء الشحن', 'value' => '94.1%', 'bar' => 'w-[94%]'],
                            ['title' => 'نمو المتاجر', 'value' => '24.6%', 'bar' => 'w-[75%]'],
                            ['title' => 'تحويل الزيارات', 'value' => '6.8%', 'bar' => 'w-[68%]'],
                        ] as $item)
                            <div class="rounded-[28px] bg-slate-50 p-5">
                                <div class="flex items-center justify-between">
                                    <p class="font-extrabold text-slate-900">{{ $item['title'] }}</p>
                                    <p class="text-sm font-bold text-brand-600">{{ $item['value'] }}</p>
                                </div>
                                <div class="mt-4 h-3 rounded-full bg-white">
                                    <div class="h-3 rounded-full bg-gradient-to-l from-brand-500 to-sky-400 {{ $item['bar'] }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div id="section-7" class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">خدمات الشركاء</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">الطلبات والخدمات</h3>
                        </div>
                        <img src="{{ url('/feature_1.png') }}" alt="خدمات الشركاء" class="h-20 rounded-3xl object-cover">
                    </div>
                    <div class="mt-6 space-y-4">
                        @foreach ($services as $service)
                            <div class="rounded-[28px] border border-slate-100 p-5">
                                <div class="flex items-center justify-between">
                                    <p class="text-lg font-extrabold text-slate-900">{{ $service['name'] }}</p>
                                    <p class="rounded-full bg-brand-50 px-3 py-2 text-xs font-bold text-brand-600">{{ $service['sla'] }}</p>
                                </div>
                                <p class="mt-3 text-sm text-slate-500">{{ $service['requests'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="mt-6 grid gap-6 xl:grid-cols-2">
                <div id="section-8" class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">تطبيق المتجر</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">التحميلات والمستخدمون النشطون</h3>
                        </div>
                        <img src="{{ url('/تطبيق_متاجر.png') }}" alt="تطبيق المتجر" class="h-24 rounded-3xl object-cover">
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="text-sm text-slate-500">عدد التحميلات</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-900">84K</p>
                        </div>
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="text-sm text-slate-500">المستخدمون النشطون</p>
                            <p class="mt-3 text-3xl font-extrabold text-slate-900">21.4K</p>
                        </div>
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="text-sm text-slate-500">معدل التحويل</p>
                            <p class="mt-3 text-3xl font-extrabold text-emerald-600">31%</p>
                        </div>
                    </div>
                </div>

                <div id="section-9" class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-brand-600">الإعدادات</p>
                            <h3 class="mt-2 text-2xl font-extrabold text-slate-900">إدارة المنصة والصلاحيات</h3>
                        </div>
                        <span class="rounded-2xl bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600">آخر تحديث: اليوم</span>
                    </div>
                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        @foreach ([
                            'إعدادات المنصة العامة',
                            'إعدادات الدفع',
                            'إعدادات الشحن',
                            'المستخدمون والصلاحيات',
                            'مفاتيح التكامل',
                            'قواعد الإشعارات',
                        ] as $setting)
                            <div class="rounded-[28px] bg-slate-50 p-5">
                                <p class="font-extrabold text-slate-900">{{ $setting }}</p>
                                <p class="mt-2 text-sm leading-7 text-slate-500">تحكم مركزي وإعدادات مرنة تناسب التوسع التشغيلي لمنصة Solve.</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>
@endsection
