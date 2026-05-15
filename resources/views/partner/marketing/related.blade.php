@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $title)

@section('partner-content')
@php
    $filters = $marketingPage['filters'];
    $pagination = $marketingPage['pagination'];
    $sectionKey = match($section) {
        'marketing_coupons' => 'coupons',
        'marketing_campaigns' => 'campaigns',
        'marketing_bundles' => 'bundles',
        'marketing_affiliate_links' => 'affiliate',
        default => 'ads',
    };
    $columns = array_values(array_unique(collect($marketingPage['rows'])->flatMap(fn ($row) => array_keys($row))->reject(fn ($key) => in_array($key, ['store_id', 'updated_at_human'], true))->all()));
@endphp

<div class="px-4 py-6 lg:px-8" x-data="{ loading: false, error: '' }">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <a href="{{ route('partner.marketing') }}" class="text-sm font-black text-solve-700 dark:text-solve-300">التسويق</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">بيانات حقيقية من `platform_records` ومفلترة حسب {{ $partner['store_id'] }}.</p>
        </div>
        <a href="{{ route('api.partner.' . match($section) { 'marketing_coupons' => 'coupons.index', 'marketing_campaigns' => 'campaigns.index', 'marketing_bundles' => 'bundles.index', 'marketing_affiliate_links' => 'affiliate.index', default => 'ads.integrations' }) }}" class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">API</a>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('partner.marketing.' . ($sectionKey === 'ads' ? 'ads' : $sectionKey)) }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center">
            <div class="relative w-full max-w-md">
                <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">@include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])</span>
                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="بحث سريع"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pr-11 pl-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </div>
            <select name="status" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($marketingPage['statusOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="h-11 rounded-full bg-slate-950 px-5 text-sm font-black text-white dark:bg-white dark:text-slate-950">تطبيق</button>
        </form>

        <form method="POST" action="{{ route('partner.marketing.store', ['section' => $sectionKey]) }}" class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 md:grid-cols-6">
            @csrf
            <input name="name" required placeholder="الاسم" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @if ($section === 'marketing_coupons')
                <input name="code" required placeholder="CODE" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <select name="discount_type" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    @foreach (\App\Support\PartnerMarketing::COUPON_TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                <input name="discount_value" type="number" step="0.01" placeholder="قيمة الخصم" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="minimum_order" type="number" step="0.01" placeholder="حد أدنى" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="usage_limit" type="number" placeholder="مرات الاستخدام" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @elseif ($section === 'marketing_campaigns')
                <select name="type" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    @foreach (\App\Support\PartnerMarketing::CAMPAIGN_TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
                <input name="target_audience" placeholder="الجمهور" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="coupon_code" placeholder="كوبون مرتبط" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="scheduled_at" type="datetime-local" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="sales" type="number" step="0.01" placeholder="مبيعات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @elseif ($section === 'marketing_bundles')
                <input name="products" placeholder="المنتجات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="bundle_price" required type="number" step="0.01" placeholder="سعر الحزمة" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="discount_value" type="number" step="0.01" placeholder="الخصم" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="orders" type="number" placeholder="طلبات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="sales" type="number" step="0.01" placeholder="مبيعات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @elseif ($section === 'marketing_affiliate_links')
                <input name="marketer" required placeholder="المسوق" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="url" type="url" placeholder="رابط التتبع" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="commission_rate" required type="number" step="0.01" placeholder="نسبة العمولة" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="orders" type="number" placeholder="طلبات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="earnings" type="number" step="0.01" placeholder="أرباح" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @else
                <input name="provider" required placeholder="مزود التتبع" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="pixel_id" placeholder="Pixel ID" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="conversions" type="number" placeholder="تحويلات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="spend" type="number" step="0.01" placeholder="إنفاق" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="sales" type="number" step="0.01" placeholder="مبيعات" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @endif
            <button class="h-11 rounded-xl bg-solve-700 px-4 text-sm font-black text-white">إضافة</button>
        </form>

        <div class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 sm:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">المعروض</p><p class="mt-1 text-xl font-black">{{ $marketingPage['summary']['filtered'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">الإجمالي</p><p class="mt-1 text-xl font-black">{{ $marketingPage['summary']['total'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">نشط</p><p class="mt-1 text-xl font-black">{{ $marketingPage['summary']['active'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">متوقف</p><p class="mt-1 text-xl font-black">{{ $marketingPage['summary']['paused'] }}</p></div>
        </div>

        <div class="border-b border-slate-100 px-4 py-3 text-sm font-black text-slate-500 dark:border-slate-800" x-show="loading">
            <div class="grid gap-2 md:grid-cols-4"><span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span><span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span><span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span><span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span></div>
        </div>
        <div class="border-b border-rose-100 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200" x-show="error" x-text="error" x-cloak></div>

        @if (count($marketingPage['rows']))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            @foreach ($columns as $column)<th class="whitespace-nowrap px-4 py-4">{{ $column }}</th>@endforeach
                            <th class="whitespace-nowrap px-4 py-4">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($marketingPage['rows'] as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                @foreach ($columns as $column)
                                    <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-700 dark:text-slate-300">{{ is_array($row[$column] ?? null) ? json_encode($row[$column], JSON_UNESCAPED_UNICODE) : ($row[$column] ?? '-') }}</td>
                                @endforeach
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('partner.marketing.status', ['section' => $sectionKey, 'record' => $row['id']]) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ ($row['status_key'] ?? '') === 'active' ? 'paused' : 'active' }}">
                                            <button class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black dark:border-slate-700">{{ ($row['status_key'] ?? '') === 'active' ? 'إيقاف' : 'تفعيل' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('partner.marketing.delete', ['section' => $sectionKey, 'record' => $row['id']]) }}" onsubmit="return confirm('تأكيد الحذف؟')">
                                            @csrf
                                            <button class="rounded-full border border-rose-200 px-3 py-2 text-xs font-black text-rose-700 dark:border-rose-500/30 dark:text-rose-200">حذف</button>
                                        </form>
                                        @if ($section === 'marketing_coupons')
                                            <a href="{{ route('api.partner.coupons.usage', ['coupon' => $row['id']]) }}" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-black dark:bg-slate-800">تقرير</a>
                                        @elseif ($section === 'marketing_campaigns')
                                            <a href="{{ route('api.partner.campaigns.analytics', ['campaign' => $row['id']]) }}" class="rounded-full bg-slate-100 px-3 py-2 text-xs font-black dark:bg-slate-800">تحليل</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد بيانات في {{ $title }}</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">أضف أول سجل ليبدأ القياس والتتبع.</p>
            </div>
        @endif
    </section>
</div>
@endsection
