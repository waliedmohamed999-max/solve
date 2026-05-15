@extends('layouts.partner')

@section('title', $title . ' | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div><div class="text-sm font-black text-solve-700"><a href="{{ route('partner.services') }}">الخدمات</a> / {{ $title }}</div><h1 class="mt-3 text-3xl font-black">{{ $title }}</h1><p class="mt-2 text-sm font-bold text-slate-500">أدوات النمو مبنية من التسويق والتحليلات وواجهة المتجر.</p></div>
        <a href="{{ route('api.partner.services.growth.recommendations') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">Recommendations API</a>
    </div>
    <div class="grid gap-5 xl:grid-cols-2">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">الأدوات</h2>
            <div class="mt-4 space-y-3">
                @foreach ($growth['tools'] as $tool)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="font-black">{{ $tool['name'] }}</p><p class="mt-1 text-xs font-bold text-slate-500">{{ $tool['category'] ?? '-' }} · {{ $tool['impact'] ?? '-' }}</p></div>
                @endforeach
            </div>
        </section>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">توصيات النمو</h2>
            <div class="mt-4 space-y-3">
                @forelse ($growth['recommendations'] as $row)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><div class="flex justify-between gap-3"><p class="font-black">{{ $row['name'] }}</p><span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ $row['priority'] ?? '-' }}</span></div><p class="mt-1 text-xs font-bold text-slate-500">{{ $row['source'] ?? '-' }}</p></div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-5 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد توصيات.</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
