@extends('layouts.partner')

@section('title', $title . ' | Solve')

@php
    $headerMenu = $navigation['header_menu'] ?? [];
    $footerMenu = $navigation['footer_menu'] ?? [];
    $menuToText = fn (array $items) => collect($items)
        ->map(fn (array $item) => ($item['label'] ?? '') . '|' . ($item['url'] ?? '#'))
        ->implode("\n");
@endphp

@section('partner-content')
<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300">
                    <a href="{{ route('partner.storefront') }}">المتجر الإلكتروني</a>
                    <span>/</span>
                    <span>{{ $title }}</span>
                </div>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 dark:text-white">{{ $title }}</h1>
                <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-slate-500">
                    أدخل كل عنصر بصيغة <span class="font-black text-slate-800 dark:text-slate-100">الاسم|الرابط</span>. يدعم روابط داخلية مثل `/products` وخارجية مثل `https://example.com`.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('partner.partials.api-tools', [
                    'url' => route('api.partner.navigation.index'),
                    'copyLabel' => 'نسخ API',
                    'openLabel' => 'فتح API',
                ])
                <a href="{{ route('partner.storefront.customize') }}" class="rounded-full bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">تعديل الواجهة</a>
            </div>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-3">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-black">معاينة الهيدر</h2>
            <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                <div class="flex items-center justify-between gap-3 rounded-2xl bg-white px-4 py-3 shadow-sm dark:bg-slate-900">
                    <span class="font-black">متجر أطلس</span>
                    <div class="flex flex-wrap justify-end gap-2">
                        @forelse ($headerMenu as $item)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $item['label'] ?? '-' }}</span>
                        @empty
                            <span class="text-xs font-bold text-slate-400">لا توجد روابط</span>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="mt-4 grid gap-3">
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">روابط الهيدر</p>
                    <p class="mt-2 text-3xl font-black">{{ count($headerMenu) }}</p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">روابط الفوتر</p>
                    <p class="mt-2 text-3xl font-black">{{ count($footerMenu) }}</p>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('partner.storefront.navigation.update') }}" class="grid gap-5 xl:col-span-2">
            @csrf
            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black">Header Menu</h2>
                            <p class="mt-1 text-sm font-bold text-slate-500">روابط أعلى المتجر.</p>
                        </div>
                        <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ count($headerMenu) }} روابط</span>
                    </div>
                    <textarea name="header_menu" rows="14" dir="ltr" class="mt-4 w-full rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left font-mono text-sm font-bold leading-7 outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">{{ $menuToText($headerMenu) }}</textarea>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-black text-slate-500">
                        <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">الرئيسية|/</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">المنتجات|/products</span>
                    </div>
                </section>

                <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black">Footer Menu</h2>
                            <p class="mt-1 text-sm font-bold text-slate-500">روابط أسفل المتجر وخدمة العملاء.</p>
                        </div>
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700">{{ count($footerMenu) }} روابط</span>
                    </div>
                    <textarea name="footer_menu" rows="14" dir="ltr" class="mt-4 w-full rounded-3xl border border-slate-200 bg-slate-50 p-4 text-left font-mono text-sm font-bold leading-7 outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">{{ $menuToText($footerMenu) }}</textarea>
                    <div class="mt-3 flex flex-wrap gap-2 text-xs font-black text-slate-500">
                        <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">تواصل معنا|/contact</span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800">سياسة الاسترجاع|/pages/returns-policy</span>
                    </div>
                </section>
            </div>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-xl font-black">معاينة الفوتر</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">هذه الروابط ستظهر في Footer المتجر العام.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($footerMenu as $item)
                            <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $item['label'] ?? '-' }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-3">
                    <a href="{{ route('partner.storefront') }}" class="rounded-full border border-slate-200 px-6 py-3 text-sm font-black dark:border-slate-700">رجوع</a>
                    <button class="rounded-full bg-solve-700 px-8 py-3 text-sm font-black text-white shadow-lg shadow-solve-700/20">حفظ القوائم</button>
                </div>
            </section>
        </form>
    </section>
</div>
@endsection
