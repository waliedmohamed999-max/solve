@extends('layouts.partner')

@section('title', 'إعدادات ' . $appPage['app']['name'] . ' | Solve')

@section('partner-content')
@php($app = $appPage['app'])
@php($settings = $appPage['settings'])
<div class="space-y-6">
    <div class="flex items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.apps.installed') }}">التطبيقات المثبتة</a><span>/</span><span>{{ $app['name'] }}</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">إعدادات {{ $app['name'] }}</h1>
        </div>
        <a href="{{ route('api.partner.apps.settings', ['app' => $app['id']]) }}" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">API</a>
    </div>

    <form method="POST" action="{{ route('partner.apps.settings.update', ['app' => $app['id']]) }}" class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-2">
        @csrf
        <label class="grid gap-2 text-sm font-black">API Key<input name="api_key" placeholder="{{ $settings['api_key_masked'] ?? 'API Key' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">Webhook URL<input name="webhook_url" value="{{ $settings['webhook_url'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">الصلاحيات<input name="permissions" value="{{ implode(',', (array) ($settings['permissions'] ?? [])) }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">الأحداث<input name="events" value="{{ implode(',', (array) ($settings['events'] ?? [])) }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <div class="flex flex-wrap gap-2 md:col-span-2">
            <button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">حفظ الإعدادات</button>
            <button form="test-app" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">اختبار الاتصال</button>
        </div>
    </form>
    <form id="test-app" method="POST" action="{{ route('partner.apps.test', ['app' => $app['id']]) }}">@csrf</form>
</div>
@endsection
