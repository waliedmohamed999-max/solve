@extends('layouts.partner')

@section('title', 'فواتير الاشتراك - Solve Merchant')

@section('partner-content')
    <section class="rounded-[2rem] bg-white p-6 shadow-card dark:bg-slate-900">
        <h1 class="text-3xl font-black">فواتير الاشتراك</h1>
        <div class="mt-6 space-y-3">
            @forelse ($subscriptionSuite['invoices'] as $invoice)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4 dark:bg-slate-800">
                    <div><p class="font-black">{{ $invoice['id'] }}</p><p class="text-xs font-bold text-slate-400">{{ $invoice['issued_at'] ?? '-' }}</p></div>
                    <p class="font-black">{{ $invoice['amount'] }} {{ $invoice['currency'] ?? 'SAR' }}</p>
                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ $invoice['status'] }}</span>
                    <div class="flex flex-wrap gap-2">
                        <a href="/api/partner/invoices/{{ $invoice['id'] }}/pdf" class="rounded-xl bg-white px-3 py-2 text-xs font-black text-solve-700 shadow-sm dark:bg-slate-900">PDF</a>
                        @if (($invoice['status'] ?? '') !== 'paid')
                            <form method="POST" action="/api/partner/invoices/{{ $invoice['id'] }}/retry" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>location.reload())">@csrf<button class="rounded-xl bg-solve-600 px-3 py-2 text-xs font-black text-white">Retry</button></form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-3xl bg-slate-50 p-10 text-center font-black text-slate-400 dark:bg-slate-800">لا توجد فواتير اشتراك بعد.</div>
            @endforelse
        </div>
    </section>
@endsection
