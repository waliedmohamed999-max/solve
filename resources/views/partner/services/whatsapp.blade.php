@extends('layouts.partner')

@section('title', $title . ' | Solve')

@section('partner-content')
@php $settings = $whatsapp['settings'] ?? []; @endphp
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div><div class="text-sm font-black text-solve-700"><a href="{{ route('partner.services') }}">الخدمات</a> / {{ $title }}</div><h1 class="mt-3 text-3xl font-black">{{ $title }}</h1><p class="mt-2 text-sm font-bold text-slate-500">WhatsApp Business وقوالب الرسائل وسجل الإرسال.</p></div>
        <a href="{{ route('api.partner.services.whatsapp') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>
    <div class="grid gap-5 xl:grid-cols-3">
        <form method="POST" action="{{ route('partner.services.whatsapp.settings') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
            @csrf
            <div class="grid gap-3">
                <input name="business_number" required value="{{ $settings['business_number'] ?? '' }}" placeholder="9665xxxxxxxx" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <input name="access_token" placeholder="{{ $settings['access_token_masked'] ?? 'Access token' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <textarea name="order_confirmation_template" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $settings['order_confirmation_template'] ?? '' }}</textarea>
                <textarea name="order_status_template" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $settings['order_status_template'] ?? '' }}</textarea>
                <textarea name="abandoned_cart_template" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $settings['abandoned_cart_template'] ?? '' }}</textarea>
                <textarea name="back_in_stock_template" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $settings['back_in_stock_template'] ?? '' }}</textarea>
                <div class="flex gap-2"><button class="rounded-full bg-solve-700 px-6 py-3 text-sm font-black text-white">حفظ</button><button form="wa-test" class="rounded-full border border-slate-200 px-6 py-3 text-sm font-black dark:border-slate-700">اختبار إرسال</button></div>
            </div>
        </form>
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">سجل الرسائل</h2>
            <div class="mt-4 space-y-3">
                @forelse ($whatsapp['logs'] as $log)
                    <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="font-black">{{ $log['name'] }}</p><p class="mt-1 text-xs font-bold text-slate-500">{{ $log['recipient'] ?? '-' }} · {{ $log['status'] ?? '-' }}</p></div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-5 text-center text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد رسائل.</div>
                @endforelse
            </div>
        </section>
    </div>
    <form id="wa-test" method="POST" action="{{ route('partner.services.whatsapp.test') }}">@csrf</form>
</div>
@endsection
