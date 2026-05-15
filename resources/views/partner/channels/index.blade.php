@extends('layouts.partner')

@section('title', 'القنوات | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.dashboard') }}">لوحة التحكم</a><span>/</span><span>القنوات</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">قنوات البيع</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">كل قناة مرتبطة بمتجر {{ $channels['store_id'] }} وبالطلبات والمنتجات والباقة والصلاحيات.</p>
        </div>
        <a href="{{ route('api.partner.channels.index') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
    </div>

    <form method="GET" action="{{ route('partner.channels') }}" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-4">
        <input name="q" value="{{ $channels['filters']['q'] }}" placeholder="بحث عن قناة" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @foreach ($channels['statusOptions'] as $key => $label)
                <option value="{{ $key }}" @selected($channels['filters']['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">فلترة</button>
        <a href="{{ route('partner.channels') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-black dark:border-slate-700">إعادة ضبط</a>
    </form>

    <div class="grid gap-4 md:grid-cols-4">
        @foreach ($channels['counts'] as $label => $value)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-3xl font-black">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse ($channels['channels'] as $channel)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black">{{ $channel['name'] }}</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $channel['provider'] ?? '-' }} · {{ $channel['plan'] ?? 'Starter' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ ($channel['status_key'] ?? '') === 'enabled' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $channel['status'] }}</span>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">آخر مزامنة</p><p class="mt-1 text-sm font-black">{{ $channel['last_sync_at'] ?? '-' }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">منتجات متزامنة</p><p class="mt-1 text-sm font-black">{{ $channel['products_synced'] ?? 0 }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">طلبات القناة</p><p class="mt-1 text-sm font-black">{{ $channel['orders_synced'] ?? 0 }}</p></div>
                </div>
                <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-500 dark:bg-slate-950">{{ $channel['help_tip'] ?? 'أكمل إعداد القناة.' }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ match($channel['id']) { 'storefront' => route('partner.channels.storefront'), 'marketplaces' => route('partner.channels.marketplaces'), 'mobile-app' => route('partner.channels.mobile-app'), default => route('partner.channels.pos') } }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إعداد القناة</a>
                    <form method="POST" action="{{ route('partner.channels.sync', ['channel' => $channel['id']]) }}">@csrf<button class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">مزامنة</button></form>
                    @if (($channel['status_key'] ?? '') !== 'admin_paused')
                        <form method="POST" action="{{ route('partner.channels.status', ['channel' => $channel['id']]) }}" onsubmit="return confirm('تأكيد تغيير حالة القناة؟')">@csrf<input type="hidden" name="status" value="{{ ($channel['status_key'] ?? '') === 'enabled' ? 'disabled' : 'enabled' }}"><button class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">{{ ($channel['status_key'] ?? '') === 'enabled' ? 'تعطيل' : 'تفعيل' }}</button></form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl bg-white p-10 text-center text-sm font-bold text-slate-500 dark:bg-slate-900 xl:col-span-2">لا توجد قنوات متاحة لهذه الباقة.</div>
        @endforelse
    </div>
</div>
@endsection
