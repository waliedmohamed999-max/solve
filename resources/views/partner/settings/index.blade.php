@extends('layouts.partner')

@section('title', 'Solve Merchant | الإعدادات')

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    <section class="mb-6 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-sm font-black text-solve-600 dark:text-solve-300">مركز التحكم</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">الإعدادات</h1>
                <p class="mt-2 text-sm font-bold text-slate-500 dark:text-slate-400">كل إعدادات {{ $partner['name'] }} مرتبطة بمتجر {{ $partner['store_id'] }} وتقرأ من قاعدة البيانات.</p>
            </div>
            <div class="grid gap-2 text-xs font-black text-slate-500 sm:grid-cols-3">
                <span class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">Store ID: {{ $partner['store_id'] }}</span>
                <span class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">Plan: {{ $partner['plan'] }}</span>
                <span class="rounded-2xl bg-slate-50 px-4 py-3 dark:bg-slate-950">RBAC: {{ $roleLabel }}</span>
            </div>
        </div>
    </section>

    <div class="space-y-8">
        @foreach ($settingsGroups as $group)
            <section>
                <h2 class="mb-3 text-sm font-black text-slate-700 dark:text-slate-300">{{ $group['title'] }}</h2>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['url'] }}" class="group rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-solve-200 hover:bg-solve-50/40 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-slate-800">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-black text-slate-950 group-hover:text-solve-700 dark:text-white">{{ $item['title'] }}</h3>
                                    <p class="mt-2 min-h-10 text-xs font-bold leading-6 text-slate-500">{{ $item['body'] }}</p>
                                    <div class="mt-3 flex items-center gap-2">
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-black text-slate-500 dark:bg-slate-800">{{ $item['status'] }}</span>
                                        <span class="text-[11px] font-black text-solve-600">{{ $item['progress'] }}%</span>
                                    </div>
                                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="h-full rounded-full bg-solve-600" style="width: {{ $item['progress'] }}%"></div>
                                    </div>
                                </div>
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-50 text-slate-500 group-hover:bg-white group-hover:text-solve-700 dark:bg-slate-950 dark:text-slate-300">
                                    @include('partner.partials.icon', ['name' => $item['icon'], 'class' => 'h-4 w-4'])
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
