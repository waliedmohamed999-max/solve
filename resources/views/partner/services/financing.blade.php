@extends('layouts.partner')

@section('title', $title . ' | Solve')

@section('partner-content')
@php $settings = $financing['settings'] ?? []; @endphp
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div><div class="text-sm font-black text-solve-700"><a href="{{ route('partner.services') }}">الخدمات</a> / {{ $title }}</div><h1 class="mt-3 text-3xl font-black">{{ $title }}</h1><p class="mt-2 text-sm font-bold text-slate-500">إعداد التمويل ومراجعة طلباته حسب متجر التاجر.</p></div>
        <a href="{{ route('api.partner.services.financing') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>
    <form method="POST" action="{{ route('partner.services.financing.settings') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <div class="grid gap-3 md:grid-cols-5">
            <input name="provider" required value="{{ $settings['provider'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            <input name="min_order_total" required type="number" value="{{ $settings['min_order_total'] ?? 0 }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            <input name="max_installments" required type="number" value="{{ $settings['max_installments'] ?? 4 }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            <label class="flex items-center gap-2 rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black dark:bg-slate-950"><input type="checkbox" name="enabled" value="1" @checked(! empty($settings['enabled']))> مفعلة</label>
            <button class="rounded-2xl bg-solve-700 px-4 py-3 text-sm font-black text-white">حفظ</button>
            <textarea name="terms" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 md:col-span-5">{{ $settings['terms'] ?? '' }}</textarea>
        </div>
    </form>
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-xl font-black">طلبات التمويل</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[760px] text-right text-sm">
                <thead><tr class="text-slate-400"><th class="p-3">الطلب</th><th class="p-3">العميل</th><th class="p-3">المبلغ</th><th class="p-3">الحالة</th><th class="p-3">إجراء</th></tr></thead>
                <tbody>
                    @forelse ($financing['requests'] as $row)
                        <tr class="border-t border-slate-100 dark:border-slate-800"><td class="p-3 font-black">{{ $row['name'] }}</td><td class="p-3">{{ $row['customer'] ?? '-' }}</td><td class="p-3">{{ $row['amount'] ?? '-' }}</td><td class="p-3">{{ $row['request_status'] ?? $row['status'] }}</td><td class="p-3"><form method="POST" action="{{ route('partner.services.financing.requests.status', ['record' => $row['id']]) }}">@csrf<input type="hidden" name="status" value="مكتملة"><button class="rounded-full bg-slate-950 px-4 py-2 text-xs font-black text-white dark:bg-white dark:text-slate-950">اعتماد</button></form></td></tr>
                    @empty
                        <tr><td colspan="5" class="p-8 text-center text-slate-500">لا توجد طلبات تمويل.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
