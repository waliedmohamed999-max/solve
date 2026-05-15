@extends('layouts.admin')

@section('title', 'Solve Admin | ' . $partner['name'])

@section('admin-content')
@php
    $statusClass = [
        'نشط' => 'bg-emerald-50 text-emerald-700',
        'موقوف' => 'bg-rose-50 text-rose-700',
        'تحت المراجعة' => 'bg-amber-50 text-amber-700',
    ];
@endphp

<section class="mt-6 overflow-hidden rounded-[32px] border border-white/70 bg-white shadow-card">
    <div class="bg-gradient-to-l from-brand-700 via-brand-600 to-sky-500 p-7 text-white">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ asset($partner['logo']) }}" alt="{{ $partner['name'] }}" class="h-20 w-20 rounded-[24px] border border-white/30 bg-white object-contain p-2">
                <div>
                    <p class="text-sm font-bold text-brand-100">Partner Tenant</p>
                    <h2 class="mt-2 text-4xl font-extrabold">{{ $partner['name'] }}</h2>
                    <a href="{{ $partner['store_url'] }}" target="_blank" rel="noopener noreferrer" class="mt-2 inline-block text-sm font-bold text-white/80">{{ $partner['store_url'] }}</a>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <span class="rounded-2xl bg-white/15 px-4 py-3 text-sm font-bold">{{ $partner['plan'] }}</span>
                <span class="rounded-2xl bg-white px-4 py-3 text-sm font-extrabold text-brand-700">{{ $partner['status'] }}</span>
            </div>
        </div>
    </div>

    <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
        @foreach (collect($dashboard['kpis'])->take(8) as $metric)
            <div class="rounded-[26px] bg-slate-50 p-5">
                <p class="text-sm font-bold text-slate-500">{{ $metric['label'] }}</p>
                <p class="mt-3 text-2xl font-extrabold text-slate-900">{{ $metric['value'] }}</p>
                <p class="mt-2 text-xs font-bold text-slate-400">{{ $metric['hint'] ?? $dashboard['store']['id'] }}</p>
            </div>
        @endforeach
    </div>
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-[1.15fr_0.85fr]">
    <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">بيانات الشريك</h3>
        <div class="mt-5 grid gap-4 md:grid-cols-2">
            @foreach ([
                'store_id' => 'Store ID',
                'owner' => 'المالك',
                'email' => 'البريد',
                'phone' => 'الهاتف',
                'domain' => 'الدومين',
                'payment_provider' => 'إعدادات الدفع',
                'shipping_provider' => 'إعدادات الشحن',
                'notifications' => 'الإشعارات',
                'renewal_at' => 'تاريخ التجديد',
                'payment_status' => 'حالة الدفع',
            ] as $key => $label)
                <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-400">{{ $label }}</p>
                    <p class="mt-2 font-extrabold text-slate-800">{{ $partner[$key] ?? '-' }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">المستخدمون والصلاحيات</h3>
        <div class="mt-5 space-y-3">
            @foreach ($partner['users'] as $user)
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-extrabold text-slate-900">{{ $user['name'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $user['email'] }}</p>
                        </div>
                        <span class="rounded-2xl bg-brand-50 px-3 py-2 text-xs font-extrabold text-brand-700">{{ $user['role'] }}</span>
                    </div>
                    <p class="mt-3 text-xs font-bold text-slate-400">username: {{ $user['username'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="mt-6 grid gap-6 xl:grid-cols-2">
    <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">التنبيهات والملاحظات</h3>
        <div class="mt-5 space-y-3">
            @foreach ($partner['alerts'] as $alert)
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4">
                    <p class="font-extrabold text-slate-900">{{ $alert['title'] }}</p>
                    <p class="mt-2 text-sm leading-7 text-slate-500">{{ $alert['body'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <div class="rounded-[32px] border border-white/70 bg-white p-6 shadow-card">
        <h3 class="text-2xl font-extrabold text-slate-900">تقارير الأداء</h3>
        <div class="mt-5 space-y-4">
            @foreach ($partner['performance'] as $item)
                <div>
                    <div class="flex items-center justify-between text-sm font-bold text-slate-500">
                        <span>{{ $item['label'] }}</span>
                        <span>{{ $item['value'] }}</span>
                    </div>
                    <div class="mt-2 h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-gradient-to-l from-brand-500 to-sky-400" style="width: {{ $item['width'] }}"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
