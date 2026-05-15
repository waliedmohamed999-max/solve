@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $analytics['title'])

@php
    $maxSales = max(collect($analytics['chart'])->pluck('sales')->map(fn ($value) => (float) $value)->max() ?: 1, 1);
    $rangeLinks = [
        '7' => '7 أيام',
        '30' => '30 يوم',
        '90' => '90 يوم',
        '365' => 'سنة',
    ];
@endphp

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-black text-slate-500 dark:text-slate-400">
        <a href="{{ route('partner.dashboard') }}" class="hover:text-solve-600">لوحة التحكم</a>
        <span>/</span>
        <span class="text-slate-950 dark:text-white">التحليلات</span>
        @if ($analytics['key'] !== 'overview')
            <span>/</span>
            <span class="text-slate-950 dark:text-white">{{ $analytics['title'] }}</span>
        @endif
    </nav>

    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-black text-solve-600 dark:text-solve-300">تحليلات المتجر</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $analytics['title'] }}</h1>
                <p class="mt-3 text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">{{ $analytics['description'] }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $analytics['apiUrl'] }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200">API JSON</a>
                <a href="{{ $analytics['exportUrl'] }}" class="rounded-full bg-solve-700 px-5 py-2 text-sm font-black text-white transition hover:bg-solve-800">تصدير CSV</a>
            </div>
        </div>

        <div class="mt-5 flex flex-col gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap gap-2">
                @foreach ($rangeLinks as $range => $label)
                    <a href="{{ url()->current() . '?' . http_build_query(array_merge(request()->except('range'), ['range' => $range])) }}"
                        class="rounded-full px-4 py-2 text-xs font-black transition {{ $analytics['period']['range'] === $range ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-50 text-slate-600 hover:bg-slate-100 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-black text-slate-500 dark:text-slate-400">
                <span class="rounded-full bg-slate-50 px-3 py-2 dark:bg-slate-950">Store ID: {{ $analytics['store']['id'] }}</span>
                <span class="rounded-full bg-slate-50 px-3 py-2 dark:bg-slate-950">{{ $analytics['period']['from'] }} - {{ $analytics['period']['to'] }}</span>
            </div>
        </div>
    </section>

    <section class="mt-4 overflow-x-auto rounded-3xl border border-slate-200 bg-white p-2 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex min-w-max gap-2">
            @foreach ($analytics['tabs'] as $tab)
                <a href="{{ $tab['url'] . '?' . http_build_query(request()->only('range')) }}"
                    class="rounded-2xl px-4 py-3 text-sm font-black transition {{ $analytics['key'] === $tab['key'] ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' }}">
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
        @foreach ($analytics['cards'] as $card)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $card['label'] }}</p>
                <p class="mt-3 truncate text-2xl font-black text-slate-950 dark:text-white">{{ $card['value'] }}</p>
                <p class="mt-3 text-xs font-bold leading-6 text-slate-500 dark:text-slate-400">{{ $card['hint'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_360px]">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">اتجاه المبيعات</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500 dark:text-slate-400">قراءة يومية من جدول platform_records حسب store_id.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-2 text-xs font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">متصل بقاعدة البيانات</span>
            </div>
            <div class="mt-6 flex h-64 items-end gap-1 overflow-hidden rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                @foreach ($analytics['chart'] as $point)
                    @php $height = max(6, min(100, ((float) $point['sales'] / $maxSales) * 100)); @endphp
                    <div class="group flex min-w-4 flex-1 flex-col items-center justify-end gap-2">
                        <div class="w-full rounded-t-xl bg-gradient-to-t from-solve-700 to-cyan-400 transition group-hover:from-solve-900" style="height: {{ $height }}%"></div>
                        @if ($loop->iteration === 1 || $loop->last || $loop->iteration % 5 === 0)
                            <span class="text-[10px] font-bold text-slate-400">{{ $point['label'] }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
        </article>

        <aside class="space-y-4">
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">تنبيهات التحليل</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($analytics['insights'] as $insight)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                            <span class="text-sm font-black text-slate-700 dark:text-slate-200">{{ $insight['title'] }}</span>
                            <span class="rounded-full bg-white px-3 py-1 text-sm font-black text-solve-700 dark:bg-slate-900 dark:text-solve-200">{{ $insight['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">حالة الربط</h2>
                <div class="mt-4 space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                    <div class="flex justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950"><span>النطاق</span><span>{{ $analytics['store']['id'] }}</span></div>
                    <div class="flex justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950"><span>الصلاحيات</span><span>RBAC</span></div>
                    <div class="flex justify-between rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950"><span>المصدر</span><span>{{ implode(', ', $analytics['meta']['source_tables']) }}</span></div>
                </div>
            </article>
        </aside>
    </section>

    <section class="mt-4 rounded-3xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-black text-slate-950 dark:text-white">بيانات التقرير</h2>
                <p class="mt-1 text-sm font-bold text-slate-500 dark:text-slate-400">كل صف معزول بمتجر {{ $analytics['store']['id'] }} فقط.</p>
            </div>
            <label class="relative block w-full max-w-sm">
                <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">@include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])</span>
                <input type="search" placeholder="بحث داخل التقرير" class="h-11 w-full rounded-2xl border border-slate-200 bg-slate-50 pr-10 pl-3 text-sm font-bold outline-none focus:border-solve-300 focus:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </label>
        </div>

        @if (count($analytics['rows']))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            @foreach ($analytics['columns'] as $column)
                                <th class="whitespace-nowrap px-5 py-4">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        @foreach ($analytics['rows'] as $row)
                            <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                @foreach ($analytics['columns'] as $column)
                                    <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-700 dark:text-slate-300">{{ $row[$column] ?? '-' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    @include('partner.partials.icon', ['name' => 'bar-chart'])
                </div>
                <h2 class="mt-4 text-xl font-black text-slate-950 dark:text-white">{{ $analytics['emptyState']['title'] }}</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">{{ $analytics['emptyState']['body'] }}</p>
            </div>
        @endif
    </section>
</div>
@endsection
