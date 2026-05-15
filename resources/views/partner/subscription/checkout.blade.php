@extends('layouts.partner')

@section('title', 'Checkout - Solve Merchant')

@section('partner-content')
<section class="space-y-6">
    <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
        <p class="text-sm font-black text-solve-600">Checkout</p>
        <h1 class="mt-2 text-3xl font-black">ترقية الباقة إلى {{ $checkoutPlan['name'] }}</h1>
        <p class="mt-2 text-sm font-bold text-slate-500">راجع الباقة، اختر دورة الدفع، ثم أكمل الدفع. سيتم فتح المميزات مباشرة بعد نجاح الدفع.</p>
    </div>
    <form class="grid gap-5 lg:grid-cols-[1fr_0.8fr]" method="POST" action="/api/partner/subscription/checkout" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify(Object.fromEntries(new FormData(this)))}).then(r=>r.json()).then(()=>location.href='{{ route('partner.subscription.billing') }}')">
        @csrf
        <input type="hidden" name="plan" value="{{ $checkoutPlan['name'] }}">
        <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <h2 class="text-xl font-black">بيانات الدفع</h2>
            <div class="mt-5 grid gap-3">
                <label class="rounded-2xl border border-slate-100 p-4">
                    <input type="radio" name="cycle" value="monthly" checked>
                    <span class="mr-2 font-black">شهري - {{ $checkoutPlan['price_label'] }}</span>
                </label>
                <label class="rounded-2xl border border-slate-100 p-4">
                    <input type="radio" name="cycle" value="yearly">
                    <span class="mr-2 font-black">سنوي - {{ $checkoutPlan['yearly_price_label'] }}</span>
                </label>
                <input name="coupon" placeholder="كود الخصم" class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold">
            </div>
        </div>
        <aside class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <h2 class="text-xl font-black">ملخص الفاتورة</h2>
            <div class="mt-4 space-y-3 text-sm font-bold text-slate-600">
                <div class="flex justify-between"><span>الباقة</span><span>{{ $checkoutPlan['name'] }}</span></div>
                <div class="flex justify-between"><span>العملة</span><span>SAR</span></div>
                <div class="flex justify-between"><span>الحالة</span><span>Pending until webhook confirms payment</span></div>
            </div>
            <button class="mt-6 w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">الدفع وتفعيل الباقة</button>
            <a href="{{ route('partner.subscription.plans') }}" class="mt-3 block text-center text-xs font-black text-slate-400">مقارنة الباقات</a>
        </aside>
    </form>
</section>
@endsection
