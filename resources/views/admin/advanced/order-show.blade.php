@extends('layouts.admin')

@section('title', 'تفاصيل الطلب - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    @include('admin.components.confirm-dialog')

    <section class="space-y-6">
        @include('admin.components.data-toolbar', ['eyebrow' => 'Order Operations', 'title' => 'تفاصيل الطلب ' . $order['order_number']])

        <div class="grid gap-4 lg:grid-cols-4">
            @foreach ([
                ['label' => 'حالة الطلب', 'value' => $order['status']],
                ['label' => 'الدفع', 'value' => $order['payment_status']],
                ['label' => 'الشحن', 'value' => $order['shipping_status']],
                ['label' => 'الإجمالي', 'value' => $order['total']],
            ] as $card)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-card transition hover:-translate-y-1 hover:shadow-soft">
                    <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-2xl font-black text-slate-900">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-black text-slate-900">Order Timeline</h3>
                    <button class="rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white" @click="$dispatch('solve-toast', 'تم تحديث حالة الطلب')">تحديث الحالة</button>
                </div>
                <div class="mt-6 space-y-4">
                    @foreach ($order['timeline'] as $step)
                        <div class="flex gap-4 rounded-2xl border border-slate-100 p-4">
                            <span class="mt-1 h-3 w-3 rounded-full {{ $step['state'] === 'done' ? 'bg-emerald-500' : ($step['state'] === 'blocked' ? 'bg-rose-500' : 'bg-slate-300') }}"></span>
                            <div>
                                <p class="font-black text-slate-900">{{ $step['label'] }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $step['time'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                    <h3 class="text-xl font-black text-slate-900">الروابط التشغيلية</h3>
                    <div class="mt-5 grid gap-3 text-sm font-bold text-slate-600">
                        <p>العميل: {{ $order['linked']['customer'] }}</p>
                        <p>الشحنة: {{ $order['linked']['shipment'] }}</p>
                        <p>الدفع: {{ $order['linked']['payment'] }}</p>
                        <p>الفاتورة: {{ $order['invoice_id'] }}</p>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <button class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold" @click="$dispatch('solve-toast', 'تم تجهيز الفاتورة للطباعة')">طباعة فاتورة</button>
                        <button class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold" @click="$dispatch('solve-toast', 'تم تجهيز ملصق الشحن')">ملصق شحن</button>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                    <h3 class="text-xl font-black text-slate-900">حالات مخصصة</h3>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($order['custom_statuses'] as $status)
                            <button class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600 transition hover:bg-brand-600 hover:text-white">{{ $status }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                <h3 class="text-xl font-black text-slate-900">سجل تغييرات الحالة</h3>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">من</th><th class="p-3">إلى</th><th class="p-3">المستخدم</th><th class="p-3">التاريخ</th></tr></thead>
                        <tbody>
                            @foreach ($order['status_history'] as $change)
                                <tr class="border-t border-slate-100"><td class="p-3">{{ $change['from'] }}</td><td class="p-3 font-bold">{{ $change['to'] }}</td><td class="p-3">{{ $change['user'] }}</td><td class="p-3">{{ $change['date'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                <h3 class="text-xl font-black text-slate-900">ملاحظات داخلية</h3>
                <div class="mt-5 space-y-3">
                    @forelse ($order['internal_notes'] as $note)
                        <p class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-600">{{ $note }}</p>
                    @empty
                        @include('admin.components.empty-state', ['title' => 'لا توجد ملاحظات'])
                    @endforelse
                </div>
            </div>
        </div>
    </section>
@endsection
