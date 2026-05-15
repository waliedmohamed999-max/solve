@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $page['title'])

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <nav class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-500 dark:text-slate-400">
        @foreach ($page['breadcrumbs'] as $crumb)
            @if ($crumb['url'])
                <a href="{{ $crumb['url'] }}" class="hover:text-solve-600">{{ $crumb['label'] }}</a>
            @else
                <span class="text-slate-900 dark:text-white">{{ $crumb['label'] }}</span>
            @endif
            @if (! $loop->last)
                <span>/</span>
            @endif
        @endforeach
    </nav>

    <section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-sm font-black text-solve-600 dark:text-solve-300">{{ $page['sectionTitle'] }}</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $page['title'] }}</h1>
                <p class="mt-3 text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">{{ $page['description'] }}</p>
            </div>
            <div class="grid gap-2 text-sm font-bold sm:grid-cols-2 xl:min-w-[360px]">
                <span class="rounded-xl bg-slate-50 px-3 py-2 text-slate-600 dark:bg-slate-950 dark:text-slate-300">Store: {{ $page['storeScope']['store_id'] }}</span>
                <span class="rounded-xl bg-slate-50 px-3 py-2 text-slate-600 dark:bg-slate-950 dark:text-slate-300">Plan: {{ $page['storeScope']['plan'] }}</span>
                <a href="{{ $page['apiUrl'] }}" class="rounded-xl bg-solve-50 px-3 py-2 text-solve-700 hover:bg-solve-100 dark:bg-solve-500/10 dark:text-solve-200">API JSON</a>
                <span class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">RBAC محمي</span>
            </div>
        </div>
    </section>

    <section class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($page['stats'] as $stat)
            <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $stat['label'] }}</p>
                <p class="mt-2 truncate text-xl font-black text-slate-950 dark:text-white">{{ $stat['value'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="mt-4 grid gap-4 xl:grid-cols-[1fr_320px]" x-data="{ tableQuery: '' }">
        <article class="rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                <label class="relative block w-full max-w-md">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400">
                        @include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])
                    </span>
                    <input x-model.debounce.200ms="tableQuery" type="search" placeholder="بحث داخل البيانات"
                        class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pr-10 pl-3 text-sm font-bold outline-none focus:border-solve-300 focus:bg-white dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                </label>
                <div class="flex flex-wrap gap-2">
                    @foreach (array_slice($page['quickActions'], 0, 3) as $action)
                        <a href="{{ $action['url'] }}" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">{{ $action['label'] }}</a>
                    @endforeach
                </div>
            </div>

            @if (count($page['rows']))
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-right dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                            <tr>
                                @foreach ($page['columns'] as $column)
                                    <th class="whitespace-nowrap px-4 py-3">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                            @foreach ($page['rows'] as $row)
                                <tr class="transition hover:bg-slate-50 dark:hover:bg-slate-950/60"
                                    x-show="tableQuery.trim() === '' || @js(json_encode($row, JSON_UNESCAPED_UNICODE)).toLowerCase().includes(tableQuery.trim().toLowerCase())">
                                    @foreach ($page['columns'] as $column)
                                        <td class="whitespace-nowrap px-4 py-3 font-bold text-slate-700 dark:text-slate-300">{{ $row[$column] ?? '-' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-10 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        @include('partner.partials.icon', ['name' => 'grid'])
                    </div>
                    <h2 class="mt-4 text-lg font-black text-slate-900 dark:text-white">{{ $page['emptyState']['title'] }}</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm font-bold leading-7 text-slate-500">{{ $page['emptyState']['body'] }}</p>
                </div>
            @endif
        </article>

        <aside class="space-y-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">Quick Actions</h2>
                <div class="mt-4 space-y-2">
                    @foreach ($page['quickActions'] as $action)
                        <a href="{{ $action['url'] }}" class="flex w-full items-center justify-between rounded-xl bg-slate-50 px-3 py-3 text-sm font-black text-slate-700 hover:bg-slate-100 dark:bg-slate-950 dark:text-slate-300 dark:hover:bg-slate-800">
                            <span>{{ $action['label'] }}</span>
                            @include('partner.partials.icon', ['name' => 'bolt', 'class' => 'h-4 w-4 text-solve-500'])
                        </a>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-lg font-black text-slate-950 dark:text-white">حالة الربط</h2>
                <div class="mt-4 space-y-3 text-sm font-bold text-slate-600 dark:text-slate-300">
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-950"><span>قاعدة البيانات</span><span>متصل</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-950"><span>لوحة الأدمن</span><span>متزامن</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-950"><span>الصلاحيات</span><span>{{ $roleLabel }}</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-950"><span>آخر تحديث</span><span>فوري</span></div>
                </div>
            </article>
        </aside>
    </section>
</div>
@endsection
