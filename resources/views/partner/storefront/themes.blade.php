@extends('layouts.partner')

@section('title', 'قوالب المتجر | Solve')

@php
    $marketplace = $themeMarketplace ?? [];
    $templates = $marketplace['templates'] ?? [];
    $categories = $marketplace['categories'] ?? [];
    $stats = $marketplace['stats'] ?? [];
    $recommendation = $marketplace['recommendation'] ?? [];
    $recommendedTheme = $recommendation['theme'] ?? [];
    $intelligence = $themeIntelligence ?? [];
    $presets = $intelligence['presets'] ?? [];
    $matching = $intelligence['matching'] ?? [];
    $autoStyling = $intelligence['auto_styling'] ?? [];
    $bannerGeneration = $intelligence['banner_generation'] ?? [];
    $conversionEngine = $intelligence['conversion_engine'] ?? [];
    $themeAnalytics = $intelligence['theme_analytics'] ?? [];
    $marketplaceRanking = $intelligence['marketplace_ranking'] ?? [];
    $currentTheme = $storefrontPage['currentTheme'] ?? null;
@endphp

@section('partner-content')
<div class="space-y-6 p-4 lg:p-8" dir="rtl"
    x-data="{
        previewOpen: false,
        previewDevice: 'desktop',
        selected: null,
        compare: [],
        openPreview(theme) {
            this.selected = theme;
            this.previewDevice = 'desktop';
            this.previewOpen = true;
        },
        toggleCompare(id) {
            this.compare = this.compare.includes(id) ? this.compare.filter(item => item !== id) : [...this.compare, id].slice(-3);
        }
    }">
    <span class="sr-only">AI Theme Intelligence قوالب تفهم نشاط متجرك Ù‚ÙˆØ§Ù„Ø¨ ØªÙÙ‡Ù… Ù†Ø´Ø§Ø· Ù…ØªØ¬Ø±Ùƒ {{ $currentTheme['name'] ?? '' }}</span>
    @if (session('status'))
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900 lg:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300">
                    <a href="{{ route('partner.storefront') }}">المتجر الإلكتروني</a>
                    <span>/</span>
                    <span>القوالب</span>
                </div>
                <h1 class="mt-4 text-3xl font-black text-slate-950 dark:text-white lg:text-5xl">قوالب المتجر</h1>
                <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">
                    اختر قالباً جاهزاً حسب نشاطك التجاري، عاينه على الديسكتوب والموبايل، ثم ثبته كمسودة أو انشره مباشرة على واجهة المتجر.
                </p>
                @if ($currentTheme)
                    <div class="mt-4 inline-flex flex-wrap items-center gap-2 rounded-2xl border border-solve-100 bg-solve-50 px-4 py-2 text-xs font-black text-solve-700 dark:border-solve-500/20 dark:bg-solve-500/10 dark:text-solve-200">
                        <span>القالب الحالي</span>
                        <span class="rounded-full bg-white px-3 py-1 text-slate-950 shadow-sm dark:bg-slate-900 dark:text-white">{{ $currentTheme['name'] ?? '-' }}</span>
                        <span class="text-slate-500 dark:text-slate-300">{{ $currentTheme['style'] ?? 'Light' }}</span>
                    </div>
                @endif
            </div>

            <div class="grid gap-3 sm:grid-cols-4 xl:min-w-[520px]">
                <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">كل القوالب</p>
                    <p class="mt-2 text-2xl font-black">{{ $stats['total'] ?? count($templates) }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">المعروضة</p>
                    <p class="mt-2 text-2xl font-black">{{ $stats['visible'] ?? count($templates) }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">Premium</p>
                    <p class="mt-2 text-2xl font-black">{{ $stats['premium'] ?? 0 }}</p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">مثبتة</p>
                    <p class="mt-2 text-2xl font-black">{{ $stats['installed'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </section>

    @if (! empty($recommendedTheme))
        <section class="overflow-hidden rounded-[2rem] border border-solve-100 bg-gradient-to-l from-solve-50 via-white to-cyan-50 p-5 shadow-card dark:border-solve-500/20 dark:from-slate-900 dark:via-slate-900 dark:to-slate-950">
            <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
                <div>
                    <span class="rounded-full bg-solve-600 px-3 py-1 text-xs font-black text-white">AI Theme Match</span>
                    <h2 class="mt-4 text-2xl font-black text-slate-950 dark:text-white">Solve يقترح لك: {{ $recommendedTheme['name'] ?? '-' }}</h2>
                    <p class="mt-3 text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">{{ $recommendation['reason'] ?? 'تم اختيار القالب بناءً على نوع النشاط والمنتجات الحالية.' }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (array_slice($recommendedTheme['features'] ?? [], 0, 5) as $feature)
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-slate-600 shadow-sm dark:bg-slate-800 dark:text-slate-200">{{ $feature }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="rounded-[1.5rem] p-4 text-white" style="background: linear-gradient(135deg, {{ $recommendedTheme['primary_color'] ?? '#111827' }}, {{ $recommendedTheme['secondary_color'] ?? '#6d28d9' }});">
                    <p class="text-xs font-black text-white/70">{{ $recommendation['industry'] ?? 'متجر عام' }}</p>
                    <p class="mt-3 text-3xl font-black">{{ $recommendedTheme['headline'] ?? ($recommendedTheme['name'] ?? '-') }}</p>
                    <form method="POST" action="{{ route('partner.storefront.themes.install', ['theme' => $recommendedTheme['id'] ?? 'theme-minimal-fashion']) }}" class="mt-5">
                        @csrf
                        <button class="w-full rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 transition hover:bg-solve-50">تثبيت التوصية</button>
                    </form>
                </div>
            </div>
        </section>
    @endif

    <section class="grid gap-5 xl:grid-cols-[1.2fr_.8fr]">
        <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-black text-white dark:bg-white dark:text-slate-950">AI Theme Intelligence</span>
                    <h2 class="mt-4 text-2xl font-black text-slate-950 dark:text-white">محرك تجربة تجارية يفهم نشاط متجرك</h2>
                    <p class="mt-2 max-w-2xl text-sm font-bold leading-7 text-slate-500 dark:text-slate-400">
                        يحلل النشاط والمنتجات والألوان والجمهور، ثم يقترح قالباً وترتيب Sections وبنرات موسمية قابلة للتطبيق على نفس store_id.
                    </p>
                </div>
                <div class="rounded-3xl bg-slate-50 p-4 text-sm font-black dark:bg-slate-950">
                    <p class="text-slate-400">النشاط المكتشف</p>
                    <p class="mt-1 text-xl text-slate-950 dark:text-white">{{ $matching['analyzed']['activity'] ?? ($intelligence['context']['industry'] ?? '-') }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-3">
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">أفضل Preset</p>
                    <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $matching['best_preset']['name'] ?? ($intelligence['recommendation']['best_preset']['name'] ?? '-') }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $matching['best_preset']['industry'] ?? '' }}</p>
                </div>
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">أفضل Layout</p>
                    <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $matching['layout'] ?? '-' }}</p>
                    <p class="mt-1 text-xs font-bold text-slate-500">مبني على كتالوج المنتجات الحالي</p>
                </div>
                <div class="rounded-3xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">Conversion</p>
                    <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ $themeAnalytics['current']['conversion_rate'] ?? ($intelligence['analytics']['conversion_rate'] ?? 0) }}%</p>
                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $themeAnalytics['sales_impact_summary']['message'] ?? 'راقب أثر القالب بعد النشر.' }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('partner.storefront.themes.generate') }}" class="mt-5 grid gap-3 lg:grid-cols-[1fr_auto]">
                @csrf
                <input name="prompt" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none transition focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950" placeholder="مثال: أريد متجر عطور فاخر أو متجر رياضي عصري">
                <button class="rounded-2xl bg-solve-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-solve-600/20 transition hover:bg-solve-700">ولّد تجربة كاملة</button>
            </form>

            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @foreach (array_slice($conversionEngine['insights'] ?? [], 0, 4) as $insight)
                    <div class="rounded-3xl border border-slate-100 p-4 dark:border-slate-800">
                        <p class="text-sm font-black text-slate-950 dark:text-white">{{ $insight['title'] ?? '-' }}</p>
                        <p class="mt-2 text-xs font-bold leading-6 text-slate-500 dark:text-slate-400">{{ $insight['body'] ?? '' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <aside class="space-y-5">
            <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-lg font-black text-slate-950 dark:text-white">Auto Store Styling</h3>
                <p class="mt-1 text-xs font-bold text-slate-500">ألوان وخطوط مستخرجة من نشاط ومنتجات المتجر.</p>
                <div class="mt-4 grid grid-cols-3 gap-2">
                    @foreach (($autoStyling['extracted_palette'] ?? []) as $name => $color)
                        <div class="rounded-2xl border border-slate-100 p-2 text-center text-[11px] font-black dark:border-slate-800">
                            <div class="h-12 rounded-xl border border-white/50 shadow-inner" style="background: {{ $color }}"></div>
                            <p class="mt-2 text-slate-500">{{ $name }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-4 space-y-2">
                    @foreach (array_slice($autoStyling['ui_suggestions'] ?? [], 0, 3) as $suggestion)
                        <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                            <p class="text-xs font-black text-slate-950 dark:text-white">{{ $suggestion['title'] ?? '-' }}</p>
                            <p class="mt-1 text-[11px] font-bold leading-5 text-slate-500">{{ $suggestion['body'] ?? '' }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">AI Banners</h3>
                    <form method="POST" action="{{ route('partner.storefront.themes.generate-banners') }}">
                        @csrf
                        <button class="rounded-full bg-slate-950 px-3 py-1 text-xs font-black text-white dark:bg-white dark:text-slate-950">توليد</button>
                    </form>
                </div>
                <div class="mt-4 space-y-3">
                    @foreach (array_slice($bannerGeneration['banners'] ?? [], 0, 3) as $banner)
                        @php($colors = $banner['colors'] ?? ['#111827', '#6d28d9'])
                        <div class="rounded-3xl p-4 text-white" style="background: linear-gradient(135deg, {{ $colors[0] ?? '#111827' }}, {{ $colors[1] ?? '#6d28d9' }});">
                            <p class="text-xs font-black text-white/70">{{ $banner['placement'] ?? 'home' }}</p>
                            <p class="mt-2 text-lg font-black">{{ $banner['title'] ?? '-' }}</p>
                            <p class="mt-1 text-xs font-bold text-white/80">{{ $banner['subtitle'] ?? '' }}</p>
                            <span class="mt-3 inline-flex rounded-full bg-white px-3 py-1 text-[11px] font-black text-slate-950">{{ $banner['cta'] ?? 'تسوق الآن' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </aside>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <form method="GET" action="{{ route('partner.storefront.themes') }}" class="grid flex-1 gap-3 lg:grid-cols-[1fr_180px_180px_auto]">
                <input name="q" value="{{ request('q') }}" class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold outline-none transition focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950" placeholder="ابحث عن قالب يناسب متجرك...">
                <select name="category" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-950">
                    @foreach ($categories as $category)
                        <option value="{{ $category['key'] }}" @selected(request('category', 'all') === $category['key'])>{{ $category['label'] }}</option>
                    @endforeach
                </select>
                <select name="sort" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-950">
                    <option value="recommended" @selected(request('sort', 'recommended') === 'recommended')>الأكثر ملاءمة</option>
                    <option value="conversion" @selected(request('sort') === 'conversion')>الأعلى Conversion</option>
                    <option value="speed" @selected(request('sort') === 'speed')>الأسرع</option>
                    <option value="mobile" @selected(request('sort') === 'mobile')>الأفضل للموبايل</option>
                    <option value="newest" @selected(request('sort') === 'newest')>الأحدث</option>
                </select>
                <button class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white transition hover:bg-solve-700 dark:bg-white dark:text-slate-950">تصفية</button>
            </form>
            <a href="{{ route('partner.storefront.customize') }}" class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">تعديل الواجهة</a>
        </div>

        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
            @foreach ($categories as $category)
                <a href="{{ route('partner.storefront.themes', array_filter(['category' => $category['key'] === 'all' ? null : $category['key'], 'sort' => request('sort')])) }}"
                    class="shrink-0 rounded-2xl border px-4 py-2 text-sm font-black transition {{ request('category', 'all') === $category['key'] ? 'border-slate-950 bg-slate-950 text-white dark:border-white dark:bg-white dark:text-slate-950' : 'border-slate-200 bg-white text-slate-600 hover:border-solve-200 hover:text-solve-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-300' }}">
                    {{ $category['label'] }}
                </a>
            @endforeach
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-3 2xl:grid-cols-4">
        @forelse ($templates as $theme)
            <article class="group overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-card transition duration-300 hover:-translate-y-1 hover:shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                <button type="button" @click='openPreview(@json($theme, JSON_UNESCAPED_UNICODE))' class="block w-full text-right">
                    <div class="relative h-56 overflow-hidden border-b border-slate-100 dark:border-slate-800" style="background: linear-gradient(135deg, {{ $theme['primary_color'] }}, {{ $theme['secondary_color'] }});">
                        <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 18% 18%, white 0, transparent 18%), radial-gradient(circle at 80% 22%, white 0, transparent 16%), linear-gradient(135deg, transparent 0 45%, rgba(255,255,255,.22) 45% 55%, transparent 55%);"></div>
                        <div class="absolute inset-x-4 top-4 flex items-center justify-between rounded-2xl bg-white/15 px-3 py-2 text-[11px] font-black text-white backdrop-blur">
                            <span>{{ $theme['header_layout'] }}</span>
                            <span>{{ $theme['price'] }}</span>
                        </div>
                        <div class="absolute bottom-5 right-5 left-5">
                            <p class="max-w-[260px] text-2xl font-black text-white">{{ $theme['headline'] }}</p>
                            <p class="mt-2 line-clamp-2 text-xs font-bold text-white/80">{{ $theme['description'] }}</p>
                        </div>
                        <div class="absolute left-4 bottom-4 flex gap-1">
                            @foreach (array_slice($theme['badges'], 0, 2) as $badge)
                                <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black text-slate-950">{{ $badge }}</span>
                            @endforeach
                        </div>
                    </div>
                </button>

                <div class="p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-950 dark:text-white">{{ $theme['name'] }}</h3>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $theme['category'] }} · {{ $theme['style'] }}</p>
                        </div>
                        <form method="POST" action="{{ route('partner.storefront.themes.favorite', ['theme' => $theme['id']]) }}">
                            @csrf
                            <button class="rounded-2xl border border-slate-200 px-3 py-2 text-sm font-black {{ $theme['favorite'] ? 'bg-amber-50 text-amber-700' : 'text-slate-500' }}" title="حفظ للمفضلة">★</button>
                        </form>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-center text-[11px] font-black text-slate-500">
                        <div class="rounded-2xl bg-slate-50 p-2 dark:bg-slate-950">Speed {{ $theme['speed_score'] }}</div>
                        <div class="rounded-2xl bg-slate-50 p-2 dark:bg-slate-950">SEO {{ $theme['seo_score'] }}</div>
                        <div class="rounded-2xl bg-slate-50 p-2 dark:bg-slate-950">Mobile {{ $theme['mobile_score'] }}</div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach (array_slice($theme['features'], 0, 3) as $feature)
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $feature }}</span>
                        @endforeach
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-2">
                        <button type="button" @click='openPreview(@json($theme, JSON_UNESCAPED_UNICODE))' class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-black transition hover:border-solve-200 hover:text-solve-700 dark:border-slate-700">معاينة</button>
                        <form method="POST" action="{{ route('partner.storefront.themes.install', ['theme' => $theme['id']]) }}">
                            @csrf
                            <button class="w-full rounded-2xl bg-solve-600 px-4 py-3 text-sm font-black text-white transition hover:bg-solve-700">{{ $theme['installed'] ? 'إعادة تثبيت' : 'استخدام القالب' }}</button>
                        </form>
                        <button type="button" @click="toggleCompare('{{ $theme['id'] }}')" class="rounded-2xl bg-slate-50 px-4 py-3 text-xs font-black text-slate-600 dark:bg-slate-950 dark:text-slate-300">مقارنة</button>
                        <form method="POST" action="{{ route('partner.storefront.themes.publish', ['theme' => $theme['id']]) }}">
                            @csrf
                            <button class="w-full rounded-2xl bg-slate-950 px-4 py-3 text-xs font-black text-white dark:bg-white dark:text-slate-950">{{ $theme['active'] ? 'منشور' : 'نشر' }}</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-[2rem] border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                <p class="text-2xl font-black">لا توجد قوالب مطابقة</p>
                <p class="mt-2 text-sm font-bold text-slate-500">غيّر الفلاتر أو استخدم توصيات AI لتوليد واجهة مناسبة.</p>
            </div>
        @endforelse
    </section>

    <section class="rounded-[2rem] border border-solve-100 bg-solve-50 p-6 shadow-card dark:border-solve-500/20 dark:bg-solve-500/10">
        <div class="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-center">
            <div>
                <h2 class="text-2xl font-black text-slate-950 dark:text-white">إنشاء قالب مخصص</h2>
                <p class="mt-2 text-sm font-bold leading-7 text-slate-500 dark:text-slate-300">استخدم ذكاء Solve لاختيار الألوان والخطوط والبنرات وترتيب الصفحة حسب نشاط متجرك ومنتجاتك.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @foreach (array_slice($presets, 0, 4) as $preset)
                        <form method="POST" action="{{ route('partner.storefront.themes.apply-preset') }}">
                            @csrf
                            <input type="hidden" name="preset_key" value="{{ $preset['key'] ?? '' }}">
                            <button class="rounded-full bg-white px-3 py-1 text-xs font-black text-solve-700 shadow-sm dark:bg-slate-900">{{ $preset['name'] ?? 'Preset' }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
            <a href="{{ route('partner.storefront.customize') }}" class="rounded-2xl bg-solve-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-solve-600/20 transition hover:bg-solve-700">افتح محرر الواجهة</a>
        </div>
    </section>

    <div x-show="previewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" @keydown.escape.window="previewOpen = false">
        <div class="max-h-[92vh] w-full max-w-6xl overflow-hidden rounded-[2rem] bg-white shadow-2xl dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                <div>
                    <p class="text-xs font-black text-solve-600">Live Preview</p>
                    <h3 class="text-xl font-black" x-text="selected?.name"></h3>
                </div>
                <div class="flex items-center gap-2">
                    <template x-for="device in ['desktop','tablet','mobile']" :key="device">
                        <button type="button" @click="previewDevice = device" :class="previewDevice === device ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-50 text-slate-500 dark:bg-slate-950'" class="rounded-2xl px-4 py-2 text-xs font-black" x-text="device"></button>
                    </template>
                    <button type="button" @click="previewOpen = false" class="rounded-2xl border border-slate-200 px-4 py-2 text-sm font-black dark:border-slate-700">إغلاق</button>
                </div>
            </div>
            <div class="overflow-auto bg-slate-100 p-5 dark:bg-slate-950">
                <div class="mx-auto overflow-hidden rounded-[1.5rem] bg-white shadow-xl transition-all dark:bg-slate-900"
                    :class="previewDevice === 'mobile' ? 'max-w-sm' : (previewDevice === 'tablet' ? 'max-w-3xl' : 'max-w-5xl')">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 text-sm font-black dark:border-slate-800">
                        <span x-text="selected?.headline"></span>
                        <span class="text-solve-600">Solve</span>
                    </div>
                    <div class="min-h-72 p-6 text-white" :style="`background: linear-gradient(135deg, ${selected?.primary_color || '#111827'}, ${selected?.secondary_color || '#6d28d9'})`">
                        <div class="max-w-xl">
                            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-black" x-text="selected?.category"></span>
                            <h4 class="mt-6 text-4xl font-black" x-text="selected?.headline"></h4>
                            <p class="mt-3 text-sm font-bold text-white/80" x-text="selected?.description"></p>
                            <button class="mt-6 rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950">تسوق الآن</button>
                        </div>
                    </div>
                    <div class="grid gap-3 p-5 sm:grid-cols-3">
                        <template x-for="section in (selected?.sections_included || []).slice(0, 6)" :key="section">
                            <div class="rounded-2xl border border-slate-200 p-4 text-center text-xs font-black dark:border-slate-800" x-text="section"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
