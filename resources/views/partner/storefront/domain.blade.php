@extends('layouts.partner')

@section('title', $title . ' | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300">
                <a href="{{ route('partner.storefront') }}">المتجر الإلكتروني</a><span>/</span><span>{{ $title }}</span>
            </div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">ربط دومين مخصص والتحقق من DNS وSSL ضمن نفس store_id.</p>
        </div>
        @include('partner.partials.api-tools', [
            'url' => route('api.partner.domain.index'),
            'copyLabel' => 'نسخ API',
            'openLabel' => 'فتح API',
        ])
    </div>

    <div class="grid gap-5 xl:grid-cols-3">
        <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
            <h2 class="text-xl font-black">ربط دومين</h2>
            <form method="POST" action="{{ route('partner.storefront.domain.connect') }}" class="mt-4 grid gap-3 md:grid-cols-3">
                @csrf
                <input name="custom_domain" required value="{{ $domain['custom_domain'] ?? '' }}" placeholder="example.com" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 md:col-span-2">
                <button class="rounded-2xl bg-solve-700 px-4 py-3 text-sm font-black text-white">حفظ الدومين</button>
            </form>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">الدومين الحالي</p><p class="mt-2 font-black">{{ $domain['current_domain'] ?? '-' }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">DNS</p><p class="mt-2 font-black">{{ $domain['dns_status'] ?? '-' }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">SSL</p><p class="mt-2 font-black">{{ $domain['ssl_status'] ?? '-' }}</p></div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">الحالة</p><p class="mt-2 font-black">{{ ! empty($domain['active']) ? 'نشط' : 'متوقف' }}</p></div>
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <form method="POST" action="{{ route('partner.storefront.domain.verify') }}">@csrf<button class="rounded-full bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">تحقق DNS / SSL</button></form>
                <form method="POST" action="{{ route('partner.storefront.domain.status') }}">@csrf<input type="hidden" name="active" value="{{ empty($domain['active']) ? 1 : 0 }}"><button class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">{{ ! empty($domain['active']) ? 'تعطيل' : 'تفعيل' }}</button></form>
            </div>
        </section>
        <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">تعليمات الربط</h2>
            <div class="mt-4 space-y-3">
                @forelse (($domain['instructions'] ?? []) as $instruction)
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold dark:bg-slate-950">{{ $instruction }}</div>
                @empty
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold text-slate-500 dark:bg-slate-950">لا توجد تعليمات مخصصة.</div>
                @endforelse
            </div>
        </aside>
    </div>
</div>
@endsection
