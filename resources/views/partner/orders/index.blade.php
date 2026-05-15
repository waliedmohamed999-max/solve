@extends('layouts.partner')

@section('title', 'Solve Merchant | قائمة الطلبات')

@section('partner-content')
@php
    $filters = $ordersPage['filters'];
    $pagination = $ordersPage['pagination'];
    $canManageOrders = \App\Support\PartnerTenantStore::can($partnerUser, 'view-orders');
    $statusColors = [
        'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
        'cancelled' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200',
        'delivery' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        'processing' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200',
        'new' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-200',
    ];
@endphp

<div class="px-4 py-6 lg:px-8" x-data="{ loading: false, error: '', selectAll: false }">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-950 dark:text-white">قائمة الطلبات</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">جميع طلبات متجرك هنا · {{ $partner['store_id'] }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.orders.manual') }}" class="rounded-full bg-solve-700 px-6 py-3 text-sm font-black text-white hover:bg-solve-800">إنشاء</a>
            <a href="{{ route('partner.orders.export', request()->query()) }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">تصدير الطلبات</a>
            <a href="{{ route('partner.api.orders', request()->query()) }}" class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">API</a>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex gap-4 overflow-x-auto border-b border-slate-100 px-4 py-4 text-sm font-black dark:border-slate-800">
            @foreach ($ordersPage['statusOptions'] as $key => $label)
                <a href="{{ route('partner.orders', array_merge(request()->except('status'), ['status' => $key])) }}"
                    class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-2 {{ ($filters['status'] === $key || ($filters['status'] === '' && $key === 'all')) ? 'bg-slate-100 text-solve-700 dark:bg-slate-800 dark:text-solve-200' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <span>{{ $label }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-700 dark:text-slate-300">{{ $ordersPage['counts'][$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('partner.orders') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <div class="relative w-full max-w-md">
                <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">@include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])</span>
                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="بحث برقم الطلب أو العميل أو الجوال"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pr-11 pl-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </div>
            <select name="payment_status" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($ordersPage['paymentOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['payment_status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="shipping_status" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($ordersPage['shippingOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['shipping_status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <input name="date_from" value="{{ $filters['date_from'] }}" type="date" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <input name="date_to" value="{{ $filters['date_to'] }}" type="date" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            <button class="h-11 rounded-full bg-slate-950 px-5 text-sm font-black text-white dark:bg-white dark:text-slate-950">تطبيق</button>
            <a href="{{ route('partner.orders') }}" class="flex h-11 items-center rounded-full border border-slate-200 px-5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">إعادة ضبط</a>
        </form>

        <div class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 sm:grid-cols-3">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">المعروض</p><p class="mt-1 text-xl font-black">{{ $ordersPage['summary']['filtered'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">إجمالي القيمة</p><p class="mt-1 text-xl font-black">{{ $ordersPage['summary']['total_sales'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">تحتاج متابعة</p><p class="mt-1 text-xl font-black">{{ $ordersPage['summary']['pending'] }}</p></div>
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

        @if (count($ordersPage['orders']))
            <form method="POST" action="{{ route('partner.orders.bulk') }}" onsubmit="return confirm('تأكيد تطبيق الإجراء على الطلبات المحددة؟')">
                @csrf
                @if ($canManageOrders)
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                        <select name="bulk_status" class="h-10 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            @foreach (\App\Support\PartnerOrders::ORDER_STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <button class="h-10 rounded-full bg-solve-700 px-5 text-sm font-black text-white hover:bg-solve-800">تطبيق Bulk Action</button>
                        <span class="text-xs font-bold text-slate-400">حدد الطلبات من الجدول ثم نفذ الإجراء.</span>
                    </div>
                @endif
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-4"><input type="checkbox" x-model="selectAll" class="rounded border-slate-300"></th>
                            <th class="px-4 py-4">رقم الطلب<br><span class="font-bold text-slate-400">المصدر</span></th>
                            <th class="px-4 py-4">العميل<br><span class="font-bold text-slate-400">الجوال</span></th>
                            <th class="px-4 py-4">الدفع</th>
                            <th class="px-4 py-4">حالة الدفع</th>
                            <th class="px-4 py-4">الشحن</th>
                            <th class="px-4 py-4">المجموع<br><span class="font-bold text-slate-400">العملة</span></th>
                            <th class="px-4 py-4">الحالة</th>
                            <th class="px-4 py-4">تاريخ الإنشاء</th>
                            <th class="px-4 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        @foreach ($ordersPage['orders'] as $order)
                            @php $statusKey = $order['status_key'] ?? 'new'; @endphp
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                <td class="px-4 py-4"><input name="order_ids[]" value="{{ $order['id'] }}" type="checkbox" :checked="selectAll" class="rounded border-slate-300"></td>
                                <td class="px-4 py-4">
                                    <a href="{{ route('partner.orders.show', ['order' => $order['id']]) }}" class="font-black text-slate-950 hover:text-solve-700 dark:text-white">{{ $order['order_number'] }}</a>
                                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $order['source'] }}</p>
                                </td>
                                <td class="px-4 py-4 font-bold text-slate-700 dark:text-slate-300">
                                    {{ $order['customer'] }}
                                    <p class="mt-1 text-xs text-slate-500">{{ $order['phone'] }}</p>
                                </td>
                                <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $order['payment_method'] }}</span></td>
                                <td class="px-4 py-4"><span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ $order['payment_status'] }}</span></td>
                                <td class="px-4 py-4 font-bold">{{ $order['shipping_method'] }}</td>
                                <td class="px-4 py-4 font-black">{{ $order['total'] }}<p class="text-xs font-bold text-slate-500">{{ $order['currency'] }}</p></td>
                                <td class="px-4 py-4"><span class="{{ $statusColors[$statusKey] ?? 'bg-slate-100 text-slate-700' }} rounded-full px-3 py-1 text-xs font-black">{{ $order['status'] }}</span></td>
                                <td class="px-4 py-4 font-bold text-slate-600 dark:text-slate-300">{{ $order['created_at'] }}<p class="text-xs text-slate-400">{{ $order['updated_at_human'] }}</p></td>
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('partner.orders.show', ['order' => $order['id']]) }}" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">فتح</a>
                                        <a href="{{ route('partner.orders.invoice', ['order' => $order['id']]) }}" class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">فاتورة</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </form>
            <div class="flex flex-col gap-3 border-t border-slate-100 p-4 text-sm font-black text-slate-500 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                <span>عرض {{ $pagination['from'] }} - {{ $pagination['to'] }} من {{ $pagination['total'] }}</span>
                <div class="flex gap-2">
                    @if ($pagination['page'] > 1)
                        <a href="{{ route('partner.orders', array_merge(request()->query(), ['page' => $pagination['page'] - 1])) }}" class="rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">السابق</a>
                    @endif
                    <span class="rounded-full bg-slate-100 px-4 py-2 dark:bg-slate-800">{{ $pagination['page'] }} / {{ $pagination['last_page'] }}</span>
                    @if ($pagination['page'] < $pagination['last_page'])
                        <a href="{{ route('partner.orders', array_merge(request()->query(), ['page' => $pagination['page'] + 1])) }}" class="rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">التالي</a>
                    @endif
                </div>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد طلبات مطابقة</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">غيّر الفلاتر أو أنشئ طلباً يدوياً جديداً.</p>
                <a href="{{ route('partner.orders.manual') }}" class="mt-5 inline-flex rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إنشاء طلب</a>
            </div>
        @endif
    </section>
</div>
@endsection
