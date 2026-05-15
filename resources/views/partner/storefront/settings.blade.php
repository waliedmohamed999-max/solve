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
            <p class="mt-2 text-sm font-bold text-slate-500">اسم المتجر والشعار والتواصل واللغة والعملة مرتبطة بواجهة المتجر.</p>
        </div>
        @include('partner.partials.api-tools', [
            'url' => route('api.partner.store-settings.index'),
            'copyLabel' => 'نسخ API',
            'openLabel' => 'فتح API',
        ])
    </div>

    <form method="POST" action="{{ route('partner.storefront.settings.update') }}" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        @csrf
        <div class="grid gap-4 md:grid-cols-2">
            <div><label class="text-sm font-black">اسم المتجر</label><input name="store_name" required value="{{ $settings['store_name'] ?? '' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">الشعار</label><input name="logo" value="{{ $settings['logo'] ?? '' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">الفافيكون</label><input name="favicon" value="{{ $settings['favicon'] ?? '' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">البريد</label><input name="contact_email" value="{{ $settings['contact_email'] ?? '' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">الجوال</label><input name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">أوقات العمل</label><input name="working_hours" value="{{ $settings['working_hours'] ?? '' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">اللغة</label><input name="language" required value="{{ $settings['language'] ?? 'ar' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div><label class="text-sm font-black">العملة</label><input name="currency" required value="{{ $settings['currency'] ?? 'SAR' }}" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950"></div>
            <div class="md:col-span-2"><label class="text-sm font-black">روابط السوشيال، رابط بكل سطر</label><textarea name="social_links" rows="5" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">@foreach (($settings['social_links'] ?? []) as $link){{ $link }}
@endforeach</textarea></div>
        </div>
        <div class="mt-5 flex justify-end">
            <button class="rounded-full bg-solve-700 px-7 py-3 text-sm font-black text-white">حفظ الإعدادات</button>
        </div>
    </form>
</div>
@endsection
