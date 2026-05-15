@extends('layouts.admin')

@section('title', 'الفوترة - Solve Admin')

@section('admin-content')
    <section class="space-y-6">
        <div class="rounded-3xl bg-white p-6 shadow-card">
            <h1 class="text-3xl font-black text-slate-950">فواتير ومدفوعات الاشتراكات</h1>
            <div class="mt-5 grid gap-3 md:grid-cols-4">
                @foreach ($billing['summary'] as $label => $value)
                    <div class="rounded-2xl bg-slate-50 p-4"><p class="text-xs font-black text-slate-400">{{ $label }}</p><p class="mt-2 text-2xl font-black">{{ $value }}</p></div>
                @endforeach
            </div>
        </div>
        <div class="rounded-3xl bg-white p-6 shadow-card">
            @forelse ($billing['invoices'] as $invoice)
                <div class="flex items-center justify-between border-b border-slate-100 py-3">
                    <span class="font-black">{{ $invoice['store'] ?? $invoice['store_id'] }} / {{ $invoice['plan'] }}</span>
                    <span class="font-bold text-slate-500">{{ $invoice['amount'] }} {{ $invoice['currency'] ?? 'SAR' }} - {{ $invoice['status'] }}</span>
                </div>
            @empty
                <p class="py-10 text-center font-black text-slate-400">لا توجد فواتير اشتراك بعد.</p>
            @endforelse
        </div>
    </section>
@endsection
