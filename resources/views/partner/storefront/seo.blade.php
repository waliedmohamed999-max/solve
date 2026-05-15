@extends('layouts.partner')

@section('title', $title . ' | Solve')

@php
    $metaTitle = $seo['meta_title'] ?? 'متجر أطلس';
    $metaDescription = $seo['meta_description'] ?? 'متجر إلكتروني احترافي يعمل على منصة Solve.';
    $socialImage = $seo['social_image'] ?? 'solve-logo.png';
    $speedScore = (int) ($seo['speed_score'] ?? 90);
    $seoChecks = [
        ['label' => 'Meta Title', 'done' => filled($metaTitle), 'hint' => mb_strlen($metaTitle) . ' حرف'],
        ['label' => 'Meta Description', 'done' => filled($metaDescription), 'hint' => mb_strlen($metaDescription) . ' حرف'],
        ['label' => 'Sitemap', 'done' => ! empty($seo['sitemap_enabled']), 'hint' => ! empty($seo['sitemap_enabled']) ? 'مفعل' : 'غير مفعل'],
        ['label' => 'Open Graph', 'done' => ! empty($seo['open_graph_enabled']), 'hint' => ! empty($seo['open_graph_enabled']) ? 'مفعل' : 'غير مفعل'],
    ];
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
                    <span>SEO</span>
                </div>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 dark:text-white">SEO</h1>
                <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-slate-500">
                    اضبط عنوان المتجر، وصف نتائج البحث، صورة المشاركة، Sitemap، و Robots.txt من صفحة واحدة مرتبطة بواجهة المتجر.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @include('partner.partials.api-tools', [
                    'url' => route('api.partner.seo.index'),
                    'copyLabel' => 'نسخ API',
                    'openLabel' => 'فتح API',
                ])
                <a href="{{ route('partner.storefront.customize') }}" class="rounded-full bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">تعديل الواجهة</a>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('partner.storefront.seo.update') }}" class="grid gap-5 xl:grid-cols-[1fr_380px]">
        @csrf
        <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-2xl font-black text-slate-950 dark:text-white">إعدادات البحث</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">حافظ على العنوان والوصف واضحين ومناسبين للظهور في نتائج البحث.</p>
                </div>
                <span class="rounded-full bg-solve-50 px-4 py-2 text-xs font-black text-solve-700 dark:bg-solve-900/30 dark:text-solve-200">{{ $seo['index_status'] ?? 'جاهز للأرشفة' }}</span>
            </div>

            <div class="grid gap-4">
                <label class="block">
                    <span class="text-sm font-black">Meta Title</span>
                    <input name="meta_title" required value="{{ $metaTitle }}" maxlength="180" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="block">
                    <span class="text-sm font-black">Meta Description</span>
                    <textarea name="meta_description" rows="4" maxlength="320" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold leading-7 outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">{{ $metaDescription }}</textarea>
                </label>
                <label class="block">
                    <span class="text-sm font-black">Social Image</span>
                    <input name="social_image" value="{{ $socialImage }}" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">
                </label>
                <label class="block">
                    <span class="text-sm font-black">Robots.txt</span>
                    <textarea name="robots_txt" rows="7" dir="ltr" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left font-mono text-sm leading-7 outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">{{ $seo['robots_txt'] ?? '' }}</textarea>
                </label>
            </div>
        </section>

        <aside class="space-y-5">
            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black">جاهزية SEO</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($seoChecks as $check)
                        <div class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                            <div>
                                <p class="text-sm font-black">{{ $check['label'] }}</p>
                                <p class="mt-1 text-xs font-bold text-slate-400">{{ $check['hint'] }}</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-black {{ $check['done'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $check['done'] ? 'جاهز' : 'ناقص' }}</span>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black">الحالة التقنية</h2>
                <div class="mt-4 space-y-3">
                    <label class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950">
                        <span>Sitemap مفعل</span>
                        <input type="checkbox" name="sitemap_enabled" value="1" @checked(! empty($seo['sitemap_enabled'])) class="h-5 w-5 rounded border-slate-300 text-solve-700">
                    </label>
                    <label class="flex items-center justify-between rounded-2xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950">
                        <span>Open Graph مفعل</span>
                        <input type="checkbox" name="open_graph_enabled" value="1" @checked(! empty($seo['open_graph_enabled'])) class="h-5 w-5 rounded border-slate-300 text-solve-700">
                    </label>
                    <label class="block rounded-2xl bg-slate-50 p-4 dark:bg-slate-950">
                        <span class="text-xs font-black text-slate-400">سرعة الصفحات</span>
                        <input type="number" name="speed_score" min="0" max="100" value="{{ $speedScore }}" class="mt-2 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold dark:border-slate-700 dark:bg-slate-900">
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-solve-600" style="width: {{ max(0, min(100, $speedScore)) }}%"></div>
                        </div>
                    </label>
                    <input name="index_status" value="{{ $seo['index_status'] ?? 'جاهز للأرشفة' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                </div>
            </section>
        </aside>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900 xl:col-span-2">
            <div class="grid gap-5 lg:grid-cols-2">
                <div>
                    <h2 class="text-xl font-black">معاينة Google</h2>
                    <div class="mt-4 rounded-3xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950">
                        <p class="text-xs font-bold text-emerald-700">https://store-atlas.solve.sa</p>
                        <h3 class="mt-2 text-xl font-black text-blue-700">{{ $metaTitle }}</h3>
                        <p class="mt-2 text-sm font-bold leading-7 text-slate-600 dark:text-slate-300">{{ $metaDescription }}</p>
                    </div>
                </div>
                <div>
                    <h2 class="text-xl font-black">معاينة المشاركة</h2>
                    <div class="mt-4 overflow-hidden rounded-3xl border border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                        <div class="flex h-40 items-center justify-center bg-white dark:bg-slate-900">
                            <img src="{{ str_starts_with($socialImage, 'http') ? $socialImage : asset($socialImage) }}" alt="Social image" class="max-h-28 max-w-[70%] object-contain">
                        </div>
                        <div class="p-4">
                            <p class="text-sm font-black">{{ $metaTitle }}</p>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $metaDescription }}</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end">
                <button class="rounded-full bg-solve-700 px-8 py-3 text-sm font-black text-white shadow-lg shadow-solve-700/20">حفظ SEO</button>
            </div>
        </section>
    </form>
</div>
@endsection
