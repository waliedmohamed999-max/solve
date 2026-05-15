@extends('layouts.partner')

@section('title', 'Solve Merchant | الطلب ' . $order['order_number'])

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <a href="{{ route('partner.orders') }}" class="text-sm font-black text-solve-600 dark:text-solve-300">العودة لقائمة الطلبات</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $order['order_number'] }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">{{ $order['customer'] }} · {{ $order['phone'] }} · {{ $order['store_id'] }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.orders.invoice', ['order' => $order['id']]) }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">طباعة فاتورة</a>
            <a href="{{ route('partner.orders.shipping-label', ['order' => $order['id']]) }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">طباعة بوليصة الشحن</a>
            <form method="POST" action="{{ route('partner.orders.status', ['order' => $order['id']]) }}" class="flex gap-2">
                @csrf
                <select name="status" class="rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-900 dark:text-white">
                    @foreach (\App\Support\PartnerOrders::ORDER_STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(($order['status_key'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">تحديث الحالة</button>
            </form>
        </div>
    </div>

    <section class="mt-6 grid gap-4 xl:grid-cols-[1fr_360px]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black text-slate-950 dark:text-white">تفاصيل الطلب</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach ([
                    'الحالة' => $order['status'],
                    'حالة الدفع' => $order['payment_status'],
                    'طريقة الدفع' => $order['payment_method'],
                    'الشحن' => $order['shipping_method'],
                    'حالة الشحن' => $order['shipping_status'],
                    'الإجمالي' => $order['total'],
                    'المصدر' => $order['source'],
                    'تاريخ الإنشاء' => $order['created_at'],
                ] as $label => $value)
                    <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                        <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                        <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <h3 class="mt-6 text-lg font-black text-slate-950 dark:text-white">المنتجات</h3>
            <div class="mt-3 overflow-hidden rounded-2xl border border-slate-100 dark:border-slate-800">
                <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950"><tr><th class="px-4 py-3">المنتج</th><th class="px-4 py-3">الكمية</th><th class="px-4 py-3">السعر</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($order['items'] as $item)
                            <tr><td class="px-4 py-3 font-bold">{{ $item['name'] ?? '-' }}</td><td class="px-4 py-3">{{ $item['qty'] ?? 1 }}</td><td class="px-4 py-3">{{ $item['price'] ?? $order['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h3 class="mt-6 text-lg font-black text-slate-950 dark:text-white">ملخص المبالغ</h3>
            <div class="mt-3 grid gap-3 md:grid-cols-4">
                @foreach ([
                    'قيمة المنتجات' => $order['subtotal'],
                    'الخصم' => $order['discount'],
                    'الضريبة' => $order['tax'],
                    'الشحن' => $order['shipping_fee'],
                ] as $label => $value)
                    <div class="rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950">
                        <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                        <p class="mt-1 text-sm font-black text-slate-900 dark:text-white">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
        </article>

        <aside class="space-y-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">Timeline</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($order['timeline'] as $event)
                        <div class="flex gap-3">
                            <span class="mt-1 h-3 w-3 rounded-full {{ ($event['state'] ?? '') === 'done' ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">{{ $event['label'] }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">{{ $event['time'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">ربط الطلب</h2>
                <div class="mt-4 space-y-2 text-sm font-bold">
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span>العميل</span><span>{{ $order['customer'] }}</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span>الدفع</span><span>{{ $order['payment_status'] }}</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span>الشحن</span><span>{{ $order['shipping_status'] }}</span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span>Store ID</span><span>{{ $order['store_id'] }}</span></div>
                </div>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">ملاحظات داخلية</h2>
                <form method="POST" action="{{ route('partner.orders.notes', ['order' => $order['id']]) }}" class="mt-4 space-y-3">
                    @csrf
                    <textarea name="note" rows="3" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="أضف ملاحظة لفريق المتجر"></textarea>
                    <button class="rounded-full bg-solve-700 px-5 py-2 text-sm font-black text-white">إضافة ملاحظة</button>
                </form>
                <div class="mt-4 space-y-2">
                    @forelse ($order['notes'] as $note)
                        <div class="rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-200">{{ $note['body'] }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-400">{{ $note['actor'] ?? '-' }} · {{ $note['created_at'] ?? '-' }}</p>
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 px-3 py-6 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد ملاحظات داخلية بعد.</div>
                    @endforelse
                </div>
            </article>

            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">سجل التعديلات</h2>
                <div class="mt-4 space-y-2">
                    @forelse ($order['change_log'] as $change)
                        <div class="rounded-xl bg-slate-50 px-3 py-3 text-sm font-bold dark:bg-slate-950">
                            <p class="text-slate-900 dark:text-white">{{ $change['action'] ?? '-' }}</p>
                            <p class="mt-1 text-xs text-slate-400">{{ $change['actor'] ?? '-' }} · {{ $change['time'] ?? '-' }}</p>
                        </div>
                    @empty
                        <div class="rounded-xl bg-slate-50 px-3 py-6 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">سيظهر هنا أي تعديل على الطلب.</div>
                    @endforelse
                </div>
            </article>
        </aside>
    </section>
</div>
@endsection
