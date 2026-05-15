@extends('layouts.admin')

@section('title', 'Solve Admin | Onboarding')

@section('admin-content')
<section class="mt-6 rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
    <p class="text-sm font-bold text-brand-600">Store Onboarding</p>
    <h2 class="mt-2 text-3xl font-extrabold text-slate-900">تجهيز متجر جديد</h2>
    <p class="mt-3 text-sm leading-8 text-slate-500">مسار تشغيل تدريجي: بيانات المتجر، الدفع، الشحن، أول منتج، وربط الدومين.</p>
</section>

<section class="mt-6 grid gap-4 lg:grid-cols-5">
    @foreach ($steps as $step)
        @php $data = is_array($step) ? $step : $step->toArray(); @endphp
        <div class="rounded-[28px] border border-white/70 bg-white p-5 shadow-card">
            <span class="rounded-2xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-700">{{ $data['status'] }}</span>
            <h3 class="mt-5 text-xl font-extrabold text-slate-900">{{ $data['title'] }}</h3>
            <p class="mt-3 text-sm leading-7 text-slate-500">Store ID: {{ $storeId }}</p>
        </div>
    @endforeach
</section>
@endsection
