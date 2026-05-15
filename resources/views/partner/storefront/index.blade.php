@extends('layouts.partner')

@section('title', 'المتجر الإلكتروني | Solve')

@section('partner-content')
<div class="space-y-6" x-data="{ loading: false, error: false }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300">
                <a href="{{ route('partner.dashboard') }}">لوحة التحكم</a>
                <span>/</span>
                <span>المتجر الإلكتروني</span>
            </div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">المتجر الإلكتروني</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">إدارة القالب والصفحات والبنرات والدومين وSEO حسب متجر {{ $storefront['store_id'] }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach ($storefront['quickActions'] as $action)
                @if (! empty($action['route']))
                    <a href="{{ route($action['route']) }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">{{ $action['label'] }}</a>
                @else
                    <a href="{{ $action['url'] }}" target="_blank" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">{{ $action['label'] }}</a>
                @endif
            @endforeach
        </div>
    </div>

    <div x-show="loading" class="grid gap-4 md:grid-cols-3">
        @for ($i = 0; $i < 6; $i++)
            <div class="h-32 animate-pulse rounded-3xl bg-slate-200 dark:bg-slate-800"></div>
        @endfor
    </div>
    <div x-show="error" class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm font-black text-rose-700">تعذر تحميل ملخص واجهة المتجر. أعد المحاولة.</div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" x-show="!loading && !error">
        @foreach ($storefront['cards'] as $card)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm font-black text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-3 text-2xl font-black text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                <p class="mt-2 text-xs font-bold text-slate-400">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-black">جاهزية المتجر</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">كل عنصر مرتبط بإعداد فعلي في قاعدة البيانات.</p>
                </div>
                @include('partner.partials.api-tools', [
                    'url' => route('api.partner.storefront.summary'),
                    'copyLabel' => 'نسخ API',
                    'openLabel' => 'فتح API',
                ])
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ($storefront['readiness'] as $step)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                        <span class="font-black">{{ $step['label'] }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-black {{ $step['done'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $step['done'] ? 'مكتمل' : 'يحتاج إجراء' }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">القالب الحالي</h2>
            @if ($storefront['currentTheme'])
                <div class="mt-4 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-lg font-black">{{ $storefront['currentTheme']['name'] }}</p>
                    <p class="mt-2 text-sm font-bold text-slate-500">{{ $storefront['currentTheme']['style'] ?? '-' }}</p>
                    <div class="mt-4 flex gap-2">
                        <span class="h-8 w-8 rounded-full border" style="background: {{ $storefront['currentTheme']['primary_color'] ?? '#6d28d9' }}"></span>
                        <span class="h-8 w-8 rounded-full border" style="background: {{ $storefront['currentTheme']['secondary_color'] ?? '#06b6d4' }}"></span>
                    </div>
                </div>
            @else
                <div class="mt-4 rounded-2xl bg-slate-50 p-6 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">لا يوجد قالب مفعل.</div>
            @endif
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">آخر الصفحات</h2>
            <div class="mt-4 space-y-3">
                @forelse ($storefront['recentPages'] as $page)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                        <div><p class="font-black">{{ $page['title'] }}</p><p class="text-xs font-bold text-slate-500">/{{ $page['slug'] }}</p></div>
                        <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ $page['status'] }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد صفحات بعد.</div>
                @endforelse
            </div>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">البنرات النشطة</h2>
            <div class="mt-4 space-y-3">
                @forelse ($storefront['activeBanners'] as $banner)
                    <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                        <div><p class="font-black">{{ $banner['title'] }}</p><p class="text-xs font-bold text-slate-500">{{ $banner['placement'] }}</p></div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $banner['status'] }}</span>
                    </div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-6 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد بنرات نشطة.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
