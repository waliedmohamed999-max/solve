@extends('layouts.partner')

@section('title', 'التطبيقات المثبتة | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.apps') }}">التطبيقات</a><span>/</span><span>المثبتة</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">التطبيقات المثبتة</h1>
        </div>
        <a href="{{ route('api.partner.apps.installed') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>
    @include('partner.apps.partials.filters', ['action' => route('partner.apps.installed'), 'payload' => $apps])
    <div class="grid gap-5 xl:grid-cols-2">
        @forelse ($apps['apps'] as $app)
            @include('partner.apps.partials.card', ['app' => $app])
        @empty
            <div class="rounded-3xl bg-white p-10 text-center text-sm font-bold text-slate-500 dark:bg-slate-900 xl:col-span-2">لا توجد تطبيقات مثبتة حالياً.</div>
        @endforelse
    </div>
</div>
@endsection
