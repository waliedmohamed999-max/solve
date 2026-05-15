@extends('layouts.admin')

@section('title', 'إدارة طلبات المتاجر - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    @include('admin.components.confirm-dialog')

    @php
        $selectedStore = $ordersDashboard['selectedStore'] ?? null;
        $filters = $ordersDashboard['filters'];
    @endphp

    <section class="mt-6 space-y-6">
        <div class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-card">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-black text-brand-600">Solve Merchant Control</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $ordersDashboard['title'] }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">{{ $ordersDashboard['summary'] }}</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-black text-slate-600">
                        <span class="rounded-full bg-slate-50 px-3 py-2">إدارة الطلبات</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">Timeline</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">طباعة فاتورة</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">فلترة ذكية</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.orders') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">كل المتاجر</a>
                    @if ($selectedStore)
                        <a href="{{ route('admin.partners.show', ['partner' => $selectedStore['partner_id'] ?? \Illuminate\Support\Str::after($selectedStore['id'], 'store-')]) }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">فتح ملف الشريك</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-testid="admin-order-store-cards">
            @foreach ($ordersDashboard['stores'] as $store)
                <a href="{{ $store['url'] }}" class="rounded-[24px] border p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft {{ $store['active'] ? 'border-brand-200 bg-brand-50' : 'border-slate-100 bg-white' }}" data-testid="admin-order-store-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">{{ $store['name'] }}</h3>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $store['id'] }} · {{ $store['owner'] }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-brand-700">{{ $store['plan'] }}</span>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-2xl bg-white/80 p-3">
                            <p class="text-xs font-bold text-slate-500">الطلبات</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ $store['orders_count'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-3">
                            <p class="text-xs font-bold text-slate-500">مدفوع</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ $store['paid_count'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-3">
                            <p class="text-xs font-bold text-slate-500">متابعة</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ $store['pending_count'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs font-black">
                        <span class="text-slate-500">المبيعات: {{ $store['sales_total'] }}</span>
                        <span class="text-brand-700">عرض الطلبات</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($ordersDashboard['stats'] as $stat)
                <article class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                    <p class="text-sm font-bold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-xs font-black text-brand-600">{{ $stat['hint'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="rounded-[28px] border border-slate-100 bg-white p-5 shadow-card">
            <form method="GET" action="{{ route('admin.orders') }}" class="grid gap-3 lg:grid-cols-[1fr_180px_180px_160px]">
                <input type="hidden" name="store_id" value="{{ $filters['store_id'] }}">
                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="بحث برقم الطلب أو العميل أو المتجر" class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-300 focus:bg-white">
                <select name="status" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                    <option value="all">كل الحالات</option>
                    @foreach ($ordersDashboard['statusOptions'] as $option)
                        <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="payment_status" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                    <option value="all">كل المدفوعات</option>
                    @foreach ($ordersDashboard['paymentOptions'] as $option)
                        <option value="{{ $option }}" @selected($filters['payment_status'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <button class="h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white">تطبيق الفلتر</button>
            </form>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
            <section class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-card">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-950">{{ $selectedStore ? 'طلبات ' . $selectedStore['name'] : 'كل الطلبات' }}</h3>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ count($ordersDashboard['orders']) }} طلب معروض من أصل {{ $ordersDashboard['allOrdersCount'] }}</p>
                    </div>
                    <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2 text-sm font-black text-slate-600" @click="$dispatch('solve-toast', 'تم تجهيز تصدير الطلبات الحالية')">تصدير</button>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="p-4 font-black">المتجر</th>
                                <th class="p-4 font-black">الطلب</th>
                                <th class="p-4 font-black">العميل</th>
                                <th class="p-4 font-black">الحالة</th>
                                <th class="p-4 font-black">الدفع</th>
                                <th class="p-4 font-black">الشحن</th>
                                <th class="p-4 font-black">الإجمالي</th>
                                <th class="p-4 font-black">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($ordersDashboard['orders'] as $order)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="p-4 font-black text-slate-800">
                                        <a href="{{ route('admin.orders', ['store_id' => $order['store_id']]) }}" class="text-brand-700">{{ $order['store'] }}</a>
                                        <p class="mt-1 text-xs font-bold text-slate-400">{{ $order['store_id'] }}</p>
                                    </td>
                                    <td class="p-4 font-black text-slate-900">
                                        {{ $order['order_number'] }}
                                        @if (($order['admin_reference'] ?? '') !== ($order['order_number'] ?? ''))
                                            <p class="mt-1 text-xs font-bold text-slate-400">{{ $order['admin_reference'] }}</p>
                                        @endif
                                    </td>
                                    <td class="p-4 font-bold text-slate-700">{{ $order['customer'] }}</td>
                                    <td class="p-4"><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $order['status'] }}</span></td>
                                    <td class="p-4 font-bold text-slate-700">{{ $order['payment_status'] }}</td>
                                    <td class="p-4 font-bold text-slate-700">{{ $order['shipping_status'] }}</td>
                                    <td class="p-4 font-black text-slate-900">{{ $order['total'] }}</td>
                                    <td class="p-4">
                                        <a href="{{ route('admin.orders.show', ['order' => $order['id']]) }}" class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-black text-brand-700">تفاصيل</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-10 text-center text-sm font-black text-slate-500">لا توجد طلبات مطابقة لهذا المتجر أو الفلتر.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                    <h4 class="text-lg font-black text-slate-950">مركز تحكم الشريك</h4>
                    <p class="mt-2 text-sm leading-7 text-slate-500">اختر أي متجر من الأعلى لعرض ملخص طلباته، ثم افتح تفاصيل الطلب لمراجعة التايملاين، الدفع، الشحن، والفاتورة.</p>
                </div>
                <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                    <h4 class="text-lg font-black text-slate-950">روابط الإدارة</h4>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('admin.stores') }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">إدارة المتاجر</a>
                        <a href="{{ route('admin.payments') }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">المدفوعات والفواتير</a>
                        <a href="{{ route('admin.shipping') }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">الشحنات</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection
