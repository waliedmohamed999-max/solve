@extends('layouts.partner')

@section('title', 'متجر التطبيقات | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.apps') }}">التطبيقات</a><span>/</span><span>المتجر</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">متجر التطبيقات</h1>
        </div>
        <a href="{{ route('api.partner.apps.marketplace') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>
    @include('partner.apps.partials.filters', ['action' => route('partner.apps.marketplace'), 'payload' => $apps])

    @foreach ($apps['apps'] as $group)
        <section class="space-y-4">
            <h2 class="text-xl font-black">{{ $group['label'] }}</h2>
            <div class="grid gap-5 xl:grid-cols-2">
                @foreach ($group['items'] as $app)
                    @include('partner.apps.partials.card', ['app' => $app])
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
