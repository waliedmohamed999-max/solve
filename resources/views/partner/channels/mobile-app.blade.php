@extends('layouts.partner')

@section('title', 'تطبيق الجوال | Solve')

@section('partner-content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300"><a href="{{ route('partner.channels') }}">القنوات</a><span>/</span><span>تطبيق الجوال</span></div>
            <h1 class="mt-3 text-3xl font-black text-slate-950 dark:text-white">تطبيق الجوال</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">إعدادات التطبيق والهوية والتنبيهات وروابط App Store وGoogle Play.</p>
        </div>
        <a href="{{ route('api.partner.channels.mobile-app') }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">API</a>
    </div>

    <form method="POST" action="{{ route('partner.channels.mobile-app.settings') }}" class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 md:grid-cols-2">
        @csrf
        <label class="grid gap-2 text-sm font-black">لون التطبيق<input name="primary_color" value="{{ $mobileApp['settings']['primary_color'] ?? '#6d28d9' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">رابط اللوجو<input name="logo_url" value="{{ $mobileApp['settings']['logo_url'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">حالة النشر<input name="publish_status" value="{{ $mobileApp['settings']['publish_status'] ?? 'مسودة' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">App Store<input name="app_store_url" value="{{ $mobileApp['settings']['app_store_url'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="grid gap-2 text-sm font-black">Google Play<input name="google_play_url" value="{{ $mobileApp['settings']['google_play_url'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 dark:border-slate-700 dark:bg-slate-950"></label>
        <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950"><input type="checkbox" name="push_enabled" value="1" @checked($mobileApp['settings']['push_enabled'] ?? false)> Push Notifications</label>
        <div class="flex flex-wrap gap-2 md:col-span-2">
            <button class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">حفظ الإعدادات</button>
            <button form="push-test" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">اختبار Push</button>
        </div>
    </form>
    <form id="push-test" method="POST" action="{{ route('partner.channels.mobile-app.push-test') }}">@csrf</form>
</div>
@endsection
