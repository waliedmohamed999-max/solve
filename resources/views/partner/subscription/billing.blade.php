@extends('layouts.partner')

@section('title', 'الفوترة - Solve Merchant')

@section('partner-content')
    @php $subscription = $subscriptionSuite['subscription']; @endphp
    <section class="space-y-6">
        <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <h1 class="text-3xl font-black">الفوترة والتجديد</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">الباقة {{ $subscription['plan_name'] }}، التجديد {{ $subscription['renews_at'] ?? '-' }}.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <form method="POST" action="/api/partner/subscription/renew" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>location.reload())">@csrf<button class="rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">تجديد الآن</button></form>
                <form method="POST" action="/api/partner/subscription/cancel" onsubmit="event.preventDefault(); if(confirm('إلغاء الاشتراك؟')) fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>location.reload())">@csrf<button class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black text-slate-600">إلغاء الاشتراك</button></form>
            </div>
        </div>
        <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <h2 class="text-xl font-black">Billing alerts</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-black text-slate-400">Payment status</p>
                    <p class="mt-2 font-black">{{ $subscription['payment_status'] ?? '-' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-black text-slate-400">Auto renew</p>
                    <p class="mt-2 font-black">{{ $subscription['auto_renew'] ? 'Enabled' : 'Disabled' }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <p class="text-xs font-black text-slate-400">Last payment</p>
                    <p class="mt-2 font-black">{{ $subscription['last_payment'] ?? '-' }}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
