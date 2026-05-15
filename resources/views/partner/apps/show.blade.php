@extends('layouts.partner')

@section('title', $appPage['app']['name'] . ' | Solve')

@section('partner-content')
@php($app = $appPage['app'])
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.apps.marketplace') }}">متجر التطبيقات</a><span>/</span><span>{{ $app['name'] }}</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">{{ $app['name'] }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">{{ $app['provider'] }} · {{ $app['category'] }} · {{ $app['price'] }}</p>
        </div>
        <a href="{{ route('api.partner.apps.show', ['app' => $app['id']]) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>

    <section class="grid gap-5 lg:grid-cols-[1fr_360px]">
        <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">المميزات والصلاحيات</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach (($app['features'] ?? []) as $feature)
                    <div class="rounded-2xl bg-slate-50 p-4 text-sm font-bold dark:bg-slate-950">{{ $feature }}</div>
                @endforeach
            </div>
            <div class="mt-5 rounded-2xl bg-amber-50 p-4 text-sm font-bold text-amber-900">الصلاحيات المطلوبة قبل التثبيت: {{ implode(', ', $app['permissions'] ?? []) }}</div>
        </article>
        <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm font-black text-slate-400">الحالة</p>
            <p class="mt-2 text-2xl font-black">{{ $app['status'] }}</p>
            <div class="mt-5 flex flex-wrap gap-2">
                @if (($app['status_key'] ?? '') === 'not_installed')
                    <form method="POST" action="{{ route('partner.apps.install', ['app' => $app['id']]) }}">@csrf<button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">تثبيت التطبيق</button></form>
                @else
                    <a href="{{ route('partner.apps.settings', ['app' => $app['id']]) }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">الإعدادات</a>
                    <form method="POST" action="{{ route('partner.apps.uninstall', ['app' => $app['id']]) }}" onsubmit="return confirm('تأكيد إزالة التطبيق؟')">@csrf<button class="rounded-full border border-rose-200 px-5 py-3 text-sm font-black text-rose-700">إزالة</button></form>
                @endif
            </div>
        </aside>
    </section>
</div>
@endsection
