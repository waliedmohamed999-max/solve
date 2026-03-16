@extends('layouts.admin')

@section('title', 'Solve Admin | الرئيسية')

@section('admin-content')
<section class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
    <div class="overflow-hidden rounded-[32px] bg-gradient-to-br from-slate-950 via-brand-900 to-brand-600 p-7 text-white shadow-soft">
        <div class="flex flex-col gap-8 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-2xl">
                <p class="text-sm font-bold text-brand-100">Solve Platform Control Center</p>
                <h2 class="mt-4 text-3xl font-extrabold leading-[1.6] lg:text-5xl">لوحة تحكم احترافية لإدارة منصة Solve، حل المشاكل، ودفع نمو المتاجر.</h2>
                <p class="mt-4 max-w-xl text-sm leading-8 text-slate-200 lg:text-base">تصميم SaaS نظيف مستلهم من لوحات Shopify و Stripe مع واجهة عربية RTL ومسارات مستقلة لكل وحدة تشغيلية.</p>
                <div class="mt-6 flex flex-wrap gap-3 text-sm">
                    <span class="rounded-full bg-white/10 px-4 py-2">12.4K متجر</span>
                    <span class="rounded-full bg-white/10 px-4 py-2">248K طلب هذا الشهر</span>
                    <span class="rounded-full bg-emerald-400/20 px-4 py-2 text-emerald-100">نمو +24.6%</span>
                </div>
            </div>
            <div class="relative mx-auto w-full max-w-sm">
                <div class="absolute inset-0 rounded-full bg-white/10 blur-3xl"></div>
                <img src="{{ asset('منصة_متاجر.png') }}" alt="Solve" class="relative mx-auto w-full max-w-xs drop-shadow-2xl">
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
        @foreach ([
            ['label' => 'توفر المنصة', 'value' => '99.98%', 'width' => '99%', 'tone' => 'from-emerald-400 to-brand-500'],
            ['label' => 'أداء المدفوعات', 'value' => '97.6%', 'width' => '97%', 'tone' => 'from-sky-400 to-brand-500'],
            ['label' => 'الالتزام بزمن الدعم', 'value' => '94%', 'width' => '94%', 'tone' => 'from-amber-400 to-brand-500'],
        ] as $health)
            <div class="mt-4 rounded-3xl bg-slate-50 p-4">
                <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ $health['label'] }}</span><span>{{ $health['value'] }}</span></div>
                <div class="mt-3 h-2 rounded-full bg-slate-200"><div class="h-2 rounded-full bg-gradient-to-l {{ $health['tone'] }}" style="width: {{ $health['width'] }}"></div></div>
            </div>
        @endforeach
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
            <div class="mt-5 h-2 rounded-full bg-slate-100"><div class="h-2 rounded-full bg-gradient-to-l from-brand-400 to-sky-400" style="width: {{ 62 + ($loop->index * 4) }}%"></div></div>
        </div>
    @endforeach
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-bold text-brand-600">الوحدات السريعة</p>
                <h3 class="mt-2 text-2xl font-extrabold text-slate-900">انتقل مباشرة إلى أقسام الإدارة</h3>
            </div>
            <a href="{{ route('site.home') }}" class="rounded-2xl bg-brand-50 px-4 py-2 text-sm font-bold text-brand-600">واجهة الموقع</a>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @foreach ([
                ['label' => 'المتاجر', 'route' => 'admin.stores'],
                ['label' => 'الاشتراكات', 'route' => 'admin.subscriptions'],
                ['label' => 'المدفوعات', 'route' => 'admin.payments'],
                ['label' => 'الشحن', 'route' => 'admin.shipping'],
                ['label' => 'التحليلات', 'route' => 'admin.analytics'],
                ['label' => 'خدمات الشركاء', 'route' => 'admin.partners'],
                ['label' => 'الدعم الفني', 'route' => 'admin.support'],
                ['label' => 'التطبيقات', 'route' => 'admin.apps'],
                ['label' => 'محتوى الموقع', 'route' => 'admin.site-content'],
            ] as $link)
                <a href="{{ route($link['route']) }}" class="rounded-[26px] bg-slate-50 p-5 text-center text-lg font-extrabold text-slate-800 transition hover:bg-brand-50 hover:text-brand-700">{{ $link['label'] }}</a>
            @endforeach
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
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-600 text-lg font-extrabold text-white">{{ mb_substr($topStore['name'], 0, 1) }}</div>
                        <div><p class="font-extrabold text-slate-900">{{ $topStore['name'] }}</p><p class="text-sm text-slate-500">{{ $topStore['orders'] }} طلب</p></div>
                    </div>
                    <p class="text-sm font-extrabold text-slate-900">{{ $topStore['revenue'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection