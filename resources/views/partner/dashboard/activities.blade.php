@extends('layouts.partner')

@section('title', 'Solve Merchant | آخر النشاطات')

@section('partner-content')
<div class="px-4 py-6 lg:px-8"
    x-data="{ loading: true, error: '', async ping() { try { const response = await fetch(@js($activityPage['apiUrl']), { headers: { Accept: 'application/json' } }); if (!response.ok) throw new Error('تعذر تحميل النشاطات'); await response.json(); } catch (exception) { this.error = exception.message || 'حدث خطأ غير متوقع'; } finally { this.loading = false; } } }"
    x-init="ping()">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400">
        @foreach ($activityPage['breadcrumbs'] as $crumb)
            @if ($crumb['url'])
                <a href="{{ $crumb['url'] }}" class="hover:text-solve-600">{{ $crumb['label'] }}</a>
            @else
                <span class="text-slate-900 dark:text-white">{{ $crumb['label'] }}</span>
            @endif
            @if (! $loop->last)<span>/</span>@endif
        @endforeach
    </nav>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div>
                <h1 class="text-3xl font-black text-slate-950 dark:text-white">آخر النشاطات</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">كل عملية مهمة مرتبطة بمتجر {{ $partner['store_id'] }} فقط، وتظهر للأدمن ضمن سجل المنصة.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $activityPage['apiUrl'] }}" class="rounded-xl bg-solve-50 px-4 py-3 text-sm font-black text-solve-700 hover:bg-solve-100 dark:bg-solve-500/10 dark:text-solve-200">API JSON</a>
                <a href="{{ route('partner.dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">لوحة التحكم</a>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('partner.activities') }}" class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
        <input name="q" value="{{ $activityPage['filters']['q'] }}" type="search" placeholder="بحث في النشاطات"
            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        <button class="h-11 rounded-xl bg-slate-950 px-5 text-sm font-black text-white dark:bg-white dark:text-slate-950">بحث</button>
        <a href="{{ route('partner.activities') }}" class="flex h-11 items-center rounded-xl border border-slate-200 px-5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">إعادة ضبط</a>
    </form>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-500 dark:border-slate-800 dark:bg-slate-900" x-show="loading">
        جاري التحقق من API النشاطات...
    </div>
    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200" x-show="error" x-text="error" x-cloak></div>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_280px]">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
            @if (count($activityPage['rows']))
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($activityPage['rows'] as $row)
                        <div class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-black text-slate-950 dark:text-white">{{ $row['action'] }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $row['actor'] }} · {{ $row['subject_type'] }} · {{ $row['subject_id'] }}</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs font-black">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $row['store_id'] }}</span>
                                <span class="rounded-full bg-solve-50 px-3 py-1 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200">{{ $row['created_at'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد نشاطات مطابقة</h2>
                    <p class="mt-2 text-sm font-bold text-slate-500">ستظهر هنا العمليات المرتبطة بالطلبات والمنتجات والإعدادات.</p>
                </div>
            @endif
        </article>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black text-slate-400">السجلات</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $activityPage['summary']['total'] }}</p>
            <p class="mt-4 rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500 dark:bg-slate-950">Store ID: {{ $activityPage['summary']['store_id'] }}</p>
        </aside>
    </section>
</div>
@endsection
