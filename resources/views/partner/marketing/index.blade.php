@extends('layouts.partner')

@section('title', 'Solve Merchant | التسويق')

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="text-sm font-black text-solve-700 dark:text-solve-300">لوحة الشريك / التسويق</div>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">ملخص التسويق</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">أداء الحملات والكوبونات والسلات المتروكة لمتجر {{ $partner['store_id'] }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.marketing.campaigns') }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إنشاء حملة</a>
            <a href="{{ route('partner.marketing.coupons') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">إنشاء كوبون</a>
            <a href="{{ route('api.partner.marketing.summary') }}" class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">API</a>
        </div>
    </section>

    <section class="mt-8 grid gap-4 md:grid-cols-4">
        @foreach ($marketing['kpis'] as $kpi)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $kpi['label'] }}</p>
                <p class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $kpi['value'] }}</p>
                <p class="mt-2 text-xs font-bold text-slate-500">{{ $kpi['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-6 grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">أفضل حملة أداء</h2>
            @if ($marketing['bestCampaign'])
                <div class="mt-5 rounded-2xl bg-slate-50 p-5 dark:bg-slate-950">
                    <p class="text-lg font-black">{{ $marketing['bestCampaign']['name'] }}</p>
                    <p class="mt-2 text-sm font-bold text-slate-500">{{ $marketing['bestCampaign']['type'] ?? '-' }} · {{ $marketing['bestCampaign']['target_audience'] ?? '-' }}</p>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-sm font-black">
                        <span class="rounded-xl bg-white p-3 dark:bg-slate-900">زيارات<br>{{ $marketing['bestCampaign']['visits'] ?? 0 }}</span>
                        <span class="rounded-xl bg-white p-3 dark:bg-slate-900">طلبات<br>{{ $marketing['bestCampaign']['orders'] ?? 0 }}</span>
                        <span class="rounded-xl bg-white p-3 dark:bg-slate-900">مبيعات<br>{{ $marketing['bestCampaign']['sales'] ?? '0 ر.س' }}</span>
                    </div>
                </div>
            @else
                <p class="mt-4 rounded-2xl bg-slate-50 p-5 text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد حملات بعد.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">اختصارات التسويق</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                @foreach ($marketing['quickActions'] as $action)
                    <a href="{{ route($action['route']) }}" class="rounded-2xl bg-slate-50 p-5 text-sm font-black text-slate-800 hover:bg-solve-50 hover:text-solve-700 dark:bg-slate-950 dark:text-slate-100 dark:hover:bg-slate-800">{{ $action['label'] }}</a>
                @endforeach
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-5">
                <a href="{{ route('partner.marketing.coupons') }}" class="rounded-2xl border border-slate-100 p-4 text-center text-sm font-black dark:border-slate-800">كوبونات<br>{{ $marketing['counts']['coupons'] }}</a>
                <a href="{{ route('partner.marketing.campaigns') }}" class="rounded-2xl border border-slate-100 p-4 text-center text-sm font-black dark:border-slate-800">حملات<br>{{ $marketing['counts']['campaigns'] }}</a>
                <a href="{{ route('partner.marketing.bundles') }}" class="rounded-2xl border border-slate-100 p-4 text-center text-sm font-black dark:border-slate-800">حزم<br>{{ $marketing['counts']['bundles'] }}</a>
                <a href="{{ route('partner.marketing.affiliate') }}" class="rounded-2xl border border-slate-100 p-4 text-center text-sm font-black dark:border-slate-800">عمولة<br>{{ $marketing['counts']['affiliate'] }}</a>
                <a href="{{ route('partner.marketing.ads') }}" class="rounded-2xl border border-slate-100 p-4 text-center text-sm font-black dark:border-slate-800">ربط إعلاني<br>{{ $marketing['counts']['ads_connected'] }}</a>
            </div>
        </div>
    </section>

    <section class="mt-6 grid gap-5 xl:grid-cols-2">
        @foreach (['campaigns' => 'آخر الحملات', 'coupons' => 'آخر الكوبونات'] as $key => $title)
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">{{ $title }}</h2>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($marketing['recent'][$key] as $row)
                        <div class="flex items-center justify-between gap-3 p-4">
                            <div>
                                <p class="font-black">{{ $row['name'] }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $row['status'] }} · {{ $row['updated_at_human'] }}</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black dark:bg-slate-800">{{ $row['store_id'] }}</span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm font-bold text-slate-500">لا توجد بيانات.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </section>
</div>
@endsection
