@extends('layouts.admin')

@section('title', 'كوبونات الباقات - Solve Admin')

@section('admin-content')
    <section class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-card">
            <h1 class="text-3xl font-black text-slate-950">كوبونات الباقات</h1>
            <form class="mt-5 grid gap-3 md:grid-cols-6" method="POST" action="/api/admin/coupons" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:new FormData(this)}).then(()=>location.reload())">
                @csrf
                <input name="code" class="rounded-2xl border border-slate-200 px-4 py-3 font-bold" placeholder="CODE" required>
                <select name="type" class="rounded-2xl border border-slate-200 px-4 py-3 font-bold"><option value="percent">نسبة</option><option value="fixed">مبلغ</option></select>
                <input name="value" type="number" class="rounded-2xl border border-slate-200 px-4 py-3 font-bold" placeholder="القيمة" required>
                <select name="plan" class="rounded-2xl border border-slate-200 px-4 py-3 font-bold"><option value="all">كل الباقات</option>@foreach($plans as $plan)<option value="{{ $plan['name'] }}">{{ $plan['name'] }}</option>@endforeach</select>
                <input name="uses_limit" type="number" value="100" class="rounded-2xl border border-slate-200 px-4 py-3 font-bold">
                <button class="rounded-2xl bg-brand-600 px-4 py-3 font-black text-white">إنشاء</button>
            </form>
        </div>
        <div class="grid gap-3 md:grid-cols-3">
            @forelse ($coupons as $coupon)
                <div class="rounded-3xl bg-white p-5 shadow-card">
                    <p class="text-xl font-black text-slate-950">{{ $coupon['code'] }}</p>
                    <p class="mt-2 text-sm font-bold text-slate-500">{{ $coupon['type'] }} / {{ $coupon['value'] }} / {{ $coupon['plan'] }}</p>
                    <p class="mt-3 rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500">{{ $coupon['status'] }}</p>
                </div>
            @empty
                <div class="rounded-3xl bg-white p-10 text-center font-black text-slate-400 shadow-card md:col-span-3">لا توجد كوبونات باقات بعد.</div>
            @endforelse
        </div>
    </section>
@endsection
