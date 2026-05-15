@extends('layouts.partner')

@section('title', $title . ' | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.services') }}">الخدمات</a><span>/</span><span>{{ $title }}</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">{{ $type === 'logistics' ? 'شركات الشحن ومناطق وأسعار الربط.' : 'بوابات الدفع ووضع الاختبار/الإنتاج.' }}</p>
        </div>
        <a href="{{ route('api.partner.services.' . $type) }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse ($servicesPage['rows'] as $row)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="text-xl font-black">{{ $row['name'] }}</h2><p class="mt-1 text-sm font-bold text-slate-500">{{ $row['provider'] ?? '-' }}</p></div>
                    <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ $row['status'] }}</span>
                </div>
                <form method="POST" action="{{ route('partner.services.' . $type . '.settings', ['record' => $row['id']]) }}" class="mt-5 grid gap-3">
                    @csrf
                    <input name="provider" required value="{{ $row['provider'] ?? $row['name'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    <input name="api_key" placeholder="{{ $row['api_key_masked'] ?? 'API Key' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    @if ($type === 'logistics')
                        <input name="regions" value="{{ $row['regions'] ?? '' }}" placeholder="مناطق الشحن" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        <input name="shipping_rates" value="{{ $row['shipping_rates'] ?? '' }}" placeholder="أسعار الشحن" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    @else
                        <select name="mode" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <option value="test" @selected(($row['mode'] ?? '') === 'test')>اختبار</option>
                            <option value="production" @selected(($row['mode'] ?? '') === 'production')>إنتاج</option>
                        </select>
                    @endif
                    <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        @foreach (\App\Support\PartnerServices::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(($row['status_key'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">حفظ الإعدادات</button>
                        <button form="test-{{ $row['id'] }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">اختبار الاتصال</button>
                        <a href="{{ route('api.partner.services.' . $type . '.status', ['record' => $row['id']]) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">الحالة</a>
                    </div>
                </form>
                <form id="test-{{ $row['id'] }}" method="POST" action="{{ route('partner.services.' . $type . '.test', ['record' => $row['id']]) }}">@csrf</form>
            </article>
        @empty
            <div class="rounded-3xl bg-white p-10 text-center text-sm font-bold text-slate-500 dark:bg-slate-900 xl:col-span-2">لا توجد خدمات مرتبطة.</div>
        @endforelse
    </div>
</div>
@endsection
