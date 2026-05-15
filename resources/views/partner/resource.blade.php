@extends('layouts.partner')

@section('title', 'Solve Partner | ' . $resourceTitle)

@section('partner-content')
@php
    $columns = [
        'orders' => ['id' => 'رقم الطلب', 'customer' => 'العميل', 'status' => 'الحالة', 'amount' => 'القيمة', 'date' => 'التاريخ'],
        'products' => ['sku' => 'SKU', 'name' => 'المنتج', 'stock' => 'المخزون', 'price' => 'السعر', 'status' => 'الحالة'],
        'customers' => ['name' => 'العميل', 'email' => 'البريد', 'orders' => 'الطلبات', 'spent' => 'الإنفاق'],
        'payments' => ['id' => 'رقم العملية', 'gateway' => 'البوابة', 'status' => 'الحالة', 'amount' => 'القيمة', 'settlement' => 'التسوية'],
    ][$resourceKey] ?? [];
@endphp

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <p class="text-sm font-bold text-brand-600">Store Scoped Data</p>
            <h1 class="mt-2 text-3xl font-extrabold text-slate-900">{{ $resourceTitle }}</h1>
            <p class="mt-3 max-w-3xl text-sm leading-8 text-slate-500">{{ $resourceDescription }} البيانات المعروضة تخص {{ $partner['name'] }} فقط.</p>
        </div>
        <span class="rounded-2xl bg-brand-50 px-4 py-3 text-sm font-bold text-brand-700">Store ID: {{ $partner['store_id'] }}</span>
    </div>
</section>

<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <input type="text" placeholder="بحث سريع داخل الجدول" class="w-full max-w-xl rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-brand-300 focus:bg-white">
        <div class="flex gap-3">
            <span class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold text-slate-600">{{ count($rows) }} سجل</span>
            <span class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-700">معزول حسب المتجر</span>
        </div>
    </div>

    <div class="mt-6 overflow-hidden rounded-[28px] border border-slate-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-right">
                <thead class="bg-slate-50 text-xs font-extrabold uppercase tracking-wide text-slate-500">
                    <tr>
                        @foreach ($columns as $label)
                            <th class="px-5 py-4">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-sm">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-slate-50/70">
                            @foreach (array_keys($columns) as $key)
                                <td class="px-5 py-4 font-bold text-slate-700">{{ $row[$key] ?? '-' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(count($columns), 1) }}" class="px-5 py-12 text-center text-sm font-bold text-slate-500">لا توجد بيانات حتى الآن.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
