@extends('layouts.partner')

@section('title', 'طرق دفع الاشتراك - Solve Merchant')

@section('partner-content')
    <section class="space-y-6">
        <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <h1 class="text-3xl font-black">طرق الدفع</h1>
            <form class="mt-5 grid gap-3 md:grid-cols-4" method="POST" action="/api/partner/payment-methods" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:new FormData(this)}).then(()=>location.reload())">
                @csrf
                <input name="brand" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 font-bold dark:bg-slate-950" placeholder="Mada / Visa">
                <input name="number" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 font-bold dark:bg-slate-950" placeholder="رقم البطاقة" required>
                <input name="holder" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 font-bold dark:bg-slate-950" placeholder="اسم حامل البطاقة">
                <button class="rounded-2xl bg-solve-600 px-4 py-3 font-black text-white">إضافة</button>
            </form>
        </div>
        <div class="grid gap-3 md:grid-cols-3">
            @forelse ($subscriptionSuite['payment_methods'] as $method)
                <div class="rounded-3xl bg-white p-5 shadow-card dark:bg-slate-900">
                    <p class="text-lg font-black">{{ $method['brand'] }} **** {{ $method['last4'] }}</p>
                    <p class="mt-2 text-sm font-bold text-slate-500">{{ $method['holder'] }}</p>
                </div>
            @empty
                <div class="rounded-3xl bg-white p-10 text-center font-black text-slate-400 shadow-card dark:bg-slate-900 md:col-span-3">لا توجد طريقة دفع محفوظة.</div>
            @endforelse
        </div>
    </section>
@endsection
