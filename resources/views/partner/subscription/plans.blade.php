@extends('layouts.partner')

@section('title', 'الباقات - Solve Merchant')

@section('partner-content')
    @php $current = $subscriptionSuite['subscription']['plan_name']; @endphp
    <section class="space-y-6">
        <div class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
            <h1 class="text-3xl font-black">الباقات</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">قارن الباقات واختر حدود المنتجات والطلبات والخدمات المناسبة لمتجرك.</p>
        </div>
        <div class="grid gap-5 xl:grid-cols-3">
            @foreach ($subscriptionSuite['plans'] as $plan)
                <article class="rounded-[2rem] border bg-white p-6 shadow-card dark:bg-slate-900 {{ $plan['recommended'] ? 'border-solve-400' : 'border-slate-100 dark:border-slate-800' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-2xl font-black">{{ $plan['name'] }}</h2>
                            @if ($plan['recommended']) <p class="mt-2 text-xs font-black text-solve-600">الأكثر مناسبة للنمو</p> @endif
                        </div>
                        <p class="text-lg font-black text-solve-700">{{ $plan['price_label'] }}</p>
                    </div>
                    <div class="mt-5 space-y-2">
                        @foreach ($plan['features'] as $feature)
                            <p class="rounded-2xl bg-slate-50 px-4 py-2 text-sm font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $feature }}</p>
                        @endforeach
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-2">
                        @foreach ($plan['limits'] as $key => $limit)
                            <div class="rounded-2xl bg-slate-50 p-3 text-xs font-black text-slate-500 dark:bg-slate-800">{{ $key }}: <span class="text-slate-950 dark:text-white">{{ $limit }}</span></div>
                        @endforeach
                    </div>
                    @if ($plan['name'] === $current)
                        <button class="mt-6 w-full rounded-2xl bg-slate-100 px-4 py-3 text-sm font-black text-slate-500 dark:bg-slate-800">باقتك الحالية</button>
                    @elseif ($plan['enterprise'])
                        <a href="mailto:sales@solve.local" class="mt-6 block w-full rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-black">تواصل مع المبيعات</a>
                    @else
                        <form class="mt-6" method="POST" action="/api/partner/subscription/upgrade" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:new FormData(this)}).then(()=>location.href='{{ route('partner.subscription') }}')">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $plan['name'] }}">
                            <input type="hidden" name="cycle" value="monthly">
                            <button class="w-full rounded-2xl bg-solve-600 px-4 py-3 text-sm font-black text-white">اختيار الباقة</button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endsection
