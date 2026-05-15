@extends('layouts.site')

@section('title', 'تجهيز المتجر - Solve')

@section('content')
@php
    $done = collect($steps)->whereIn('status', ['completed', 'skipped'])->count();
    $percent = count($steps) ? round(($done / count($steps)) * 100) : 0;
@endphp
<main class="min-h-screen px-6 py-10 lg:px-10">
    <section class="mx-auto max-w-5xl rounded-[32px] bg-white p-8 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-black text-brand-600">Setup checklist</p>
                <h1 class="mt-2 text-4xl font-black text-slate-950">جهّز متجرك خطوة بخطوة</h1>
                <p class="mt-3 text-sm font-bold text-slate-500">يمكنك تخطي أي خطوة والعودة لها لاحقاً من الداشبورد.</p>
            </div>
            <a href="{{ route('partner.dashboard') }}" class="rounded-2xl bg-slate-950 px-6 py-3 text-sm font-black text-white">دخول الداشبورد</a>
        </div>
        <div class="mt-8">
            <div class="mb-2 flex justify-between text-xs font-black text-slate-500"><span>التقدم</span><span>{{ $percent }}%</span></div>
            <div class="h-3 rounded-full bg-slate-100"><div class="h-3 rounded-full bg-brand-600" style="width: {{ $percent }}%"></div></div>
        </div>
        <div class="mt-8 space-y-3">
            @foreach ($steps as $step)
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-slate-50 p-4">
                    <div>
                        <h2 class="font-black text-slate-950">{{ $step['title'] }}</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $step['description'] }}</p>
                    </div>
                    <form method="POST" action="/api/merchant/onboarding" onsubmit="event.preventDefault(); fetch(this.action,{method:'PATCH',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},body:JSON.stringify({step_key:'{{ $step['key'] }}',status:'completed'})}).then(()=>location.reload())">
                        <button class="rounded-xl bg-white px-4 py-2 text-xs font-black text-brand-700 shadow-sm">{{ $step['status'] === 'completed' ? 'مكتملة' : 'إنهاء' }}</button>
                    </form>
                </div>
            @endforeach
        </div>
        <form class="mt-6" method="POST" action="/api/merchant/onboarding/complete" onsubmit="event.preventDefault(); fetch(this.action,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}}).then(()=>location.href='{{ route('partner.dashboard') }}')">
            <button class="w-full rounded-2xl bg-brand-600 px-6 py-4 text-sm font-black text-white">إنهاء الإعداد ودخول الداشبورد</button>
        </form>
    </section>
</main>
@endsection
