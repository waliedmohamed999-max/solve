@extends('layouts.partner')

@section('title', 'Solve Merchant | برنامج الولاء')

@section('partner-content')
@php $settings = $loyalty['settings'] ?? []; @endphp

<div class="px-4 py-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.marketing') }}" class="text-sm font-black text-solve-700 dark:text-solve-300">التسويق</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">برنامج الولاء</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">نقاط العملاء ومستوياتهم لمتجر {{ $partner['store_id'] }}.</p>
        </div>
        <a href="{{ route('api.partner.loyalty.index') }}" class="rounded-full border border-slate-200 px-4 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[0.75fr_1.25fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">إعدادات الولاء</h2>
            <form method="POST" action="{{ route('api.partner.loyalty.settings') }}" class="mt-5 grid gap-3">
                @csrf
                @method('PATCH')
                <label class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950">
                    <span>تفعيل الولاء</span>
                    <input name="enabled" value="1" type="checkbox" @checked($settings['enabled'] ?? false) class="rounded border-slate-300">
                </label>
                <input name="points_per_currency" type="number" min="1" value="{{ $settings['points_per_currency'] ?? 1 }}" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="point_value" type="number" step="0.01" min="0" value="{{ $settings['point_value'] ?? 0.1 }}" class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <button class="h-11 rounded-xl bg-solve-700 text-sm font-black text-white">حفظ الإعدادات</button>
            </form>
            <div class="mt-5 grid grid-cols-2 gap-2">
                @foreach (($settings['levels'] ?? ['عادي', 'فضي', 'ذهبي', 'VIP']) as $level)
                    <span class="rounded-xl bg-slate-50 p-3 text-center text-sm font-black dark:bg-slate-950">{{ $level }}</span>
                @endforeach
            </div>
        </section>

        <section class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-5 dark:border-slate-800"><h2 class="text-xl font-black">عملاء الولاء</h2></div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                        <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950"><tr><th class="px-4 py-4">العميل</th><th class="px-4 py-4">النقاط</th><th class="px-4 py-4">المستوى</th><th class="px-4 py-4">المستبدل</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($loyalty['customers'] as $row)
                                <tr><td class="px-4 py-4 font-black">{{ $row['customer'] ?? '-' }}</td><td class="px-4 py-4 font-bold">{{ $row['points'] ?? 0 }}</td><td class="px-4 py-4 font-bold">{{ $row['level'] ?? '-' }}</td><td class="px-4 py-4 font-bold">{{ $row['redeemed'] ?? 0 }}</td></tr>
                            @empty
                                <tr><td colspan="4" class="p-8 text-center font-bold text-slate-500">لا توجد بيانات.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-5 dark:border-slate-800"><h2 class="text-xl font-black">سجل النقاط</h2></div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($loyalty['transactions'] as $row)
                        <div class="flex items-center justify-between gap-3 p-4 text-sm">
                            <div><p class="font-black">{{ $row['customer'] ?? '-' }}</p><p class="text-xs font-bold text-slate-500">{{ $row['type'] ?? '-' }} · {{ $row['source'] ?? '-' }}</p></div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 font-black dark:bg-slate-800">{{ $row['points'] ?? 0 }} نقطة</span>
                        </div>
                    @empty
                        <div class="p-8 text-center text-sm font-bold text-slate-500">لا توجد حركات نقاط.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
