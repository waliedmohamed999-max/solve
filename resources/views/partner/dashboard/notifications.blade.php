@extends('layouts.partner')

@section('title', 'Solve Merchant | الإشعارات')

@section('partner-content')
<div class="px-4 py-6 lg:px-8"
    x-data="{ loading: true, error: '', async ping() { try { const response = await fetch(@js($notificationsPage['apiUrl']), { headers: { Accept: 'application/json' } }); if (!response.ok) throw new Error('تعذر تحميل الإشعارات'); await response.json(); } catch (exception) { this.error = exception.message || 'حدث خطأ غير متوقع'; } finally { this.loading = false; } } }"
    x-init="ping()">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400">
        @foreach ($notificationsPage['breadcrumbs'] as $crumb)
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
                <h1 class="text-3xl font-black text-slate-950 dark:text-white">الإشعارات</h1>
                <p class="mt-2 text-sm font-bold text-slate-500">تنبيهات النظام والأدمن الخاصة بمتجر {{ $partner['store_id'] }} فقط.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $notificationsPage['apiUrl'] }}" class="rounded-xl bg-solve-50 px-4 py-3 text-sm font-black text-solve-700 hover:bg-solve-100 dark:bg-solve-500/10 dark:text-solve-200">API JSON</a>
                <a href="{{ route('partner.dashboard') }}" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">لوحة التحكم</a>
            </div>
        </div>
    </section>

    <form method="GET" action="{{ route('partner.notifications') }}" class="mt-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900 lg:flex-row lg:items-center">
        <input name="q" value="{{ $notificationsPage['filters']['q'] }}" type="search" placeholder="بحث في التنبيهات"
            class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
        <select name="severity" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            @foreach ($notificationsPage['severityOptions'] as $key => $label)
                <option value="{{ $key }}" @selected($notificationsPage['filters']['severity'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="h-11 rounded-xl bg-slate-950 px-5 text-sm font-black text-white dark:bg-white dark:text-slate-950">تطبيق</button>
        <a href="{{ route('partner.notifications') }}" class="flex h-11 items-center rounded-xl border border-slate-200 px-5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">إعادة ضبط</a>
    </form>

    <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-500 dark:border-slate-800 dark:bg-slate-900" x-show="loading">
        جاري التحقق من API الإشعارات...
    </div>
    <div class="mt-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200" x-show="error" x-text="error" x-cloak></div>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_280px]">
        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
            @if (count($notificationsPage['rows']))
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($notificationsPage['rows'] as $row)
                        <a href="{{ $row['url'] ?: route('partner.dashboard') }}" class="block p-4 transition hover:bg-slate-50 dark:hover:bg-slate-950/60">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-black text-slate-950 dark:text-white">{{ $row['title'] }}</p>
                                    <p class="mt-1 text-sm font-bold leading-7 text-slate-500">{{ $row['body'] }}</p>
                                </div>
                                <div class="flex shrink-0 flex-wrap items-center gap-2 text-xs font-black">
                                    <span class="rounded-full px-3 py-1 {{ $row['severity'] === 'danger' ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200' : ($row['severity'] === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200' : 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200') }}">{{ $row['severity'] }}</span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $row['created_at'] }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="p-12 text-center">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد إشعارات مطابقة</h2>
                    <p class="mt-2 text-sm font-bold text-slate-500">ستظهر هنا تنبيهات الأدمن والنظام الخاصة بمتجرك.</p>
                </div>
            @endif
        </article>

        <aside class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-black text-slate-400">الإجمالي</p>
            <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $notificationsPage['summary']['total'] }}</p>
            <p class="mt-4 rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500 dark:bg-slate-950">غير مقروء: {{ $notificationsPage['summary']['unread'] }}</p>
            <p class="mt-2 rounded-xl bg-slate-50 px-3 py-2 text-xs font-bold text-slate-500 dark:bg-slate-950">Store ID: {{ $notificationsPage['summary']['store_id'] }}</p>
        </aside>
    </section>
</div>
@endsection
