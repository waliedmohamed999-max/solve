@extends('layouts.admin')

@section('title', 'ملف العميل - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')

    <section class="space-y-6">
        @include('admin.components.data-toolbar', ['eyebrow' => 'CRM Profile', 'title' => 'ملف العميل: ' . $customer['name']])

        <div class="grid gap-4 lg:grid-cols-4">
            @foreach ([
                ['label' => 'إجمالي الإنفاق', 'value' => $customer['total_spent']],
                ['label' => 'عدد الطلبات', 'value' => $customer['orders_count']],
                ['label' => 'متوسط الطلب', 'value' => $customer['average_order_value']],
                ['label' => 'التصنيف', 'value' => $customer['segment']],
            ] as $card)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-card">
                    <p class="text-sm font-bold text-slate-500">{{ $card['label'] }}</p>
                    <p class="mt-3 text-2xl font-black text-slate-900">{{ $card['value'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                <h3 class="text-xl font-black text-slate-900">بيانات التواصل</h3>
                <div class="mt-5 space-y-3 text-sm font-bold text-slate-600">
                    <p>البريد: {{ $customer['email'] }}</p>
                    <p>الهاتف: {{ $customer['phone'] }}</p>
                    <p>الحالة: {{ $customer['status'] }}</p>
                    <p>آخر طلب: {{ $customer['last_order'] }}</p>
                </div>
                <div class="mt-6 flex flex-wrap gap-2">
                    @foreach ($customer['notification_channels'] as $channel)
                        <button class="rounded-2xl bg-brand-50 px-4 py-2 text-sm font-black text-brand-700" @click="$dispatch('solve-toast', 'تم تجهيز رسالة عبر {{ $channel }}')">{{ $channel }}</button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
                <h3 class="text-xl font-black text-slate-900">سجل الطلبات</h3>
                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">رقم الطلب</th><th class="p-3">المتجر</th><th class="p-3">الحالة</th><th class="p-3">الإجمالي</th></tr></thead>
                        <tbody>
                            @foreach ($customer['order_history'] as $order)
                                <tr class="border-t border-slate-100"><td class="p-3 font-black">{{ $order['order_number'] }}</td><td class="p-3">{{ $order['store'] }}</td><td class="p-3">{{ $order['status'] }}</td><td class="p-3">{{ $order['total'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
            <h3 class="text-xl font-black text-slate-900">ملاحظات داخلية</h3>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($customer['internal_notes'] as $note)
                    <p class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-600">{{ $note }}</p>
                @endforeach
            </div>
        </div>
    </section>
@endsection
