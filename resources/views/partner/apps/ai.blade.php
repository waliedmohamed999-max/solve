@extends('layouts.partner')

@section('title', 'الذكاء الاصطناعي | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.apps') }}">التطبيقات</a><span>/</span><span>AI</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">أدوات الذكاء الاصطناعي</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">الاستخدام: {{ $ai['usage']['used'] }} / {{ $ai['usage']['limit'] }}</p>
        </div>
        <a href="{{ route('api.partner.ai.tools') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>

    <form method="POST" action="{{ route('partner.apps.ai.generate') }}" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-4">
        @csrf
        <select name="tool" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            @foreach ($ai['tools']['tools'] as $tool)
                <option value="{{ $tool['id'] }}">{{ $tool['name'] }}</option>
            @endforeach
        </select>
        <input name="prompt" placeholder="اكتب المنتج أو الهدف" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 md:col-span-2">
        <button class="rounded-2xl bg-solve-700 px-4 py-3 text-sm font-black text-white">توليد</button>
    </form>

    <section class="grid gap-4 md:grid-cols-3">
        @foreach ($ai['recommendations']['recommendations'] as $recommendation)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <p class="text-xs font-black text-slate-400">{{ $recommendation['source'] }}</p>
                <h2 class="mt-2 text-lg font-black">{{ $recommendation['title'] }}</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">{{ $recommendation['priority'] }}</p>
            </div>
        @endforeach
    </section>
</div>
@endsection
