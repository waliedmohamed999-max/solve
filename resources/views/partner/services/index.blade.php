@extends('layouts.partner')

@section('title', 'الخدمات | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.dashboard') }}">لوحة التحكم</a><span>/</span><span>الخدمات</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">الخدمات</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">كل خدمة مرتبطة بمتجر {{ $services['store_id'] }} وبالباقة والصلاحيات.</p>
        </div>
        <a href="{{ route('api.partner.services.index') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
    </div>

    <form method="GET" action="{{ route('partner.services') }}" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-4">
        <input name="q" value="{{ $services['filters']['q'] }}" placeholder="بحث عن خدمة" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
        <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @foreach ($services['statusOptions'] as $key => $label)
                <option value="{{ $key }}" @selected($services['filters']['status'] === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button class="rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">فلترة</button>
        <a href="{{ route('partner.services') }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-black dark:border-slate-700">إعادة ضبط</a>
    </form>

    <div class="grid gap-4 md:grid-cols-4">
        @foreach ($services['counts'] as $label => $value)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                <p class="mt-2 text-3xl font-black">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-5 xl:grid-cols-2">
        @forelse ($services['services'] as $service)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-black">{{ $service['name'] }}</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ $service['provider'] ?? '-' }} · {{ $service['plan'] ?? 'Starter' }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-black {{ ($service['status_key'] ?? '') === 'enabled' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $service['status'] }}</span>
                </div>
                <p class="mt-4 rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-500 dark:bg-slate-950">{{ $service['help_tip'] ?? 'أكمل إعداد الخدمة.' }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ match($service['id']) { 'logistics' => route('partner.services.logistics'), 'payment-gateways' => route('partner.services.payment-gateways'), 'whatsapp' => route('partner.services.whatsapp'), 'financing' => route('partner.services.financing'), default => route('partner.services.growth') } }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إعداد الخدمة</a>
                    <form method="POST" action="{{ route('partner.services.test', ['service' => $service['id']]) }}">@csrf<button class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">اختبار</button></form>
                    @if (($service['status_key'] ?? '') !== 'admin_paused')
                        <form method="POST" action="{{ route('partner.services.status', ['service' => $service['id']]) }}" onsubmit="return confirm('تأكيد تغيير حالة الخدمة؟')">@csrf<input type="hidden" name="status" value="{{ ($service['status_key'] ?? '') === 'enabled' ? 'disabled' : 'enabled' }}"><button class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">{{ ($service['status_key'] ?? '') === 'enabled' ? 'تعطيل' : 'تفعيل' }}</button></form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl bg-white p-10 text-center text-sm font-bold text-slate-500 dark:bg-slate-900 xl:col-span-2">لا توجد خدمات متاحة لهذه الباقة.</div>
        @endforelse
    </div>

    @if (count($services['alerts']))
        <section class="rounded-3xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
            <h2 class="text-xl font-black">تنبيهات الخدمات</h2>
            <div class="mt-3 grid gap-3 md:grid-cols-2">
                @foreach ($services['alerts'] as $alert)
                    <div class="rounded-2xl bg-white/70 p-4"><p class="font-black">{{ $alert['title'] }}</p><p class="mt-1 text-sm font-bold">{{ $alert['body'] }}</p></div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
