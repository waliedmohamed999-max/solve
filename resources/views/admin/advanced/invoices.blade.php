@extends('layouts.admin')

@section('title', 'الفواتير والضرائب - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')

    <section class="space-y-6">
        @include('admin.components.data-toolbar', ['eyebrow' => 'Billing & VAT', 'title' => 'الفواتير والضرائب'])

        <div class="grid gap-4 lg:grid-cols-4">
            @foreach ($invoices['settings'] as $label => $value)
                <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-card">
                    <p class="text-sm font-bold text-slate-500">{{ $label }}</p>
                    <p class="mt-3 text-2xl font-black text-slate-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl border border-slate-100 bg-white p-6 shadow-card">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-black text-slate-900">سجل الفواتير</h3>
                <button class="rounded-2xl bg-brand-600 px-4 py-2 text-sm font-bold text-white" @click="$dispatch('solve-toast', 'تم توليد الفاتورة التسلسلية التالية')">إنشاء فاتورة</button>
            </div>
            <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                <table class="w-full text-right text-sm">
                    <thead class="bg-slate-50 text-slate-500"><tr><th class="p-3">رقم الفاتورة</th><th class="p-3">الطلب</th><th class="p-3">العميل</th><th class="p-3">VAT</th><th class="p-3">الإجمالي</th><th class="p-3">إجراءات</th></tr></thead>
                    <tbody>
                        @foreach ($invoices['records'] as $invoice)
                            <tr class="border-t border-slate-100">
                                <td class="p-3 font-black">{{ $invoice['id'] }}</td><td class="p-3">{{ $invoice['order'] }}</td><td class="p-3">{{ $invoice['customer'] }}</td><td class="p-3">{{ $invoice['vat'] }}</td><td class="p-3">{{ $invoice['total'] }}</td>
                                <td class="p-3"><button class="rounded-xl bg-slate-100 px-3 py-2 text-xs font-black" @click="$dispatch('solve-toast', 'تم تجهيز PDF وإرسال الفاتورة')">PDF / إرسال</button></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
