@extends('layouts.partner')

@section('title', 'تعديل الواجهة | Solve')

@php
    $currentTheme = $storefront['currentTheme'] ?? ($storefrontPage['rows'][0] ?? []);
    $themeId = $currentTheme['id'] ?? ($storefrontPage['rows'][0]['id'] ?? 'theme');
    $storefrontSlug = $partner['slug'] ?? ($partner['id'] ?? null);
    if (! $storefrontSlug) {
        $storefrontStoreId = (string) ($partner['store_id'] ?? 'atlas');
        $storefrontSlug = str_starts_with($storefrontStoreId, 'store-') ? substr($storefrontStoreId, 6) : $storefrontStoreId;
    }
    $previewUrl = route('storefront.home', ['slug' => $storefrontSlug], false);
    $previewFrameUrl = $previewUrl . (str_contains($previewUrl, '?') ? '&' : '?') . 'builder_preview=1';
    $storeName = $settings['store_name'] ?? ($partner['name'] ?? 'متجر Solve');
    $logo = $settings['logo'] ?? ($partner['logo'] ?? 'solve-logo.png');
    $favicon = $settings['favicon'] ?? 'solve-logo.png';
    $primary = $currentTheme['primary_color'] ?? '#6d28d9';
    $secondary = $currentTheme['secondary_color'] ?? '#06b6d4';
    $headerMenu = $navigation['header_menu'] ?? [];
    $footerMenu = $navigation['footer_menu'] ?? [];
    $socialLinks = $settings['social_links'] ?? [];
    $socialLinks = is_array($socialLinks) ? $socialLinks : preg_split('/\r\n|\r|\n/', (string) $socialLinks);
    $menuToText = fn (array $items) => collect($items)
        ->map(fn (array $item) => ($item['label'] ?? '') . '|' . ($item['url'] ?? '#'))
        ->implode("\n");
    $products = collect($partner['products'] ?? [])->take(4);
    $categories = collect($partner['products'] ?? [])
        ->pluck('category')
        ->filter()
        ->unique()
        ->take(5)
        ->values();
    if ($categories->isEmpty()) {
        $categories = collect(['ساعات', 'سماعات', 'كاميرات', 'إكسسوارات', 'هواتف']);
    }
    $readiness = collect($storefront['readiness'] ?? []);
    $readyCount = $readiness->where('done', true)->count();
    $readyPercent = $readiness->count() ? (int) round(($readyCount / $readiness->count()) * 100) : 0;
    $sections = [
        ['key' => 'announcement', 'label' => 'شريط الإعلان العلوي', 'icon' => 'eye', 'meta' => 'خصم أو رسالة'],
        ['key' => 'header', 'label' => 'الهيدر الرئيسي', 'icon' => 'layout', 'meta' => 'القوائم والبحث'],
        ['key' => 'hero', 'label' => 'البانر الرئيسي', 'icon' => 'home', 'meta' => 'Hero + CTA'],
        ['key' => 'benefits', 'label' => 'شريط المزايا', 'icon' => 'sparkles', 'meta' => 'شحن ودفع ودعم'],
        ['key' => 'categories', 'label' => 'التصنيفات', 'icon' => 'grid', 'meta' => $categories->count() . ' تصنيف'],
        ['key' => 'featured', 'label' => 'المنتجات المميزة', 'icon' => 'package', 'meta' => $products->count() . ' منتجات'],
        ['key' => 'offers', 'label' => 'العروض والتخفيضات', 'icon' => 'megaphone', 'meta' => 'بنرات وجدولة'],
        ['key' => 'newsletter', 'label' => 'النشرة البريدية', 'icon' => 'file', 'meta' => 'التقاط العملاء'],
        ['key' => 'footer', 'label' => 'الفوتر', 'icon' => 'link', 'meta' => 'روابط وتواصل'],
    ];
    $builderRows = collect($builderSections['rows'] ?? []);
    $sortedBuilderRows = $builderRows
        ->sortBy(fn (array $row) => (int) ($row['sort_order'] ?? 0))
        ->values();
    $sectionIconMap = [
        'announcement' => 'eye',
        'header' => 'layout',
        'hero' => 'home',
        'trust_bar' => 'sparkles',
        'categories_grid' => 'grid',
        'featured_products' => 'package',
        'offers_banner' => 'megaphone',
        'newsletter' => 'file',
        'footer' => 'link',
        'video' => 'image',
        'countdown' => 'clock',
        'slider' => 'image',
        'testimonials' => 'heart',
        'faq' => 'file',
        'whatsapp_cta' => 'message-circle',
        'ai_recommendations' => 'sparkles',
    ];
    $sectionLabelMap = [
        'announcement' => 'شريط الإعلان العلوي',
        'header' => 'الهيدر الرئيسي',
        'hero' => 'البانر الرئيسي',
        'trust_bar' => 'شريط المزايا',
        'categories_grid' => 'التصنيفات',
        'featured_products' => 'المنتجات المميزة',
        'offers_banner' => 'بنر العروض',
        'newsletter' => 'النشرة البريدية',
        'footer' => 'الفوتر',
        'video' => 'فيديو تسويقي',
        'countdown' => 'عد تنازلي',
        'slider' => 'سلايدر',
        'testimonials' => 'تقييمات العملاء',
        'faq' => 'الأسئلة الشائعة',
        'whatsapp_cta' => 'واتساب',
        'ai_recommendations' => 'توصيات ذكية',
    ];
    if ($builderRows->isNotEmpty()) {
        $sections = $builderRows
            ->sortBy(fn (array $row) => (int) ($row['sort_order'] ?? 0))
            ->map(function (array $row) use ($sectionIconMap, $sectionLabelMap): array {
                $type = $row['type'] ?? 'custom';

                return [
                    'id' => $row['id'] ?? null,
                    'key' => $row['id'] ?? $type,
                    'type' => $type,
                    'label' => $sectionLabelMap[$type] ?? ($row['title'] ?? 'قسم مخصص'),
                    'icon' => $sectionIconMap[$type] ?? 'layout',
                    'meta' => ($row['placement'] ?? 'home') . ' · ' . (! empty($row['visible']) ? 'ظاهر' : 'مخفي'),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                    'visible' => (bool) ($row['visible'] ?? true),
                    'status' => $row['status_key'] ?? ($row['status'] ?? 'active'),
                ];
            })
            ->values()
            ->all();
    }
    $initialSelected = $sections[0]['key'] ?? 'hero';
    $componentGroups = [
        'العناصر الأساسية' => [
            ['label' => 'عنوان', 'icon' => 'T'],
            ['label' => 'نص', 'icon' => '☰'],
            ['label' => 'صورة', 'icon' => '▧'],
            ['label' => 'زر', 'icon' => '▭'],
            ['label' => 'فاصل', 'icon' => '─'],
            ['label' => 'مسافة', 'icon' => '↕'],
        ],
        'عناصر المتجر' => [
            ['label' => 'منتجات', 'icon' => '▦'],
            ['label' => 'تصنيفات', 'icon' => '⌘'],
            ['label' => 'منتجات مميزة', 'icon' => '☆'],
            ['label' => 'بطاقة منتج', 'icon' => '□'],
            ['label' => 'عرض خاص', 'icon' => '%'],
            ['label' => 'شريط مزايا', 'icon' => '≋'],
        ],
        'عناصر متقدمة' => [
            ['label' => 'بانر', 'icon' => '▧'],
            ['label' => 'سلايدر', 'icon' => '⇄'],
            ['label' => 'نموذج', 'icon' => '▤'],
            ['label' => 'إحصائيات', 'icon' => '▥'],
            ['label' => 'HTML مخصص', 'icon' => '</>'],
            ['label' => 'خريطة', 'icon' => '⌖'],
        ],
    ];
    $componentGroups = [
        'العناصر الأساسية' => [
            ['label' => 'عنوان', 'icon' => 'T', 'type' => 'rich_text', 'title' => 'عنوان جديد', 'settings' => ['variant' => 'heading', 'headline' => 'عنوان قسم جديد', 'body' => 'اكتب وصفاً مختصراً يظهر في واجهة المتجر.']],
            ['label' => 'نص', 'icon' => '☰', 'type' => 'rich_text', 'title' => 'نص تعريفي', 'settings' => ['variant' => 'paragraph', 'headline' => 'نص تعريفي', 'body' => 'أضف نصاً تسويقياً أو معلومات مهمة للعميل.']],
            ['label' => 'صورة', 'icon' => '▧', 'type' => 'image_text', 'title' => 'صورة مع نص', 'settings' => ['headline' => 'صورة تسويقية', 'body' => 'اربط الصورة بعرض أو تصنيف.', 'image' => 'solve-logo.png']],
            ['label' => 'زر', 'icon' => '▭', 'type' => 'button_cta', 'title' => 'زر دعوة للإجراء', 'settings' => ['label' => 'تسوق الآن', 'url' => 'products', 'style' => 'primary']],
            ['label' => 'فاصل', 'icon' => '─', 'type' => 'divider', 'title' => 'فاصل بصري', 'settings' => ['style' => 'soft']],
            ['label' => 'مسافة', 'icon' => '↕', 'type' => 'spacer', 'title' => 'مسافة بين الأقسام', 'settings' => ['height' => '48']],
        ],
        'عناصر المتجر' => [
            ['label' => 'منتجات', 'icon' => '▦', 'type' => 'featured_products', 'title' => 'منتجات المتجر', 'settings' => ['source' => 'latest', 'limit' => '8']],
            ['label' => 'تصنيفات', 'icon' => '⌘', 'type' => 'categories_grid', 'title' => 'تصنيفات المتجر', 'settings' => ['source' => 'store_categories', 'limit' => '8']],
            ['label' => 'منتجات مميزة', 'icon' => '☆', 'type' => 'featured_products', 'title' => 'منتجات مميزة', 'settings' => ['source' => 'featured', 'limit' => '8']],
            ['label' => 'بطاقة منتج', 'icon' => '□', 'type' => 'product_card', 'title' => 'بطاقة منتج مختار', 'settings' => ['source' => 'first_featured']],
            ['label' => 'عرض خاص', 'icon' => '%', 'type' => 'offers_banner', 'title' => 'عرض خاص', 'settings' => ['source' => 'promotions', 'style' => 'wide']],
            ['label' => 'شريط مزايا', 'icon' => '≋', 'type' => 'trust_bar', 'title' => 'شريط مزايا المتجر', 'settings' => ['source' => 'trust_badges']],
        ],
        'عناصر متقدمة' => [
            ['label' => 'بنر', 'icon' => '▧', 'type' => 'hero', 'title' => 'بنر رئيسي', 'settings' => ['source' => 'active_banner', 'layout' => 'wide']],
            ['label' => 'سلايدر', 'icon' => '⇄', 'type' => 'slider', 'title' => 'سلايدر عروض', 'settings' => ['source' => 'banners', 'limit' => '3']],
            ['label' => 'فيديو', 'icon' => '▶', 'type' => 'video', 'title' => 'فيديو تسويقي', 'settings' => ['headline' => 'شاهد تجربة المتجر', 'body' => 'أضف فيديو قصير يشرح المنتج أو العرض ويزيد ثقة العميل.', 'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'poster' => 'services/banner-storefront.svg', 'cta' => 'تسوق الآن']],
            ['label' => 'نموذج', 'icon' => '▤', 'type' => 'form', 'title' => 'نموذج تواصل', 'settings' => ['headline' => 'تواصل معنا', 'button' => 'إرسال']],
            ['label' => 'إحصائيات', 'icon' => '▥', 'type' => 'stats', 'title' => 'إحصائيات المتجر', 'settings' => ['source' => 'store_metrics']],
            ['label' => 'HTML مخصص', 'icon' => '</>', 'type' => 'custom_html', 'title' => 'HTML مخصص', 'settings' => ['html' => '<div class="custom-storefront-block">محتوى مخصص</div>']],
            ['label' => 'خريطة', 'icon' => '⌖', 'type' => 'map', 'title' => 'موقع المتجر', 'settings' => ['address' => 'Saudi Arabia']],
        ],
    ];
@endphp

@section('partner-content')
<div
    class="fixed inset-0 z-[999] bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-white"
    dir="rtl"
    data-builder-shell
    data-builder-media-upload-url="{{ route('api.partner.storefront.media.upload') }}"
    data-builder-csrf="{{ csrf_token() }}"
    x-data="{ leftTab: 'content', rightTab: 'components', selected: @js($initialSelected), settingsPanel: 'theme', device: @js($builder['device'] ?? 'desktop'), zoom: 100 }"
>
    <span class="sr-only">partner/storefront/customize navigator.clipboard ØªØ¹Ø¯ÙŠÙ„ ÙˆØ§Ø¬Ù‡Ø© Ø§Ù„Ù…ØªØ¬Ø± تعديل واجهة المتجر</span>

    @if (session('status'))
        <div class="absolute left-1/2 top-20 z-20 -translate-x-1/2 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-black text-emerald-700 shadow-lg">
            {{ session('status') }}
        </div>
    @endif
    <div id="builderLiveToast" class="pointer-events-none absolute left-1/2 top-20 z-30 hidden -translate-x-1/2 rounded-2xl border px-5 py-3 text-sm font-black shadow-xl"></div>

    <form id="builder-layout-form" method="POST" action="{{ route('partner.storefront.builder.update') }}" class="hidden">
        @csrf
        @method('PATCH')
        <input type="hidden" name="page" value="home">
        <input type="hidden" name="mode" value="visual">
        <input type="hidden" name="device" :value="device">
        <input type="hidden" name="settings[selected_section]" :value="selected">
        <input type="hidden" name="settings[zoom]" :value="zoom">
        <input type="hidden" name="draft[layout]" value="commerce-builder">
        <input type="hidden" name="draft[autosave]" value="1">
    </form>
    <form id="builder-publish-form" method="POST" action="{{ route('partner.storefront.builder.publish') }}" class="hidden">
        @csrf
    </form>
    <form id="builder-rollback-form" method="POST" action="{{ route('partner.storefront.builder.rollback') }}" class="hidden">
        @csrf
    </form>

    <header class="grid h-[76px] grid-cols-[300px_minmax(0,1fr)_320px] items-center border-b border-slate-200 bg-white/95 px-5 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
        <div class="flex items-center gap-3">
            <button form="builder-layout-form" class="rounded-2xl bg-solve-600 px-6 py-3 text-sm font-black text-white shadow-lg shadow-solve-600/20 transition hover:bg-solve-700">
                حفظ
            </button>
            <button form="builder-publish-form" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900">
                نشر التغييرات
            </button>
            <button type="button" onclick="navigator.clipboard?.writeText(window.location.href)" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-950">
                ...
            </button>
        </div>

        <div class="text-center">
            <div class="flex items-center justify-center gap-2">
                <h1 class="text-xl font-black">تعديل الواجهة</h1>
                <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </div>
            <p class="mt-1 text-xs font-bold text-slate-500">تم حفظ التغييرات تلقائياً، ويمكنك النشر عند الانتهاء.</p>
        </div>

        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('partner.storefront') }}" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-black dark:border-slate-700 dark:bg-slate-950">
                المتجر الإلكتروني
            </a>
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-solve-600 text-xl font-black text-white">S</div>
        </div>
    </header>

    <div class="grid h-[calc(100vh-76px)] grid-cols-[300px_minmax(0,1fr)_330px] overflow-hidden max-lg:grid-cols-1">
        <aside class="flex min-h-0 flex-col border-l border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 max-lg:hidden">
            <div class="grid grid-cols-2 gap-2 border-b border-slate-100 p-4 dark:border-slate-800">
                <button type="button" @click="leftTab = 'settings'" class="rounded-2xl px-4 py-3 text-sm font-black transition" :class="leftTab === 'settings' ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    إعدادات الصفحة
                </button>
                <button type="button" @click="leftTab = 'content'" class="rounded-2xl px-4 py-3 text-sm font-black transition" :class="leftTab === 'content' ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    محتوى الصفحة
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div x-show="leftTab === 'content'" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-black">أقسام الصفحة</h2>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-black text-slate-500 dark:bg-slate-950">Drag Ready</span>
                    </div>

                    <div
                        id="builderSectionSortList"
                        class="space-y-2"
                        data-reorder-url="{{ route('api.partner.storefront.sections.reorder') }}"
                        data-csrf="{{ csrf_token() }}"
                    >
                        @foreach ($sections as $section)
                            @php
                                $sectionId = (string) ($section['id'] ?? $section['key']);
                            @endphp
                            <div
                                draggable="true"
                                data-section-id="{{ $sectionId }}"
                                role="button"
                                tabindex="0"
                                @click="selected = @js($sectionId); rightTab = 'settings'; settingsPanel = 'section'"
                                @keydown.enter.prevent="selected = @js($sectionId); rightTab = 'settings'; settingsPanel = 'section'"
                                @keydown.space.prevent="selected = @js($sectionId); rightTab = 'settings'; settingsPanel = 'section'"
                                class="group flex w-full cursor-pointer items-center gap-2 rounded-2xl border px-3 py-3 text-right transition active:cursor-grabbing"
                                :class="selected === @js($sectionId) ? 'border-solve-300 bg-solve-50 text-solve-700 shadow-sm shadow-solve-100 dark:border-solve-500/30 dark:bg-solve-500/10 dark:text-solve-200' : 'border-slate-200 bg-white text-slate-700 hover:border-solve-100 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-300'"
                            >
                                <button type="button" class="flex min-w-0 flex-1 items-center gap-3 text-right" @click.stop="selected = @js($sectionId); rightTab = 'settings'; settingsPanel = 'section'">
                                    @include('partner.partials.icon', ['name' => $section['icon'], 'class' => 'h-4 w-4 shrink-0 text-slate-400'])
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-black">{{ $section['label'] }}</span>
                                        <span class="mt-1 block truncate text-[11px] font-bold text-slate-400">{{ $section['meta'] }}</span>
                                    </span>
                                </button>
                                <button
                                    type="button"
                                    class="rounded-xl bg-solve-50 px-2.5 py-1.5 text-[10px] font-black text-solve-700 opacity-0 transition group-hover:opacity-100 dark:bg-solve-500/10 dark:text-solve-200"
                                    @click.stop="selected = @js($sectionId); rightTab = 'settings'; settingsPanel = 'section'"
                                >
                                    تعديل
                                </button>
                                <span class="text-lg text-slate-300">⋮⋮</span>
                                @if (! empty($section['id']))
                                    <button type="button" class="builder-section-delete-button rounded-xl p-2 text-slate-300 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-500/10" data-delete-url="{{ route('api.partner.storefront.sections.delete', ['sectionRecord' => $section['id']]) }}" data-section-title="{{ $section['label'] }}" title="حذف القسم" aria-label="حذف القسم">
                                        @include('partner.partials.icon', ['name' => 'trash', 'class' => 'h-4 w-4'])
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <p id="builderSectionSortStatus" class="min-h-5 text-[11px] font-black text-slate-400">
                        اسحب أي قسم لتغيير ترتيبه، وسيتم حفظ الترتيب تلقائياً.
                    </p>

                    <button type="button" @click="rightTab = 'settings'; settingsPanel = 'section'" class="mt-4 w-full rounded-2xl bg-solve-50 px-4 py-4 text-sm font-black text-solve-700 transition hover:bg-solve-100 dark:bg-solve-500/10 dark:text-solve-200">
                        إضافة قسم جديد +
                    </button>
                </div>

                <div x-show="leftTab === 'settings'" class="space-y-4">
                    <h2 class="text-lg font-black">إعدادات الصفحة</h2>
                    <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                        <p class="text-xs font-black text-slate-400">الصفحة الحالية</p>
                        <p class="mt-2 text-sm font-black">الصفحة الرئيسية</p>
                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $previewUrl }}</p>
                    </div>
                    <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                        <p class="text-xs font-black text-slate-400">جاهزية الواجهة</p>
                        <div class="mt-3 h-2 rounded-full bg-white dark:bg-slate-900">
                            <div class="h-2 rounded-full bg-solve-600" style="width: {{ $readyPercent }}%"></div>
                        </div>
                        <p class="mt-2 text-xs font-bold text-slate-500">{{ $readyPercent }}% مكتمل</p>
                    </div>
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="block rounded-2xl border border-slate-200 px-4 py-3 text-center text-sm font-black transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                        معاينة المتجر
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-1 border-t border-slate-100 p-3 text-slate-400 dark:border-slate-800">
                @foreach (['trash', 'copy', 'sparkles', 'settings'] as $icon)
                    <button type="button" class="rounded-xl p-3 transition hover:bg-slate-50 hover:text-solve-700 dark:hover:bg-slate-800">
                        @include('partner.partials.icon', ['name' => $icon, 'class' => 'mx-auto h-4 w-4'])
                    </button>
                @endforeach
            </div>
        </aside>

        <main class="min-w-0 overflow-hidden bg-slate-50 dark:bg-slate-950">
            <div class="flex h-[76px] items-center justify-between border-b border-slate-200 bg-white px-4 dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center gap-2">
                    <select class="h-11 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-black dark:border-slate-700 dark:bg-slate-950">
                        <option>الصفحة الرئيسية</option>
                    </select>
                    <span class="rounded-2xl bg-slate-50 px-4 py-3 text-xs font-black text-slate-500 dark:bg-slate-950">{{ $previewUrl }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="device='desktop'" class="rounded-2xl border px-4 py-3 transition" :class="device==='desktop' ? 'border-solve-300 bg-solve-50 text-solve-700' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-950'">
                        @include('partner.partials.icon', ['name' => 'monitor', 'class' => 'h-5 w-5'])
                    </button>
                    <button type="button" @click="device='tablet'" class="rounded-2xl border px-4 py-3 transition" :class="device==='tablet' ? 'border-solve-300 bg-solve-50 text-solve-700' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-950'">
                        @include('partner.partials.icon', ['name' => 'tablet', 'class' => 'h-5 w-5'])
                    </button>
                    <button type="button" @click="device='mobile'" class="rounded-2xl border px-4 py-3 transition" :class="device==='mobile' ? 'border-solve-300 bg-solve-50 text-solve-700' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-950'">
                        @include('partner.partials.icon', ['name' => 'mobile', 'class' => 'h-5 w-5'])
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-400 dark:border-slate-700 dark:bg-slate-950">
                        @include('partner.partials.icon', ['name' => 'undo', 'class' => 'h-4 w-4'])
                    </button>
                    <button type="button" class="rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-400 dark:border-slate-700 dark:bg-slate-950">
                        @include('partner.partials.icon', ['name' => 'redo', 'class' => 'h-4 w-4'])
                    </button>
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-950">
                        معاينة
                    </a>
                </div>
            </div>

            <div class="h-[calc(100vh-152px)] overflow-auto p-5">
                <div class="mx-auto rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-xl transition-all dark:border-slate-800 dark:bg-slate-900"
                    :class="device === 'mobile' ? 'max-w-[430px]' : (device === 'tablet' ? 'max-w-3xl' : 'max-w-6xl')"
                    x-bind:style="'transform: scale(' + (zoom / 100) + '); transform-origin: top center;'">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-950">
                        <div class="mx-auto h-8 max-w-4xl rounded-xl border border-slate-200 bg-white px-4 py-1 text-xs font-bold text-slate-400 dark:border-slate-700 dark:bg-slate-900">
                            {{ $previewUrl }}
                        </div>
                    </div>

                    <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-inner dark:border-slate-800 dark:bg-slate-950"
                        :class="device === 'mobile' ? 'h-[760px]' : (device === 'tablet' ? 'h-[780px]' : 'h-[820px]')">
                        <iframe
                            id="storefrontLivePreview"
                            title="Live storefront preview"
                            src="{{ $previewFrameUrl }}"
                            class="h-full w-full bg-white"
                            loading="lazy"
                            referrerpolicy="same-origin"
                        ></iframe>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs font-bold text-slate-500 dark:border-slate-800 dark:bg-slate-950">
                        <span>المعاينة هنا تعرض واجهة المتجر الحقيقية، وأي Section تضيفه من الأدوات يظهر داخل نفس المتجر بعد الحفظ.</span>
                        <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="rounded-xl bg-slate-950 px-4 py-2 text-white dark:bg-white dark:text-slate-950">فتح المتجر</a>
                    </div>
                </div>

                <div id="builderStaticPreview" class="hidden mx-auto rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-xl transition-all dark:border-slate-800 dark:bg-slate-900"
                    :class="device === 'mobile' ? 'max-w-[430px]' : (device === 'tablet' ? 'max-w-3xl' : 'max-w-6xl')"
                    x-bind:style="'transform: scale(' + (zoom / 100) + '); transform-origin: top center;'">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-950">
                        <div class="mx-auto h-8 max-w-4xl rounded-xl border border-slate-200 bg-white px-4 py-1 text-xs font-bold text-slate-400 dark:border-slate-700 dark:bg-slate-900">
                            https://atlas.solve.sa
                        </div>
                    </div>

                    <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                        <div class="bg-slate-950 px-5 py-3 text-center text-sm font-black text-white">
                            خصم 20% على جميع المنتجات لفترة محدودة
                        </div>

                        <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5 dark:border-slate-800">
                            <div class="flex items-center gap-4">
                                <button class="rounded-2xl p-2 text-slate-800 dark:text-white">@include('partner.partials.icon', ['name' => 'shopping-bag', 'class' => 'h-5 w-5'])</button>
                                <button class="rounded-2xl p-2 text-slate-800 dark:text-white">@include('partner.partials.icon', ['name' => 'heart', 'class' => 'h-5 w-5'])</button>
                                <button class="rounded-2xl p-2 text-slate-800 dark:text-white">@include('partner.partials.icon', ['name' => 'search', 'class' => 'h-5 w-5'])</button>
                            </div>
                            <nav class="hidden items-center gap-6 text-sm font-black text-slate-600 dark:text-slate-300 md:flex">
                                @forelse ($headerMenu as $item)
                                    <span>{{ $item['label'] ?? '-' }}</span>
                                @empty
                                    <span>الرئيسية</span><span>المتجر</span><span>التصنيفات</span><span>العروض</span>
                                @endforelse
                            </nav>
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-black">{{ $storeName }}</span>
                                <img src="{{ asset($logo) }}" alt="{{ $storeName }}" class="h-10 w-16 object-contain">
                            </div>
                        </div>

                        <section class="relative min-h-[360px] overflow-hidden p-8 text-white" style="background: linear-gradient(135deg, {{ $primary }}, {{ $secondary }});">
                            <div class="absolute inset-0 opacity-25" style="background-image: radial-gradient(circle at 25% 35%, white 0, transparent 18%), radial-gradient(circle at 75% 25%, white 0, transparent 12%);"></div>
                            <div class="relative grid gap-8 lg:grid-cols-[.9fr_1.1fr] lg:items-center">
                                <div class="hidden lg:block">
                                    <div class="grid h-72 grid-cols-3 items-end gap-3">
                                        <div class="h-32 rounded-[1.75rem] bg-white/30 backdrop-blur"></div>
                                        <div class="h-40 rounded-[1.75rem] bg-white/25 backdrop-blur"></div>
                                        <div class="h-28 rounded-[1.75rem] bg-white/35 backdrop-blur"></div>
                                    </div>
                                </div>
                                <div>
                                    <span class="rounded-full bg-white/20 px-4 py-2 text-xs font-black">عرض المتجر</span>
                                    <h2 class="mt-5 text-5xl font-black leading-tight">أسلوبك يبدأ من هنا</h2>
                                    <p class="mt-4 text-lg font-bold text-white/85">اكتشف أحدث المنتجات بأفضل الأسعار وجودة مضمونة من {{ $storeName }}.</p>
                                    <button class="mt-8 rounded-2xl bg-white px-8 py-4 text-sm font-black text-slate-950 shadow-lg shadow-slate-950/20">تسوق الآن</button>
                                </div>
                            </div>
                        </section>

                        <section class="grid gap-4 border-b border-slate-100 px-8 py-6 text-center dark:border-slate-800 md:grid-cols-4">
                            @foreach ([['شحن سريع', 'خلال 24 - 48 ساعة'], ['دفع آمن 100%', 'جميع طرق الدفع آمنة'], ['ضمان استرجاع', 'خلال 14 يوم'], ['دعم 24/7', 'على مدار الساعة']] as $benefit)
                                <div>
                                    <p class="font-black text-slate-950 dark:text-white">{{ $benefit[0] }}</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">{{ $benefit[1] }}</p>
                                </div>
                            @endforeach
                        </section>

                        <section class="px-8 py-8">
                            <h3 class="text-center text-2xl font-black">تصفح التصنيفات</h3>
                            <div class="mt-6 grid gap-4 md:grid-cols-5">
                                @foreach ($categories as $category)
                                    <div class="rounded-2xl border border-slate-200 p-4 text-center dark:border-slate-800">
                                        <div class="mx-auto h-24 rounded-2xl bg-slate-100 dark:bg-slate-900"></div>
                                        <p class="mt-3 text-sm font-black">{{ $category }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="bg-slate-50 px-8 py-8 dark:bg-slate-900/50">
                            <h3 class="text-2xl font-black">منتجات مميزة</h3>
                            <div class="mt-6 grid gap-4 md:grid-cols-4">
                                @forelse ($products as $product)
                                    <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                        <div class="h-32 rounded-2xl bg-slate-100 dark:bg-slate-900"></div>
                                        <p class="mt-3 text-sm font-black">{{ $product['name'] ?? 'منتج مميز' }}</p>
                                        <p class="mt-1 text-xs font-bold text-slate-500">{{ $product['price'] ?? '129 ر.س' }}</p>
                                        <button class="mt-3 w-full rounded-2xl bg-slate-950 px-4 py-3 text-xs font-black text-white dark:bg-white dark:text-slate-950">أضف للسلة</button>
                                    </div>
                                @empty
                                    @foreach (range(1, 4) as $index)
                                        <div class="rounded-3xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-950">
                                            <div class="h-32 rounded-2xl bg-slate-100 dark:bg-slate-900"></div>
                                            <p class="mt-3 text-sm font-black">منتج مميز {{ $index }}</p>
                                            <p class="mt-1 text-xs font-bold text-slate-500">129 ر.س</p>
                                            <button class="mt-3 w-full rounded-2xl bg-slate-950 px-4 py-3 text-xs font-black text-white dark:bg-white dark:text-slate-950">أضف للسلة</button>
                                        </div>
                                    @endforeach
                                @endforelse
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>

        <aside class="flex min-h-0 flex-col border-r border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900 max-lg:hidden">
            <div class="grid grid-cols-2 gap-2 border-b border-slate-100 p-4 dark:border-slate-800">
                <button type="button" @click="rightTab = 'settings'" class="rounded-2xl px-4 py-3 text-sm font-black transition" :class="rightTab === 'settings' ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    إعدادات القسم
                </button>
                <button type="button" @click="rightTab = 'components'" class="rounded-2xl px-4 py-3 text-sm font-black transition" :class="rightTab === 'components' ? 'bg-solve-50 text-solve-700 dark:bg-solve-500/10 dark:text-solve-200' : 'text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800'">
                    المكونات
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <div x-show="rightTab === 'components'" class="space-y-6">
                    @foreach ($componentGroups as $group => $items)
                        <div>
                            <h3 class="mb-3 text-sm font-black text-slate-500">{{ $group }}</h3>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach ($items as $item)
                                    <form method="POST" action="{{ route('partner.storefront.sections.store') }}" data-api-url="{{ route('api.partner.storefront.sections.store') }}" data-component-label="{{ $item['label'] }}" class="builder-component-form contents">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $item['type'] }}">
                                        <input type="hidden" name="title" value="{{ $item['title'] ?? $item['label'] }}">
                                        <input type="hidden" name="placement" value="{{ $item['placement'] ?? 'home' }}">
                                        <input type="hidden" name="sort_order" value="{{ count($sections) + ($loop->parent->iteration * 20) + $loop->iteration }}">
                                        <input type="hidden" name="status" value="active">
                                        <input type="hidden" name="visible" value="1">
                                        @foreach (($item['settings'] ?? []) as $settingKey => $settingValue)
                                            @if (is_array($settingValue))
                                                @foreach ($settingValue as $nestedValue)
                                                    <input type="hidden" name="settings[{{ $settingKey }}][]" value="{{ $nestedValue }}">
                                                @endforeach
                                            @else
                                                <input type="hidden" name="settings[{{ $settingKey }}]" value="{{ $settingValue }}">
                                            @endif
                                        @endforeach
                                        <button type="submit" class="group rounded-2xl border border-slate-200 bg-white p-3 text-center transition hover:-translate-y-0.5 hover:border-solve-200 hover:bg-solve-50 hover:text-solve-700 hover:shadow-lg hover:shadow-solve-100 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-solve-500/10">
                                            <span class="block text-xl font-black">{{ $item['icon'] }}</span>
                                            <span class="mt-2 block text-xs font-black">{{ $item['label'] }}</span>
                                            <span class="mt-1 block rounded-full bg-solve-50 px-2 py-1 text-[10px] font-black text-solve-700 transition group-hover:bg-solve-100">إضافة</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="rounded-3xl bg-solve-50 p-4 dark:bg-solve-500/10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-solve-700 shadow-sm dark:bg-slate-900">▶</span>
                            <div>
                                <p class="text-sm font-black">تحتاج مساعدة؟</p>
                                <p class="mt-1 text-xs font-bold text-slate-500 dark:text-slate-300">شاهد دليل استخدام محرر الواجهة</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div x-show="rightTab === 'settings'" class="space-y-4">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="settingsPanel='section'" class="rounded-full px-3 py-2 text-xs font-black transition" :class="settingsPanel === 'section' ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-50 text-slate-500 dark:bg-slate-950'">
                            القسم
                        </button>
                        @foreach (['theme' => 'القالب', 'identity' => 'الهوية', 'banner' => 'بنر', 'navigation' => 'القوائم', 'seo' => 'SEO', 'domain' => 'الدومين'] as $key => $label)
                            <button type="button" @click="settingsPanel='{{ $key }}'" class="rounded-full px-3 py-2 text-xs font-black transition" :class="settingsPanel === '{{ $key }}' ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950' : 'bg-slate-50 text-slate-500 dark:bg-slate-950'">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    <div x-show="settingsPanel === 'section'" class="space-y-4">
                        <div class="rounded-3xl border border-solve-100 bg-white p-4 shadow-sm dark:border-solve-500/20 dark:bg-slate-950">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-slate-950 dark:text-white">تعديل القسم المحدد</p>
                                    <p class="mt-1 text-xs font-bold text-slate-500">اختر أي قسم من قائمة الصفحة ثم عدل عنوانه وحالته وترتيبه ومحتواه.</p>
                                </div>
                                <span class="rounded-full bg-solve-50 px-3 py-1 text-[11px] font-black text-solve-700">Live</span>
                            </div>

                            <?php foreach ($sortedBuilderRows as $row): ?>
                                <?php
                                    $rowId = $row['id'] ?? '';
                                    $rowSettings = $row['settings'] ?? [];
                                ?>
                                <form
                                    x-show="selected === @js($rowId)"
                                    method="POST"
                                    action="{{ route('partner.storefront.sections.update', ['sectionRecord' => $rowId]) }}"
                                    data-api-url="{{ route('api.partner.storefront.sections.update', ['sectionRecord' => $rowId]) }}"
                                    class="builder-section-edit-form space-y-3"
                                >
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $row['type'] ?? 'custom' }}">
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="block">
                                            <span class="text-[11px] font-black text-slate-500">العنوان</span>
                                            <input name="title" required value="{{ $row['title'] ?? '-' }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">
                                        </label>
                                        <label class="block">
                                            <span class="text-[11px] font-black text-slate-500">الترتيب</span>
                                            <input name="sort_order" type="number" min="1" value="{{ $row['sort_order'] ?? 1 }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">
                                        </label>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="block">
                                            <span class="text-[11px] font-black text-slate-500">المكان</span>
                                            <select name="placement" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">
                                                @foreach (['home' => 'الرئيسية', 'top' => 'أعلى الصفحة', 'header' => 'الهيدر', 'footer' => 'الفوتر', 'mobile' => 'الموبايل'] as $placementKey => $placementLabel)
                                                    <option value="{{ $placementKey }}" @selected(($row['placement'] ?? 'home') === $placementKey)>{{ $placementLabel }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block">
                                            <span class="text-[11px] font-black text-slate-500">الحالة</span>
                                            <select name="status" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-black dark:border-slate-700 dark:bg-slate-900">
                                                <option value="active" @selected(($row['status_key'] ?? $row['status'] ?? '') === 'active')>نشط</option>
                                                <option value="draft" @selected(($row['status_key'] ?? $row['status'] ?? '') === 'draft')>مسودة</option>
                                                <option value="hidden" @selected(($row['status_key'] ?? $row['status'] ?? '') === 'hidden')>مخفي</option>
                                            </select>
                                        </label>
                                    </div>
                                    <label class="block">
                                        <span class="text-[11px] font-black text-slate-500">عنوان داخل القسم</span>
                                        <input name="settings[headline]" value="{{ $rowSettings['headline'] ?? $rowSettings['title'] ?? $row['title'] ?? '' }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-900" placeholder="مثال: عروض الموسم">
                                    </label>
                                    <label class="block">
                                        <span class="text-[11px] font-black text-slate-500">النص / الوصف</span>
                                        <textarea name="settings[body]" rows="3" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-900" placeholder="اكتب النص الذي سيظهر داخل هذا القسم">{{ $rowSettings['body'] ?? $rowSettings['text'] ?? '' }}</textarea>
                                    </label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label class="block">
                                            <span class="text-[11px] font-black text-slate-500">زر CTA</span>
                                            <input name="settings[cta]" value="{{ $rowSettings['cta'] ?? $rowSettings['label'] ?? '' }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-900" placeholder="تسوق الآن">
                                        </label>
                                        <label class="block">
                                            <span class="text-[11px] font-black text-slate-500">الرابط</span>
                                            <input name="settings[url]" value="{{ $rowSettings['url'] ?? '' }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-900" placeholder="products">
                                        </label>
                                    </div>
                                    @php
                                        $sectionImageValue = $rowSettings['image'] ?? $rowSettings['image_url'] ?? '';
                                        $sectionPosterValue = $rowSettings['poster'] ?? '';
                                        $sectionPreviewValue = $sectionImageValue ?: $sectionPosterValue;
                                        $sectionPreviewUrl = $sectionPreviewValue;
                                        if ($sectionPreviewUrl && ! str_starts_with($sectionPreviewUrl, 'http') && ! str_starts_with($sectionPreviewUrl, '/') && ! str_starts_with($sectionPreviewUrl, 'data:')) {
                                            $sectionPreviewUrl = asset($sectionPreviewUrl);
                                        }
                                    @endphp
                                    <div data-builder-media-card class="rounded-3xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                        <div class="mb-3 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-xs font-black text-slate-950 dark:text-white">الصور والفيديو</p>
                                                <p class="mt-1 text-[11px] font-bold text-slate-500">ارفع صورة، عدل الرابط الحالي، أو أضف فيديو للقسم.</p>
                                            </div>
                                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 dark:bg-slate-950">Media</span>
                                        </div>
                                        <div class="grid gap-3">
                                            <div class="overflow-hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                                <img
                                                    data-builder-image-preview
                                                    src="{{ $sectionPreviewUrl ?: asset('solve-logo.png') }}"
                                                    alt="{{ $row['title'] ?? 'section image' }}"
                                                    class="{{ $sectionPreviewUrl ? '' : 'hidden' }} h-36 w-full object-cover"
                                                >
                                                <div data-builder-image-empty class="{{ $sectionPreviewUrl ? 'hidden' : '' }} grid h-36 place-items-center px-4 text-center text-xs font-black text-slate-400">
                                                    لم يتم اختيار صورة بعد.
                                                </div>
                                            </div>
                                            <label class="block">
                                                <span class="text-[11px] font-black text-slate-500">رابط الصورة</span>
                                                <input data-builder-image-input name="settings[image]" value="{{ $sectionImageValue }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="services/banner-products.svg أو https://...">
                                            </label>
                                            <div class="grid grid-cols-[1fr_auto] gap-2">
                                                <label class="flex cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs font-black transition hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900">
                                                    <input data-builder-image-file type="file" accept="image/*" class="hidden">
                                                    رفع صورة من الجهاز
                                                </label>
                                                <button type="button" data-builder-image-clear class="rounded-2xl border border-red-200 px-4 py-3 text-xs font-black text-red-600">مسح</button>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <label class="block">
                                                    <span class="text-[11px] font-black text-slate-500">رابط الفيديو</span>
                                                    <input data-builder-video-input name="settings[video_url]" value="{{ $rowSettings['video_url'] ?? '' }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="YouTube / Vimeo / MP4">
                                                </label>
                                                <label class="block">
                                                    <span class="text-[11px] font-black text-slate-500">صورة غلاف الفيديو</span>
                                                    <input data-builder-poster-input name="settings[poster]" value="{{ $sectionPosterValue }}" class="mt-1 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="poster image">
                                                </label>
                                            </div>
                                            <div class="grid grid-cols-[1fr_auto] gap-2">
                                                <label class="flex cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs font-black transition hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900">
                                                    <input data-builder-video-file type="file" accept="video/mp4,video/webm,video/ogg" class="hidden">
                                                    رفع فيديو صغير
                                                </label>
                                                <button type="button" data-builder-video-clear class="rounded-2xl border border-red-200 px-4 py-3 text-xs font-black text-red-600">مسح الفيديو</button>
                                            </div>
                                            <div data-builder-video-preview class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 p-2 dark:border-slate-700"></div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="visible" value="0">
                                    <label class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 p-3 text-xs font-black dark:bg-slate-900">
                                        <span>إظهار القسم في المعاينة والمتجر</span>
                                        <input type="checkbox" name="visible" value="1" @checked(! empty($row['visible']))>
                                    </label>
                                    <div class="grid grid-cols-[1fr_auto] gap-2">
                                        <button class="builder-save-button rounded-2xl bg-slate-950 px-4 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">حفظ التعديل</button>
                                        <button type="button" onclick="navigator.clipboard?.writeText('{{ url('/api/partner/storefront/sections/' . $rowId) }}')" class="rounded-2xl border border-slate-200 px-4 py-3 text-xs font-black dark:border-slate-700">API</button>
                                    </div>
                                </form>
                            <?php endforeach; ?>

                            <div x-show="!selected" class="rounded-2xl bg-slate-50 p-4 text-xs font-black text-slate-500 dark:bg-slate-900">
                                اختر قسماً من قائمة أقسام الصفحة لعرض أدوات التعديل.
                            </div>
                        </div>

                        <form method="POST" action="{{ route('partner.storefront.sections.store') }}" data-api-url="{{ route('api.partner.storefront.sections.store') }}" data-component-label="Section جديد" class="builder-component-form space-y-3 rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            @csrf
                            <div>
                                <p class="text-sm font-black text-slate-950 dark:text-white">إضافة Section جديد</p>
                                <p class="mt-1 text-xs font-bold text-slate-500">يُحفظ مباشرة في Builder ويرتبط بمتجر {{ $partner['store_id'] ?? '-' }} فقط.</p>
                            </div>
                            <input name="title" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="عنوان القسم">
                            <div class="grid grid-cols-2 gap-2">
                                <select name="type" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                    <option value="hero">Hero Banner</option>
                                    <option value="slider">Slider</option>
                                    <option value="video">Video</option>
                                    <option value="featured_products">Featured Products</option>
                                    <option value="categories_grid">Categories Grid</option>
                                    <option value="offers_banner">Offers Banner</option>
                                    <option value="countdown">Countdown</option>
                                    <option value="testimonials">Testimonials</option>
                                    <option value="faq">FAQ</option>
                                    <option value="whatsapp_cta">WhatsApp CTA</option>
                                    <option value="ai_recommendations">AI Recommendations</option>
                                </select>
                                <select name="placement" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                    <option value="home">Home</option>
                                    <option value="top">Top</option>
                                    <option value="header">Header</option>
                                    <option value="footer">Footer</option>
                                    <option value="mobile">Mobile</option>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <input name="sort_order" type="number" value="{{ count($sections) + 1 }}" min="1" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="الترتيب">
                                <select name="status" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                    <option value="active">نشط</option>
                                    <option value="draft">مسودة</option>
                                    <option value="hidden">مخفي</option>
                                </select>
                            </div>
                            <div class="grid gap-2 rounded-3xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                <input name="settings[headline]" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="عنوان داخل القسم">
                                <textarea name="settings[body]" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="وصف مختصر يظهر داخل القسم"></textarea>
                                <div class="grid grid-cols-2 gap-2">
                                    <input name="settings[cta]" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="نص الزر">
                                    <input name="settings[url]" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="رابط الزر مثل products">
                                </div>
                            </div>
                            <div data-builder-media-card class="rounded-3xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black text-slate-950 dark:text-white">صورة أو فيديو للقسم</p>
                                        <p class="mt-1 text-[11px] font-bold text-slate-500">استخدم رابط صورة/فيديو أو ارفع ملفاً صغيراً للمعاينة والحفظ.</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 dark:bg-slate-950">Media</span>
                                </div>
                                <div class="grid gap-3">
                                    <div class="overflow-hidden rounded-2xl border border-dashed border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                                        <img data-builder-image-preview src="{{ asset('solve-logo.png') }}" alt="section image" class="hidden h-36 w-full object-cover">
                                        <div data-builder-image-empty class="grid h-36 place-items-center px-4 text-center text-xs font-black text-slate-400">
                                            لم يتم اختيار صورة بعد.
                                        </div>
                                    </div>
                                    <input data-builder-image-input name="settings[image]" class="w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="رابط الصورة أو مسار داخل public">
                                    <div class="grid grid-cols-[1fr_auto] gap-2">
                                        <label class="flex cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs font-black transition hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900">
                                            <input data-builder-image-file type="file" accept="image/*" class="hidden">
                                            رفع صورة
                                        </label>
                                        <button type="button" data-builder-image-clear class="rounded-2xl border border-red-200 px-4 py-3 text-xs font-black text-red-600">مسح</button>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input data-builder-video-input name="settings[video_url]" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="رابط فيديو YouTube / Vimeo / MP4">
                                        <input data-builder-poster-input name="settings[poster]" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="غلاف الفيديو">
                                    </div>
                                    <div class="grid grid-cols-[1fr_auto] gap-2">
                                        <label class="flex cursor-pointer items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 text-xs font-black transition hover:bg-white dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-slate-900">
                                            <input data-builder-video-file type="file" accept="video/mp4,video/webm,video/ogg" class="hidden">
                                            رفع فيديو صغير
                                        </label>
                                        <button type="button" data-builder-video-clear class="rounded-2xl border border-red-200 px-4 py-3 text-xs font-black text-red-600">مسح الفيديو</button>
                                    </div>
                                    <div data-builder-video-preview class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-slate-950 p-2 dark:border-slate-700"></div>
                                </div>
                            </div>
                            <input type="hidden" name="visible" value="0">
                            <label class="flex items-center gap-2 rounded-2xl bg-white p-3 text-xs font-black dark:bg-slate-900">
                                <input type="checkbox" name="visible" value="1" checked>
                                يظهر في المعاينة والمتجر بعد النشر
                            </label>
                            <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">إضافة القسم</button>
                        </form>

                        <div class="space-y-3">
                            <?php foreach ($sortedBuilderRows as $row): ?>
                                <div class="rounded-3xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                                    <form method="POST" action="{{ route('partner.storefront.sections.update', ['sectionRecord' => $row['id']]) }}" class="space-y-2">
                                        @csrf
                                        <input type="hidden" name="type" value="{{ $row['type'] ?? 'custom' }}">
                                        <input type="hidden" name="placement" value="{{ $row['placement'] ?? 'home' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <input name="title" required value="{{ $row['title'] ?? '-' }}" class="min-w-0 flex-1 rounded-2xl border border-slate-200 px-3 py-2 text-xs font-black dark:border-slate-700 dark:bg-slate-900">
                                            <input name="sort_order" type="number" min="1" value="{{ $row['sort_order'] ?? 1 }}" class="w-20 rounded-2xl border border-slate-200 px-3 py-2 text-xs font-black dark:border-slate-700 dark:bg-slate-900">
                                        </div>
                                        <div class="grid grid-cols-[1fr_auto] gap-2">
                                            <select name="status" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-black dark:border-slate-700 dark:bg-slate-900">
                                                <option value="active" @selected(($row['status_key'] ?? $row['status'] ?? '') === 'active')>نشط</option>
                                                <option value="draft" @selected(($row['status_key'] ?? $row['status'] ?? '') === 'draft')>مسودة</option>
                                                <option value="hidden" @selected(($row['status_key'] ?? $row['status'] ?? '') === 'hidden')>مخفي</option>
                                            </select>
                                            <input type="hidden" name="visible" value="0">
                                            <label class="flex items-center gap-2 rounded-2xl bg-slate-50 px-3 py-2 text-xs font-black dark:bg-slate-900">
                                                <input type="checkbox" name="visible" value="1" @checked(! empty($row['visible']))>
                                                ظاهر
                                            </label>
                                        </div>
                                        <div class="flex gap-2">
                                            <button class="flex-1 rounded-2xl bg-slate-950 px-3 py-2 text-xs font-black text-white dark:bg-white dark:text-slate-950">حفظ</button>
                                            <button type="button" onclick="navigator.clipboard?.writeText('{{ url('/api/partner/storefront/sections/' . ($row['id'] ?? '')) }}')" class="rounded-2xl border border-slate-200 px-3 py-2 text-xs font-black dark:border-slate-700">API</button>
                                        </div>
                                    </form>
                                    <form method="POST" action="{{ route('partner.storefront.sections.delete', ['sectionRecord' => $row['id']]) }}" class="mt-2" onsubmit="return confirm('حذف هذا القسم من مسودة الواجهة؟')">
                                        @csrf
                                        <button class="w-full rounded-2xl border border-red-200 px-3 py-2 text-xs font-black text-red-600">حذف القسم</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <form
                        id="builder-theme-form"
                        method="POST"
                        action="{{ route('partner.storefront.themes.customize', ['theme' => $themeId]) }}"
                        data-api-url="{{ route('api.partner.themes.customize', ['theme' => $themeId]) }}"
                        data-live-save="1"
                        class="space-y-3"
                        x-show="settingsPanel === 'theme'"
                    >
                        @csrf
                        <label class="block">
                            <span class="text-xs font-black">اللون الأساسي</span>
                            <input type="color" name="primary_color" value="{{ $primary }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <label class="block">
                            <span class="text-xs font-black">اللون الثانوي</span>
                            <input type="color" name="secondary_color" value="{{ $secondary }}" class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-950">
                        </label>
                        <label class="block">
                            <span class="text-xs font-black">الخط</span>
                            <select name="font" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                <option @selected(($currentTheme['font'] ?? '') === 'Tajawal')>Tajawal</option>
                                <option @selected(($currentTheme['font'] ?? '') === 'IBM Plex Sans Arabic')>IBM Plex Sans Arabic</option>
                                <option @selected(($currentTheme['font'] ?? '') === 'Cairo')>Cairo</option>
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="block">
                                <span class="text-xs font-black">الهيدر</span>
                                <select name="header_style" class="mt-2 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                    <option value="compact" @selected(($currentTheme['header_style'] ?? 'compact') === 'compact')>مضغوط</option>
                                    <option value="mega" @selected(($currentTheme['header_style'] ?? '') === 'mega')>ميجا</option>
                                    <option value="centered" @selected(($currentTheme['header_style'] ?? '') === 'centered')>متوسط</option>
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs font-black">الفوتر</span>
                                <select name="footer_style" class="mt-2 w-full rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                    <option value="rich" @selected(($currentTheme['footer_style'] ?? 'rich') === 'rich')>غني</option>
                                    <option value="columns" @selected(($currentTheme['footer_style'] ?? '') === 'columns')>أعمدة</option>
                                    <option value="minimal" @selected(($currentTheme['footer_style'] ?? '') === 'minimal')>بسيط</option>
                                </select>
                            </label>
                        </div>
                        <input type="hidden" name="card_style" value="{{ $currentTheme['card_style'] ?? 'soft' }}">
                        <input type="hidden" name="button_style" value="{{ $currentTheme['button_style'] ?? 'rounded' }}">
                        <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">حفظ تخصيص القالب</button>
                        <label class="flex items-center gap-2 rounded-2xl bg-slate-50 p-3 text-xs font-black dark:bg-slate-950">
                            <input type="hidden" name="supports_dark" value="0">
                            <input type="checkbox" name="supports_dark" value="1" @checked(! empty($currentTheme['supports_dark']))>
                            دعم الوضع الليلي في الواجهة
                        </label>
                    </form>

                    <form method="POST" action="{{ route('partner.storefront.settings.update') }}" class="space-y-3" x-show="settingsPanel === 'identity'">
                        @csrf
                        <input name="store_name" required value="{{ $storeName }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="اسم المتجر">
                        <input name="logo" value="{{ $logo }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="رابط الشعار">
                        <input name="favicon" value="{{ $favicon }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="الفافيكون">
                        <input name="contact_email" type="email" value="{{ $settings['contact_email'] ?? ($partner['email'] ?? '') }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="البريد الرسمي">
                        <input name="contact_phone" value="{{ $settings['contact_phone'] ?? '' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="الجوال">
                        <input name="working_hours" value="{{ $settings['working_hours'] ?? 'يومياً 9 ص - 10 م' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="أوقات العمل">
                        <div class="grid grid-cols-2 gap-3">
                            <input name="language" required value="{{ $settings['language'] ?? 'ar' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="اللغة">
                            <input name="currency" required value="{{ $settings['currency'] ?? 'SAR' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="العملة">
                        </div>
                        <textarea name="social_links" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="روابط السوشيال">@foreach ($socialLinks as $link){{ $link }}
@endforeach</textarea>
                        <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">حفظ الهوية</button>
                    </form>

                    <form method="POST" action="{{ route('partner.storefront.banners.store') }}" class="space-y-3" x-show="settingsPanel === 'banner'">
                        @csrf
                        <input name="title" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="عنوان البنر">
                        <input name="image_url" required class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="رابط الصورة">
                        <input name="link_target" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="/products">
                        <div class="grid grid-cols-2 gap-3">
                            <select name="placement" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                <option value="home_hero">Hero</option>
                                <option value="home_secondary">وسط الصفحة</option>
                                <option value="popup">Popup</option>
                            </select>
                            <select name="status" class="rounded-2xl border border-slate-200 px-3 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                <option value="active">نشط</option>
                                <option value="scheduled">مجدول</option>
                                <option value="inactive">متوقف</option>
                            </select>
                        </div>
                        <input type="hidden" name="link_type" value="url">
                        <input type="hidden" name="sort_order" value="1">
                        <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">إضافة بنر</button>
                    </form>

                    <form method="POST" action="{{ route('partner.storefront.navigation.update') }}" class="space-y-3" x-show="settingsPanel === 'navigation'">
                        @csrf
                        <label class="block">
                            <span class="text-xs font-black">Header Menu</span>
                            <textarea name="header_menu" rows="5" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $menuToText($headerMenu) }}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-xs font-black">Footer Menu</span>
                            <textarea name="footer_menu" rows="5" class="mt-2 w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $menuToText($footerMenu) }}</textarea>
                        </label>
                        <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">حفظ القوائم</button>
                    </form>

                    <form method="POST" action="{{ route('partner.storefront.seo.update') }}" class="space-y-3" x-show="settingsPanel === 'seo'">
                        @csrf
                        <input name="meta_title" value="{{ $seo['meta_title'] ?? $storeName }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="Meta Title">
                        <textarea name="meta_description" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="Meta Description">{{ $seo['meta_description'] ?? '' }}</textarea>
                        <input name="social_image" value="{{ $seo['social_image'] ?? $logo }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="Social Image">
                        <textarea name="robots_txt" rows="4" dir="ltr" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-left font-mono text-xs font-bold dark:border-slate-700 dark:bg-slate-950">{{ $seo['robots_txt'] ?? "User-agent: *\nAllow: /" }}</textarea>
                        <label class="flex items-center gap-2 rounded-2xl bg-slate-50 p-3 text-xs font-black dark:bg-slate-950"><input type="checkbox" name="sitemap_enabled" value="1" @checked(! empty($seo['sitemap_enabled']))> Sitemap مفعل</label>
                        <label class="flex items-center gap-2 rounded-2xl bg-slate-50 p-3 text-xs font-black dark:bg-slate-950"><input type="checkbox" name="open_graph_enabled" value="1" @checked(! empty($seo['open_graph_enabled']))> Open Graph مفعل</label>
                        <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">حفظ SEO</button>
                    </form>

                    <div x-show="settingsPanel === 'domain'" class="space-y-3">
                        <form method="POST" action="{{ route('partner.storefront.domain.connect') }}" class="space-y-3">
                            @csrf
                            <input name="custom_domain" value="{{ $domain['custom_domain'] ?? '' }}" class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950" placeholder="example.com">
                            <button class="w-full rounded-2xl bg-solve-600 px-5 py-3 text-sm font-black text-white">حفظ الدومين</button>
                        </form>
                        <form method="POST" action="{{ route('partner.storefront.domain.verify') }}">
                            @csrf
                            <button class="w-full rounded-2xl border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">تحقق DNS / SSL</button>
                        </form>
                        <div class="rounded-2xl bg-slate-50 p-4 text-xs font-black dark:bg-slate-950">
                            <p>الحالي: {{ $domain['current_domain'] ?? '-' }}</p>
                            <p class="mt-2">DNS: {{ $domain['dns_status'] ?? '-' }}</p>
                            <p class="mt-2">SSL: {{ $domain['ssl_status'] ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
(() => {
    const list = document.getElementById('builderSectionSortList');
    const status = document.getElementById('builderSectionSortStatus');
    const toast = document.getElementById('builderLiveToast');
    const previewFrame = document.getElementById('storefrontLivePreview');
    const themeForm = document.getElementById('builder-theme-form');
    const builderShell = document.querySelector('[data-builder-shell]');
    const mediaUploadUrl = builderShell?.dataset.builderMediaUploadUrl || '';
    const csrfToken = builderShell?.dataset.builderCsrf || document.querySelector('meta[name="csrf-token"]')?.content || '';
    const themeClassNames = [
        'theme-header-compact',
        'theme-header-mega',
        'theme-header-centered',
        'theme-footer-rich',
        'theme-footer-columns',
        'theme-footer-minimal',
        'theme-supports-dark',
        'theme-light-only',
        'theme-preview-dark',
    ];
    const fontStacks = {
        Tajawal: "'Tajawal', Tahoma, Arial, sans-serif",
        'IBM Plex Sans Arabic': "'IBM Plex Sans Arabic', Tahoma, Arial, sans-serif",
        Cairo: "'Cairo', Tahoma, Arial, sans-serif",
    };

    const showToast = (message, tone = 'success') => {
        if (!toast) return;
        toast.textContent = message;
        toast.className = [
            'pointer-events-none absolute left-1/2 top-20 z-30 -translate-x-1/2 rounded-2xl border px-5 py-3 text-sm font-black shadow-xl',
            tone === 'error'
                ? 'border-red-200 bg-red-50 text-red-700'
                : 'border-emerald-200 bg-emerald-50 text-emerald-700',
        ].join(' ');

        window.clearTimeout(showToast.timer);
        showToast.timer = window.setTimeout(() => toast.classList.add('hidden'), 2800);
    };

    const formPayload = (form) => {
        const data = new FormData(form);
        const payload = {};

        for (const [key, value] of data.entries()) {
            if (key === '_token' || key === '_method') continue;

            const settings = key.match(/^settings\[([^\]]+)\](\[\])?$/);
            const responsive = key.match(/^responsive\[([^\]]+)\](\[\])?$/);
            const target = settings ? 'settings' : (responsive ? 'responsive' : null);

            if (target) {
                const field = (settings || responsive)[1];
                payload[target] ??= {};
                if (key.endsWith('[]')) {
                    payload[target][field] ??= [];
                    payload[target][field].push(value);
                } else {
                    payload[target][field] = value;
                }
                continue;
            }

            payload[key] = value;
        }

        if ('visible' in payload) {
            payload.visible = payload.visible === '1' || payload.visible === 'true' || payload.visible === true;
        }
        if ('sort_order' in payload) {
            payload.sort_order = Number(payload.sort_order || 0);
        }

        return payload;
    };

    const inlineMediaFields = (form) => Array.from(form.querySelectorAll('input[name^="settings["], textarea[name^="settings["]'))
        .filter((field) => {
            const value = String(field.value || '').trim();
            const isMediaField = /\[(image|poster|video_url|background_image|desktop_image|mobile_image)\]/.test(field.name || '');

            return value.startsWith('data:') || (isMediaField && value.length > 1000);
        });

    const submitJsonForm = async (form, method, successMessage) => {
        const inlineMedia = inlineMediaFields(form);
        if (inlineMedia.length) {
            showToast('الصورة أو الفيديو ما زال داخل النموذج كملف Base64. ارفع الملف من زر الرفع ثم احفظ مرة أخرى.', 'error');
            return;
        }

        const button = form.querySelector('button[type="submit"], .builder-save-button');
        const originalText = button ? button.textContent : '';

        if (button) {
            button.disabled = true;
            button.textContent = 'جاري الحفظ...';
        }

        try {
            const response = await fetch(form.dataset.apiUrl, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                },
                body: JSON.stringify(formPayload(form)),
            });

            if (!response.ok) {
                const errorPayload = await response.json().catch(() => ({}));
                const validationMessage = errorPayload?.errors
                    ? Object.values(errorPayload.errors).flat().join(' ')
                    : '';
                throw new Error(errorPayload?.message || validationMessage || 'builder_request_failed');
            }

            showToast(successMessage);
            if (form.dataset.liveSave === '1') {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
                applyThemePreview();
                previewFrame?.contentWindow?.location.reload();
                return;
            }

            window.setTimeout(() => window.location.reload(), 650);
        } catch (error) {
            showToast(error?.message && error.message !== 'builder_request_failed'
                ? error.message
                : 'تعذر تنفيذ العملية. تأكد من الصلاحيات ثم حاول مرة أخرى.', 'error');
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    };

    const themeValue = (name, fallback = '') => {
        const field = themeForm?.elements?.[name];

        if (!field) {
            return fallback;
        }

        if (field instanceof RadioNodeList) {
            const checkbox = themeForm.querySelector(`input[name="${name}"][type="checkbox"]`);

            if (checkbox) {
                return checkbox.checked;
            }

            return field.value || fallback;
        }

        if (field.type === 'checkbox') {
            return field.checked;
        }

        return field.value || fallback;
    };

    const applyThemePreview = () => {
        if (!themeForm || !previewFrame?.contentDocument) {
            return;
        }

        const doc = previewFrame.contentDocument;
        const body = doc.body;

        if (!body) {
            return;
        }

        const primary = themeValue('primary_color', '#6d28d9');
        const secondary = themeValue('secondary_color', '#06b6d4');
        const font = themeValue('font', 'Tajawal');
        const header = themeValue('header_style', 'compact');
        const footer = themeValue('footer_style', 'rich');
        const supportsDark = themeValue('supports_dark', false) === true || themeValue('supports_dark', false) === '1';

        doc.documentElement.style.setProperty('--primary', primary);
        doc.documentElement.style.setProperty('--secondary', secondary);
        doc.documentElement.style.setProperty('--brand-gradient', `linear-gradient(135deg, ${primary}, #334ce7 48%, ${secondary})`);
        body.style.setProperty('--store-font', fontStacks[font] || fontStacks.Tajawal);
        body.classList.remove(...themeClassNames);
        body.classList.add(`theme-header-${header}`, `theme-footer-${footer}`, supportsDark ? 'theme-supports-dark' : 'theme-light-only');
        body.classList.toggle('theme-preview-dark', supportsDark);
    };

    if (themeForm) {
        themeForm.addEventListener('input', applyThemePreview);
        themeForm.addEventListener('change', applyThemePreview);
        themeForm.addEventListener('submit', (event) => {
            event.preventDefault();
            submitJsonForm(themeForm, 'PATCH', 'تم حفظ تخصيص القالب وربطه بواجهة المتجر.');
        });
    }

    previewFrame?.addEventListener('load', applyThemePreview);

    const assetBaseUrl = @js(asset(''));
    const uploadBuilderMedia = async (file, type) => {
        if (!mediaUploadUrl) {
            throw new Error('مسار رفع الوسائط غير متاح حالياً.');
        }

        const payload = new FormData();
        payload.append('media', file);
        payload.append('type', type);

        const response = await fetch(mediaUploadUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: payload,
        });

        if (!response.ok) {
            const errorPayload = await response.json().catch(() => ({}));
            const validationMessage = errorPayload?.errors
                ? Object.values(errorPayload.errors).flat().join(' ')
                : '';
            throw new Error(errorPayload?.message || validationMessage || 'تعذر رفع الملف.');
        }

        return response.json();
    };
    const normalizeMediaUrl = (value) => {
        const trimmed = String(value || '').trim();

        if (!trimmed) {
            return '';
        }

        if (/^(https?:|data:|blob:|\/)/i.test(trimmed)) {
            return trimmed;
        }

        return `${assetBaseUrl.replace(/\/$/, '')}/${trimmed.replace(/^\/+/, '')}`;
    };
    const normalizeVideoUrl = (value) => {
        const url = String(value || '').trim();

        if (!url) {
            return '';
        }

        if (/^javascript:/i.test(url)) {
            return '';
        }

        const youtubeShort = url.match(/youtu\.be\/([A-Za-z0-9_-]+)/);
        if (youtubeShort) {
            return `https://www.youtube.com/embed/${youtubeShort[1]}`;
        }

        const youtubeWatch = url.match(/[?&]v=([A-Za-z0-9_-]+)/);
        if (youtubeWatch) {
            return `https://www.youtube.com/embed/${youtubeWatch[1]}`;
        }

        const vimeo = url.match(/vimeo\.com\/(\d+)/);
        if (vimeo) {
            return `https://player.vimeo.com/video/${vimeo[1]}`;
        }

        if (/^(https?:|data:video\/|blob:|\/)/i.test(url)) {
            return url;
        }

        return normalizeMediaUrl(url);
    };
    const renderVideoPreview = (target, rawUrl, posterUrl) => {
        if (!target) {
            return;
        }

        const url = normalizeVideoUrl(rawUrl);
        const poster = normalizeMediaUrl(posterUrl);
        target.innerHTML = '';
        target.classList.toggle('hidden', !url);

        if (!url) {
            return;
        }

        if (/\.(mp4|webm|ogg)(\?.*)?$/i.test(url) || url.startsWith('data:video/')) {
            const video = document.createElement('video');
            video.controls = true;
            video.preload = 'metadata';
            video.className = 'h-44 w-full rounded-xl object-cover';
            if (poster) {
                video.poster = poster;
            }
            const source = document.createElement('source');
            source.src = url;
            video.appendChild(source);
            target.appendChild(video);
            return;
        }

        const iframe = document.createElement('iframe');
        iframe.src = url;
        iframe.className = 'h-44 w-full rounded-xl bg-slate-950';
        iframe.loading = 'lazy';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
        iframe.allowFullscreen = true;
        target.appendChild(iframe);
    };
    const refreshMediaCard = (card) => {
        const imageInput = card.querySelector('[data-builder-image-input]');
        const posterInput = card.querySelector('[data-builder-poster-input]');
        const videoInput = card.querySelector('[data-builder-video-input]');
        const image = card.querySelector('[data-builder-image-preview]');
        const empty = card.querySelector('[data-builder-image-empty]');
        const videoPreview = card.querySelector('[data-builder-video-preview]');
        const imageUrl = normalizeMediaUrl(imageInput?.value || posterInput?.value || '');

        if (image) {
            image.classList.toggle('hidden', !imageUrl);
            if (imageUrl) {
                image.src = imageUrl;
            }
        }

        empty?.classList.toggle('hidden', Boolean(imageUrl));
        renderVideoPreview(videoPreview, videoInput?.value || '', posterInput?.value || imageInput?.value || '');
    };

    document.querySelectorAll('[data-builder-media-card]').forEach((card) => {
        const imageInput = card.querySelector('[data-builder-image-input]');
        const posterInput = card.querySelector('[data-builder-poster-input]');
        const videoInput = card.querySelector('[data-builder-video-input]');
        const fileInput = card.querySelector('[data-builder-image-file]');
        const clearButton = card.querySelector('[data-builder-image-clear]');
        const videoFileInput = card.querySelector('[data-builder-video-file]');
        const videoClearButton = card.querySelector('[data-builder-video-clear]');

        [imageInput, posterInput, videoInput].forEach((input) => {
            input?.addEventListener('input', () => refreshMediaCard(card));
            input?.addEventListener('change', () => refreshMediaCard(card));
        });

        fileInput?.addEventListener('change', async () => {
            const file = fileInput.files?.[0];

            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                showToast('اختر ملف صورة صالح.', 'error');
                fileInput.value = '';
                return;
            }

            if (file.size > 12 * 1024 * 1024) {
                showToast('الصورة كبيرة. استخدم صورة أقل من 12MB أو رابط صورة خارجي.', 'error');
                fileInput.value = '';
                return;
            }

            try {
                showToast('جاري رفع الصورة وحفظ مسارها...');
                const uploaded = await uploadBuilderMedia(file, 'image');
                if (imageInput) {
                    imageInput.value = uploaded.path || uploaded.url || '';
                }
                refreshMediaCard(card);
                showToast('تم رفع الصورة. اضغط حفظ التعديل لتطبيقها على المتجر.');
            } catch (error) {
                showToast(error?.message || 'تعذر رفع الصورة.', 'error');
            } finally {
                fileInput.value = '';
            }
        });

        videoFileInput?.addEventListener('change', async () => {
            const file = videoFileInput.files?.[0];

            if (!file) {
                return;
            }

            if (!file.type.startsWith('video/')) {
                showToast('اختر ملف فيديو صالح.', 'error');
                videoFileInput.value = '';
                return;
            }

            if (file.size > 12 * 1024 * 1024) {
                showToast('الفيديو كبير. استخدم فيديو أقل من 12MB أو رابط YouTube/Vimeo/MP4.', 'error');
                videoFileInput.value = '';
                return;
            }

            try {
                showToast('جاري رفع الفيديو وحفظ مساره...');
                const uploaded = await uploadBuilderMedia(file, 'video');
                if (videoInput) {
                    videoInput.value = uploaded.path || uploaded.url || '';
                }
                refreshMediaCard(card);
                showToast('تم رفع الفيديو. اضغط حفظ التعديل لتطبيقه على المتجر.');
            } catch (error) {
                showToast(error?.message || 'تعذر رفع الفيديو.', 'error');
            } finally {
                videoFileInput.value = '';
            }
        });

        clearButton?.addEventListener('click', () => {
            if (imageInput) {
                imageInput.value = '';
            }
            if (posterInput) {
                posterInput.value = '';
            }
            refreshMediaCard(card);
        });

        videoClearButton?.addEventListener('click', () => {
            if (videoInput) {
                videoInput.value = '';
            }
            if (videoFileInput) {
                videoFileInput.value = '';
            }
            refreshMediaCard(card);
        });

        refreshMediaCard(card);
    });

    document.querySelectorAll('.builder-component-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitJsonForm(form, 'POST', 'تمت إضافة المكون للواجهة وسيظهر في المعاينة.');
        });
    });

    document.querySelectorAll('.builder-section-edit-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitJsonForm(form, 'PATCH', 'تم تحديث القسم وربطه بمعاينة المتجر.');
        });
    });

    document.querySelectorAll('.builder-section-delete-button').forEach((button) => {
        button.addEventListener('click', async (event) => {
            event.preventDefault();
            event.stopPropagation();

            const title = button.dataset.sectionTitle || 'هذا القسم';
            if (!window.confirm(`حذف ${title} من واجهة المتجر؟`)) {
                return;
            }

            const item = button.closest('[data-section-id]');
            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '...';

            try {
                const response = await fetch(button.dataset.deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': list?.dataset.csrf || '',
                    },
                });

                if (!response.ok) {
                    throw new Error('delete_failed');
                }

                item?.remove();
                showToast('تم حذف القسم من واجهة المتجر.');
                window.setTimeout(() => window.location.reload(), 500);
            } catch (error) {
                button.disabled = false;
                button.innerHTML = originalHtml;
                showToast('تعذر حذف القسم. تأكد من الصلاحيات ثم حاول مرة أخرى.', 'error');
            }
        });
    });

    if (!list) {
        return;
    }

    let dragged = null;
    let saveTimer = null;

    const items = () => Array.from(list.querySelectorAll('[data-section-id]'));
    const sectionOrder = () => items().map((item) => item.dataset.sectionId).filter(Boolean);
    const setStatus = (message, tone = 'muted') => {
        if (!status) return;
        status.textContent = message;
        status.classList.toggle('text-emerald-600', tone === 'success');
        status.classList.toggle('text-red-600', tone === 'error');
        status.classList.toggle('text-slate-400', tone === 'muted');
    };
    const refreshBadges = () => {
        items().forEach((item, index) => {
            const badge = item.querySelector('[data-order-badge]');
            if (badge) badge.textContent = String(index + 1);
        });
    };
    const saveOrder = async () => {
        const order = sectionOrder();

        if (!order.length) {
            return;
        }

        setStatus('جاري حفظ ترتيب الأقسام...');

        try {
            const response = await fetch(list.dataset.reorderUrl, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': list.dataset.csrf || '',
                },
                body: JSON.stringify({order}),
            });

            if (!response.ok) {
                throw new Error('reorder_failed');
            }

            setStatus('تم حفظ ترتيب الأقسام وربطه بالمتجر.', 'success');
        } catch (error) {
            setStatus('تعذر حفظ الترتيب. حاول مرة أخرى.', 'error');
        }
    };
    const scheduleSave = () => {
        window.clearTimeout(saveTimer);
        refreshBadges();
        saveTimer = window.setTimeout(saveOrder, 350);
    };
    const afterElement = (container, y) => {
        const candidates = items().filter((item) => item !== dragged);

        return candidates.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return {offset, element: child};
            }

            return closest;
        }, {offset: Number.NEGATIVE_INFINITY, element: null}).element;
    };

    items().forEach((item) => {
        item.addEventListener('dragstart', () => {
            dragged = item;
            item.classList.add('opacity-50', 'ring-2', 'ring-solve-300');
            setStatus('اسحب القسم للمكان المطلوب ثم اتركه للحفظ.');
        });

        item.addEventListener('dragend', () => {
            item.classList.remove('opacity-50', 'ring-2', 'ring-solve-300');
            dragged = null;
            scheduleSave();
        });
    });

    list.addEventListener('dragover', (event) => {
        event.preventDefault();

        if (!dragged) {
            return;
        }

        const next = afterElement(list, event.clientY);

        if (next) {
            list.insertBefore(dragged, next);
        } else {
            list.appendChild(dragged);
        }
    });
})();
</script>
@endsection
