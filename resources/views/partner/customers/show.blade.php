@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $customer['name'])

@section('partner-content')
@php
    $canManageCustomers = \App\Support\PartnerTenantStore::can($partnerUser, 'view-customers');
@endphp

<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <a href="{{ route('partner.customers') }}" class="text-sm font-black text-solve-700 dark:text-solve-300">العملاء</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $customer['name'] }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">{{ $customer['email'] }} · {{ $customer['phone'] }} · {{ $partner['store_id'] }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="mailto:{{ $customer['email'] }}" class="rounded-full bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">إرسال رسالة</a>
            <a href="{{ route('partner.api.customers', ['customer' => $customer['id']]) }}" class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">API</a>
        </div>
    </section>

    <div class="mt-8 grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
        <section class="space-y-5">
            <div class="grid gap-3 md:grid-cols-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black text-slate-400">عدد الطلبات</p>
                    <p class="mt-2 text-2xl font-black">{{ $customer['orders_count'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black text-slate-400">إجمالي الإنفاق</p>
                    <p class="mt-2 text-2xl font-black">{{ $customer['total_spent'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black text-slate-400">متوسط الطلب</p>
                    <p class="mt-2 text-2xl font-black">{{ $customer['average_order_value'] }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-xs font-black text-slate-400">آخر نشاط</p>
                    <p class="mt-2 text-lg font-black">{{ $customer['last_activity'] }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">سجل الطلبات</h2>
                </div>
                @if (count($customer['orders']))
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                            <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                                <tr>
                                    <th class="px-4 py-4">رقم الطلب</th>
                                    <th class="px-4 py-4">الإجمالي</th>
                                    <th class="px-4 py-4">الحالة</th>
                                    <th class="px-4 py-4">التاريخ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($customer['orders'] as $order)
                                    <tr>
                                        <td class="px-4 py-4"><a class="font-black text-solve-700" href="{{ route('partner.orders.show', ['order' => $order['id']]) }}">{{ $order['order_number'] }}</a></td>
                                        <td class="px-4 py-4 font-black">{{ $order['total'] }}</td>
                                        <td class="px-4 py-4 font-bold">{{ $order['status'] }}</td>
                                        <td class="px-4 py-4 font-bold text-slate-500">{{ $order['created_at'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-10 text-center">
                        <h3 class="font-black text-slate-950 dark:text-white">لا توجد طلبات مرتبطة بهذا العميل</h3>
                        <p class="mt-2 text-sm font-bold text-slate-500">سيظهر سجل الطلبات تلقائيا عند إنشاء طلب بنفس بيانات العميل.</p>
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">Timeline</h2>
                <div class="mt-5 space-y-3">
                    @foreach ($customer['timeline'] as $event)
                        <div class="flex gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                            <span class="mt-1 h-2 w-2 rounded-full bg-solve-600"></span>
                            <div>
                                <p class="font-black text-slate-800 dark:text-slate-100">{{ $event['label'] }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $event['time'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <aside class="space-y-5">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">بيانات العميل</h2>
                <form method="POST" action="{{ route('partner.customers.update', ['customer' => $customer['id']]) }}" class="mt-4 grid gap-3">
                    @csrf
                    <input name="name" value="{{ $customer['name'] }}" required class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <input name="email" value="{{ $customer['email'] }}" type="email" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <input name="phone" value="{{ $customer['phone'] }}" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <input name="city" value="{{ $customer['city'] }}" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    <select name="status" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach (\App\Support\PartnerCustomers::CUSTOMER_STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected($customer['status_key'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input name="tags" value="{{ implode(', ', $customer['tags']) }}" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    @if ($canManageCustomers)
                        <button class="h-11 rounded-xl bg-solve-700 px-4 text-sm font-black text-white">حفظ التعديلات</button>
                    @endif
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">العناوين</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($customer['addresses'] as $address)
                        <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold dark:bg-slate-950">
                            <p class="font-black">{{ $address['label'] ?? 'عنوان' }}</p>
                            <p class="mt-1 text-slate-500">{{ $address['city'] ?? '-' }} · {{ $address['address'] ?? '-' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">الملاحظات الداخلية</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($customer['notes'] as $note)
                        <div class="rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-950">
                            <p class="font-bold text-slate-700 dark:text-slate-200">{{ $note['body'] }}</p>
                            <p class="mt-1 text-xs font-black text-slate-400">{{ $note['actor'] ?? 'Partner' }} · {{ $note['created_at'] ?? '' }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد ملاحظات داخلية.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('partner.customers.notes', ['customer' => $customer['id']]) }}" class="mt-4 grid gap-3">
                    @csrf
                    <textarea name="note" required rows="3" placeholder="اكتب ملاحظة لفريق العمل فقط" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white"></textarea>
                    <button class="h-11 rounded-xl border border-slate-200 px-4 text-sm font-black dark:border-slate-700">إضافة ملاحظة</button>
                </form>
            </div>
        </aside>
    </div>
</div>
@endsection
