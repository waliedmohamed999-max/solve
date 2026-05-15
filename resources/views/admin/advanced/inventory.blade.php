@extends('layouts.admin')

@section('title', 'الفروع والمخزون - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')

    <section class="space-y-6">
        @include('admin.components.data-toolbar', ['eyebrow' => 'Inventory Control', 'title' => 'الفروع والمستودعات والمخزون'])

        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($inventory['branches'] as $branch)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-card transition hover:-translate-y-1 hover:shadow-soft">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-black text-slate-900">{{ $branch['name'] }}</h3>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $branch['status'] }}</span>
                    </div>
                    <p class="mt-3 text-sm font-bold text-slate-500">{{ $branch['type'] }}</p>
                    <p class="mt-5 text-3xl font-black text-slate-900">{{ number_format($branch['items']) }}</p>
                    <p class="text-sm text-slate-500">قطعة مسجلة</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-black text-slate-900">المخزون حسب الفرع</h3>
                    <button class="rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white" @click="$dispatch('solve-toast', 'تم تجهيز ملف التصدير')">تصدير Excel</button>
                </div>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">المنتج</th><th class="p-3">SKU</th><th class="p-3">الفرع</th><th class="p-3">الكمية</th><th class="p-3">الحالة</th></tr></thead>
                        <tbody>
                            @foreach ($inventory['stock'] as $row)
                                <tr class="border-t border-slate-100"><td class="p-3 font-bold">{{ $row['product'] }}</td><td class="p-3">{{ $row['sku'] }}</td><td class="p-3">{{ $row['branch'] }}</td><td class="p-3">{{ $row['stock'] }}</td><td class="p-3"><span class="rounded-full {{ $row['status'] === 'منخفض' ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' }} px-3 py-1 text-xs font-black">{{ $row['status'] }}</span></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                    <h3 class="text-xl font-black text-slate-900">تحويلات المخزون</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($inventory['transfers'] as $transfer)
                            <div class="rounded-2xl bg-slate-50 p-4 text-sm">
                                <p class="font-black text-slate-900">{{ $transfer['id'] }} - {{ $transfer['status'] }}</p>
                                <p class="mt-1 text-slate-500">{{ $transfer['from'] }} إلى {{ $transfer['to'] }} / {{ $transfer['items'] }} قطعة</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                    <h3 class="text-xl font-black text-slate-900">حركة المخزون</h3>
                    <div class="mt-4 space-y-3">
                        @foreach ($inventory['movements'] as $movement)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 text-sm">
                                <div><p class="font-black text-slate-900">{{ $movement['type'] }}</p><p class="text-slate-500">{{ $movement['reference'] }}</p></div>
                                <span class="font-black text-slate-900">{{ $movement['qty'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
