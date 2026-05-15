@extends('layouts.partner')

@section('title', 'التطبيقات | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.dashboard') }}">لوحة التحكم</a><span>/</span><span>التطبيقات</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">التطبيقات</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">كل تطبيق مربوط بمتجر {{ $apps['store_id'] }} وبالصلاحيات والباقة وسجلات التشغيل.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.apps.marketplace') }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">متجر التطبيقات</a>
            <a href="{{ route('api.partner.apps.index') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
        </div>
    </div>

    @include('partner.apps.partials.filters', ['action' => route('partner.apps'), 'payload' => $apps])
    @include('partner.apps.partials.counts', ['counts' => $apps['counts']])

    <section class="grid gap-5 xl:grid-cols-2">
        @foreach ($apps['installed'] as $app)
            @include('partner.apps.partials.card', ['app' => $app])
        @endforeach
        @foreach ($apps['suggested'] as $app)
            @include('partner.apps.partials.card', ['app' => $app])
        @endforeach
    </section>
</div>
@endsection
