@extends('layouts.admin')

@section('title', 'Solve Admin | ' . $pageTitle)

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-sm font-bold text-brand-600">Solve Admin</p>
            <h2 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $pageTitle }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">{{ $pageDescription }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('site.home') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700">صفحة العرض</a>
            <a href="{{ route('admin.dashboard') }}" class="rounded-2xl bg-brand-600 px-5 py-3 text-sm font-bold text-white">العودة للرئيسية</a>
        </div>
    </div>
</section>

<section class="mt-6 grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
    @foreach ($summaryCards as $card)
        <div class="rounded-[28px] border border-white/70 bg-white p-5 shadow-card">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-4 text-3xl font-extrabold text-slate-900">{{ $card['value'] }}</p>
                </div>
                <span class="rounded-2xl bg-brand-50 px-3 py-2 text-sm font-bold text-brand-600">{{ $card['change'] }}</span>
            </div>
        </div>
    @endforeach
</section>

<section class="mt-6 grid gap-6 md:grid-cols-2">
    @foreach ($panels as $panel)
        <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card {{ ($panel['span'] ?? '') === 'full' ? 'md:col-span-2' : '' }}">
            <h3 class="text-2xl font-extrabold text-slate-900">{{ $panel['title'] }}</h3>

            @if ($panel['kind'] === 'table')
                <div class="mt-6 overflow-x-auto">
                    <table class="min-w-full text-right text-sm">
                        <thead>
                            <tr class="text-slate-400">
                                @foreach ($panel['columns'] as $column)
                                    <th class="px-4 py-4 font-bold">{{ $column }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($panel['rows'] as $row)
                                <tr class="border-t border-slate-100">
                                    @foreach ($row as $cell)
                                        <td class="px-4 py-4 text-slate-700">{{ $cell }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif ($panel['kind'] === 'list')
                <div class="mt-6 space-y-4">
                    @foreach ($panel['items'] as $item)
                        <div class="flex items-center justify-between rounded-3xl bg-slate-50 p-4">
                            <div>
                                <p class="font-extrabold text-slate-900">{{ $item['title'] }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $item['meta'] }}</p>
                            </div>
                            <p class="text-sm font-extrabold text-brand-600">{{ $item['value'] }}</p>
                        </div>
                    @endforeach
                </div>
            @elseif ($panel['kind'] === 'metrics')
                <div class="mt-6 space-y-4">
                    @foreach ($panel['items'] as $item)
                        <div class="rounded-3xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between text-sm text-slate-500"><span>{{ $item['label'] }}</span><span>{{ $item['value'] }}</span></div>
                            <div class="mt-3 h-3 rounded-full bg-white"><div class="h-3 rounded-full bg-gradient-to-l from-brand-500 to-sky-400" style="width: {{ $item['width'] }}"></div></div>
                        </div>
                    @endforeach
                </div>
            @elseif ($panel['kind'] === 'tags')
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($panel['items'] as $item)
                        <span class="rounded-full bg-slate-50 px-4 py-3 text-sm font-bold text-slate-700">{{ $item }}</span>
                    @endforeach
                </div>
            @elseif ($panel['kind'] === 'settings')
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach ($panel['items'] as $item)
                        <div class="rounded-[28px] bg-slate-50 p-5">
                            <p class="font-extrabold text-slate-900">{{ $item['title'] }}</p>
                            <p class="mt-3 text-sm leading-7 text-slate-500">{{ $item['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</section>
@endsection