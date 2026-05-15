@extends('layouts.partner')

@section('title', 'نقاط البيع POS | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.channels') }}">القنوات</a><span>/</span><span>POS</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">نقاط البيع POS</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">مبيعات الفروع والكاشير والمخزون والمرتجعات مرتبطة بالطلبات والمالية.</p>
        </div>
        <a href="{{ route('api.partner.channels.pos') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
    </div>

    <section class="grid gap-4 md:grid-cols-4">
        @foreach ($pos['reports'] as $label => $value)
            @continue($label === 'store_id')
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-2xl font-black">{{ is_numeric($value) && $label === 'sales' ? number_format($value) . ' ر.س' : $value }}</p>
            </div>
        @endforeach
    </section>

    <form method="POST" action="{{ route('partner.channels.pos.settings') }}" class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-4">
        @csrf
        <label class="grid gap-2 text-sm font-black md:col-span-2">اسم الفرع<input name="branch_name" value="{{ $pos['settings']['branch_name'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950"><input type="checkbox" name="enabled" value="1" @checked($pos['settings']['enabled'] ?? false)> تفعيل POS</label>
        <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950"><input type="checkbox" name="sync_inventory" value="1" @checked($pos['settings']['sync_inventory'] ?? true)> مزامنة المخزون</label>
        <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950"><input type="checkbox" name="allow_returns" value="1" @checked($pos['settings']['allow_returns'] ?? true)> المرتجعات والاستبدال</label>
        <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950 md:col-span-3">حفظ إعدادات POS</button>
    </form>

    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="text-xl font-black">أجهزة البيع والكاشير</h2>
            <form method="POST" action="{{ route('partner.channels.pos.devices.store') }}" class="flex flex-wrap gap-2">@csrf<input name="name" required placeholder="اسم الجهاز" class="rounded-full border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"><input name="cashier" placeholder="الكاشير" class="rounded-full border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"><button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إضافة</button></form>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-right dark:divide-slate-800">
                <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950"><tr><th class="px-4 py-3">الجهاز</th><th class="px-4 py-3">الكاشير</th><th class="px-4 py-3">الفرع</th><th class="px-4 py-3">الحالة</th><th class="px-4 py-3">آخر مزامنة</th></tr></thead>
                <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    @foreach ($pos['devices'] as $device)
                        <tr><td class="px-4 py-3 font-black">{{ $device['name'] }}</td><td class="px-4 py-3">{{ $device['cashier'] ?? '-' }}</td><td class="px-4 py-3">{{ $device['branch'] ?? '-' }}</td><td class="px-4 py-3">{{ $device['status'] }}</td><td class="px-4 py-3">{{ $device['last_sync_at'] ?? '-' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
