@extends('layouts.partner')

@section('title', 'الأتمتة | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.apps') }}">التطبيقات</a><span>/</span><span>الأتمتة</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">الأتمتة</h1>
        </div>
        <a href="{{ route('api.partner.automations.index') }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>

    <form method="POST" action="{{ route('partner.apps.automations.store') }}" class="grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-5">
        @csrf
        <input name="name" required placeholder="اسم القاعدة" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
        <select name="trigger" required class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">@foreach($automations['triggers'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        <select name="action" required class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">@foreach($automations['actions'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        <input name="conditions" placeholder="الشروط" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
        <button class="rounded-2xl bg-solve-700 px-4 py-3 text-sm font-black text-white">إنشاء</button>
    </form>

    <div class="grid gap-4">
        @foreach ($automations['rules'] as $rule)
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-xl font-black">{{ $rule['name'] }}</h2><p class="mt-1 text-sm font-bold text-slate-500">{{ $rule['trigger'] }} → {{ $rule['action'] }} · {{ $rule['runs'] ?? 0 }} تشغيل</p></div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black dark:bg-slate-800">{{ $rule['status'] }}</span>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
