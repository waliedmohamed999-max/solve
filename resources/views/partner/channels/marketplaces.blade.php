@extends('layouts.partner')

@section('title', 'منصات البيع | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.channels') }}">القنوات</a><span>/</span><span>منصات البيع</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">منصات البيع</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">Amazon وNoon وTikTok وInstagram وFacebook مربوطة بمزامنة المنتجات والطلبات.</p>
        </div>
        <a href="{{ route('api.partner.channels.marketplaces') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @foreach ($marketplaces['rows'] as $row)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="text-xl font-black">{{ $row['name'] }}</h2><p class="mt-1 text-sm font-bold text-slate-500">Seller: {{ $row['seller_id'] ?? '-' }}</p></div>
                    <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ $row['status'] }}</span>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">آخر مزامنة</p><p class="mt-1 text-sm font-black">{{ $row['last_sync_at'] ?? '-' }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">منتجات</p><p class="mt-1 text-sm font-black">{{ $row['products_synced'] ?? 0 }}</p></div>
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">طلبات</p><p class="mt-1 text-sm font-black">{{ $row['orders_synced'] ?? 0 }}</p></div>
                </div>
                <form method="POST" action="{{ route('partner.channels.marketplaces.settings', ['marketplace' => $row['id']]) }}" class="mt-5 grid gap-3">
                    @csrf
                    <input name="seller_id" value="{{ $row['seller_id'] ?? '' }}" placeholder="Seller ID" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    <input name="api_key" placeholder="{{ $row['api_key_masked'] ?? 'API Key' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        @foreach (\App\Support\PartnerChannels::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(($row['status_key'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="flex flex-wrap gap-2">
                        <button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">حفظ الربط</button>
                        <button form="sync-products-{{ $row['id'] }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">مزامنة المنتجات</button>
                        <button form="sync-orders-{{ $row['id'] }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">مزامنة الطلبات</button>
                    </div>
                </form>
                <form id="sync-products-{{ $row['id'] }}" method="POST" action="{{ route('partner.channels.marketplaces.sync-products', ['marketplace' => $row['id']]) }}">@csrf</form>
                <form id="sync-orders-{{ $row['id'] }}" method="POST" action="{{ route('partner.channels.marketplaces.sync-orders', ['marketplace' => $row['id']]) }}">@csrf</form>
            </article>
        @endforeach
    </div>
</div>
@endsection
