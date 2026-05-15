@extends('layouts.partner')

@section('title', 'Solve Merchant | العملاء')

@section('partner-content')
@php
    $filters = $customersPage['filters'];
    $pagination = $customersPage['pagination'];
    $statusColors = [
        'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
        'vip' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-200',
        'new' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-200',
        'inactive' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        'blocked' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200',
    ];
@endphp

<div class="px-4 py-6 lg:px-8" x-data="{ loading: false, error: '' }">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <div class="text-sm font-black text-solve-700 dark:text-solve-300">لوحة الشريك / العملاء</div>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">جميع العملاء</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">بيانات العملاء الحقيقية لمتجر {{ $partner['store_id'] }} مع العزل حسب المتجر والصلاحيات.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.customers.export', request()->query()) }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">تصدير CSV</a>
            <a href="{{ route('partner.api.customers', request()->query()) }}" class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">API</a>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('partner.customers') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center">
            <div class="relative w-full max-w-md">
                <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">@include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])</span>
                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="بحث بالاسم أو الجوال أو البريد"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pr-11 pl-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </div>
            <select name="status" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($customersPage['statusOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="city" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($customersPage['cityOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['city'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="orders" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($customersPage['orderOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['orders'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="h-11 rounded-full bg-slate-950 px-5 text-sm font-black text-white dark:bg-white dark:text-slate-950">تطبيق</button>
            <a href="{{ route('partner.customers') }}" class="flex h-11 items-center rounded-full border border-slate-200 px-5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">إعادة ضبط</a>
        </form>

        <div class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 sm:grid-cols-5">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">المعروض</p><p class="mt-1 text-xl font-black">{{ $customersPage['summary']['filtered'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">كل العملاء</p><p class="mt-1 text-xl font-black">{{ $customersPage['summary']['total'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">نشط</p><p class="mt-1 text-xl font-black">{{ $customersPage['summary']['active'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">VIP</p><p class="mt-1 text-xl font-black">{{ $customersPage['summary']['vip'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">جدد</p><p class="mt-1 text-xl font-black">{{ $customersPage['summary']['new'] }}</p></div>
        </div>

        <div class="border-b border-slate-100 px-4 py-3 text-sm font-black text-slate-500 dark:border-slate-800" x-show="loading">
            <div class="grid gap-2 md:grid-cols-4">
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
            </div>
        </div>
        <div class="border-b border-rose-100 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200" x-show="error" x-text="error" x-cloak></div>

        @if (count($customersPage['customers']))
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-4">العميل</th>
                            <th class="px-4 py-4">الجوال</th>
                            <th class="px-4 py-4">الطلبات</th>
                            <th class="px-4 py-4">إجمالي المشتريات</th>
                            <th class="px-4 py-4">آخر طلب</th>
                            <th class="px-4 py-4">الحالة</th>
                            <th class="px-4 py-4">المدينة</th>
                            <th class="px-4 py-4">Tags</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        @foreach ($customersPage['customers'] as $customer)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                <td class="px-4 py-4">
                                    <a href="{{ route('partner.customers.show', ['customer' => $customer['id']]) }}" class="font-black text-slate-950 hover:text-solve-700 dark:text-white">{{ $customer['name'] }}</a>
                                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $customer['email'] }}</p>
                                </td>
                                <td class="px-4 py-4 font-bold">{{ $customer['phone'] }}</td>
                                <td class="px-4 py-4 font-black">{{ $customer['orders_count'] }}</td>
                                <td class="px-4 py-4 font-black">{{ $customer['total_spent'] }}</td>
                                <td class="px-4 py-4 font-bold text-slate-600 dark:text-slate-300">{{ $customer['last_order'] }}</td>
                                <td class="px-4 py-4"><span class="{{ $statusColors[$customer['status_key']] ?? 'bg-slate-100 text-slate-700' }} rounded-full px-3 py-1 text-xs font-black">{{ $customer['status'] }}</span></td>
                                <td class="px-4 py-4 font-bold">{{ $customer['city'] }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($customer['tags'] as $tag)
                                            <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 p-4 text-sm font-black text-slate-500 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                <span>عرض {{ $pagination['from'] }} - {{ $pagination['to'] }} من {{ $pagination['total'] }}</span>
                <div class="flex gap-2">
                    @if ($pagination['page'] > 1)
                        <a href="{{ route('partner.customers', array_merge(request()->query(), ['page' => $pagination['page'] - 1])) }}" class="rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">السابق</a>
                    @endif
                    <span class="rounded-full bg-slate-100 px-4 py-2 dark:bg-slate-800">{{ $pagination['page'] }} / {{ $pagination['last_page'] }}</span>
                    @if ($pagination['page'] < $pagination['last_page'])
                        <a href="{{ route('partner.customers', array_merge(request()->query(), ['page' => $pagination['page'] + 1])) }}" class="rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">التالي</a>
                    @endif
                </div>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا يوجد عملاء مطابقون</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">غير الفلاتر أو انتظر أول طلب ليظهر العميل تلقائيا.</p>
            </div>
        @endif
    </section>
</div>
@endsection
