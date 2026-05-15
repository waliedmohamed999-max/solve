@extends('layouts.partner')

@section('title', 'قناة المتجر الإلكتروني | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.channels') }}">القنوات</a><span>/</span><span>المتجر الإلكتروني</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">المتجر الإلكتروني</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">مرتبط بقسم المتجر الإلكتروني والدومين والقالب وبيانات {{ $storefrontChannel['store_id'] }}.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.storefront') }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">فتح قسم المتجر</a>
            <a href="{{ route('api.partner.channels.storefront') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
        </div>
    </div>

    <section class="grid gap-4 md:grid-cols-4">
        @foreach (['الحالة' => $storefrontChannel['channel']['status'], 'رابط المتجر' => $storefrontChannel['storefront']['url'], 'الدومين' => $storefrontChannel['storefront']['domain_status'], 'القالب' => $storefrontChannel['storefront']['theme_status']] as $label => $value)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $label }}</p>
                <p class="mt-2 break-words text-lg font-black">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <form method="POST" action="{{ route('partner.channels.storefront.settings') }}" class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-3">
        @csrf
        <label class="grid gap-2 text-sm font-black">إعدادات الظهور<input name="visibility" value="{{ $storefrontChannel['storefront']['visibility'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">حالة الدومين<input name="domain_status" value="{{ $storefrontChannel['storefront']['domain_status'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">حالة القالب<input name="theme_status" value="{{ $storefrontChannel['storefront']['theme_status'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950 md:col-span-3">حفظ الإعدادات</button>
    </form>
</div>
@endsection
