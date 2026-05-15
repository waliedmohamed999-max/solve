@extends('storefront.layout')

@section('content')
    @php
        $builderSectionRows = collect($builderSections ?? []);
        $seededBuilderSectionRows = $builderSectionRows->filter(function (array $section): bool {
            $sectionId = (string) ($section['id'] ?? $section['record_id'] ?? '');

            return str_starts_with($sectionId, 'storefront_sections-');
        });
        $builderRowsForTypes = function (array|string $types) use ($builderSectionRows, $seededBuilderSectionRows) {
            $types = (array) $types;
            $seededMatches = $seededBuilderSectionRows->filter(fn (array $section): bool => in_array($section['type'] ?? '', $types, true));

            return $seededMatches->isNotEmpty()
                ? $seededMatches
                : $builderSectionRows->filter(fn (array $section): bool => in_array($section['type'] ?? '', $types, true));
        };
        $sectionIsVisible = function (array|string $types, bool $default = true) use ($builderRowsForTypes): bool {
            $matches = $builderRowsForTypes($types);

            if ($matches->isEmpty()) {
                return $default;
            }

            return $matches->contains(function (array $section): bool {
                $status = $section['status_key'] ?? $section['status'] ?? 'active';

                return (bool) ($section['visible'] ?? true) && ! in_array($status, ['hidden', 'disabled'], true);
            });
        };
        $sectionOrder = function (array|string $types, int $default = 100) use ($builderRowsForTypes): int {
            $match = $builderRowsForTypes($types)->first();

            return $match ? (int) ($match['sort_order'] ?? $default) : $default;
        };
        $mediaUrl = function (?string $value, string $fallback = 'solve-logo.png'): string {
            $value = trim((string) $value);

            if ($value === '') {
                return asset($fallback);
            }

            if (str_starts_with($value, 'http://')
                || str_starts_with($value, 'https://')
                || str_starts_with($value, '/')
                || str_starts_with($value, 'data:')
            ) {
                return $value;
            }

            return asset($value);
        };
        $videoUrl = function (?string $value) use ($mediaUrl): string {
            $url = trim((string) $value);

            if ($url === '') {
                return '';
            }

            if (str_starts_with(strtolower($url), 'javascript:')) {
                return '';
            }

            if (preg_match('~youtu\.be/([A-Za-z0-9_-]+)~', $url, $matches)) {
                return 'https://www.youtube.com/embed/' . $matches[1];
            }

            if (preg_match('~[?&]v=([A-Za-z0-9_-]+)~', $url, $matches)) {
                return 'https://www.youtube.com/embed/' . $matches[1];
            }

            if (preg_match('~vimeo\.com/(\d+)~', $url, $matches)) {
                return 'https://player.vimeo.com/video/' . $matches[1];
            }

            if (str_starts_with($url, 'http://')
                || str_starts_with($url, 'https://')
                || str_starts_with($url, '/')
                || str_starts_with($url, 'data:video/')
            ) {
                return $url;
            }

            return $mediaUrl($url);
        };
        $isDirectVideo = fn (string $value): bool => (bool) preg_match('~(\.mp4|\.webm|\.ogg)(\?.*)?$~i', $value) || str_starts_with($value, 'data:video/');
        $coreSectionTypes = [
            'announcement',
            'quick_links',
            'hero',
            'offers_banner',
            'trust_bar',
            'categories_grid',
            'featured_products',
            'newsletter',
            'footer',
        ];
        $extraBuilderSections = $builderSectionRows
            ->filter(function (array $section) use ($coreSectionTypes): bool {
                $status = $section['status_key'] ?? $section['status'] ?? 'active';
                $sectionId = (string) ($section['id'] ?? $section['record_id'] ?? '');
                $isSeededCoreSection = in_array($section['type'] ?? '', $coreSectionTypes, true)
                    && str_starts_with($sectionId, 'storefront_sections-');

                return ! $isSeededCoreSection
                    && (bool) ($section['visible'] ?? true)
                    && ! in_array($status, ['hidden', 'disabled'], true);
            })
            ->sortBy(fn (array $section): int => (int) ($section['sort_order'] ?? 100))
            ->values();
    @endphp

    <div class="storefront-sections-flow" style="display:flex;flex-direction:column">
    @if($sectionIsVisible('hero'))
    <section class="hero" style="order: {{ $sectionOrder('hero', 10) }}">
        <div class="wrap hero-grid">
            <div class="hero-card">
                <span class="visual-chip">متجر رسمي على Solve</span>
                <h1 style="margin-top:18px">{{ $store['name'] ?? $partner['name'] }}</h1>
                <p>{{ $seo['meta_description'] ?? 'اكتشف منتجات مختارة وتجربة شراء سريعة وآمنة، مع سلة حية ودفع مختصر وطلب يظهر مباشرة في لوحة التاجر.' }}</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:28px">
                    <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products') }}">تسوق المنتجات</a>
                    <a class="btn" style="background:rgba(255,255,255,.16);color:#fff;border:1px solid rgba(255,255,255,.24)" href="{{ url('/store/' . $slug . '/categories') }}">استكشف التصنيفات</a>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:34px">
                    <div class="glass-panel" style="padding:14px;border-radius:18px;color:var(--ink)">
                        <span class="muted">منتجات</span>
                        <strong style="display:block;font-size:22px;margin-top:6px">{{ count($featuredProducts) + count($latestProducts) }}</strong>
                    </div>
                    <div class="glass-panel" style="padding:14px;border-radius:18px;color:var(--ink)">
                        <span class="muted">تصنيفات</span>
                        <strong style="display:block;font-size:22px;margin-top:6px">{{ count($categories) }}</strong>
                    </div>
                    <div class="glass-panel" style="padding:14px;border-radius:18px;color:var(--ink)">
                        <span class="muted">شراء آمن</span>
                        <strong style="display:block;font-size:18px;margin-top:6px">Solve</strong>
                    </div>
                </div>
            </div>
            @php
                $defaultBannerImages = [
                    asset('منصة_متاجر.png'),
                    asset('ما يُميز متاجر.png'),
                    asset('تطبيق_متاجر.png'),
                    asset('feature_1.png'),
                ];
                $bannerImage = function (array $banner, int $index = 0) use ($defaultBannerImages) {
                    $path = $banner['image'] ?? $banner['image_url'] ?? $banner['desktop_image'] ?? $banner['cover'] ?? null;
                    if ($path) {
                        return (str_starts_with($path, 'http') || str_starts_with($path, '/')) ? $path : asset($path);
                    }
                    return $defaultBannerImages[$index % count($defaultBannerImages)];
                };
            @endphp
            <div class="panel" style="padding:18px;display:grid;gap:12px">
                @forelse($banners as $banner)
                    @php $target = $banner['link_target'] ?? null; @endphp
                    <div class="banner-card">
                        <img src="{{ $bannerImage($banner, $loop->index) }}" alt="{{ $banner['title'] ?? 'عرض المتجر' }}" loading="lazy" onerror="this.src='{{ asset('solve-logo.png') }}'">
                        <div>
                            <span class="badge">{{ $banner['placement'] ?? 'home' }}</span>
                            <h2 style="margin:12px 0 6px">{{ $banner['title'] ?? 'عرض المتجر' }}</h2>
                            <p class="muted" style="margin:0">رابط مباشر إلى منتجات وعروض المتجر.</p>
                        </div>
                        <a class="btn btn-primary" href="{{ $target ? (str_starts_with($target, 'http') ? $target : url('/store/' . $slug . '/' . ltrim($target, '/'))) : url('/store/' . $slug . '/products') }}">مشاهدة</a>
                    </div>
                @empty
                    <div class="banner-card">
                        <img src="{{ $defaultBannerImages[0] }}" alt="عرض موسمي" loading="lazy" onerror="this.src='{{ asset('solve-logo.png') }}'">
                        <div>
                            <span class="badge">عرض موسمي</span>
                            <h2 style="margin:12px 0 6px">واجهة متجر جاهزة للبيع</h2>
                            <p class="muted" style="margin:0">أضف البنرات من لوحة التاجر لتظهر هنا فوراً.</p>
                        </div>
                        <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/products') }}">تسوق الآن</a>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    @endif

    <section class="section" style="order: {{ $sectionOrder(['announcement', 'quick_links'], 20) }};padding-top:4px">
        <div class="wrap">
            <div class="panel" style="padding:12px;background:#fff;overflow:auto">
                <div style="display:flex;gap:10px;min-width:max-content">
                    <a class="badge" href="{{ url('/store/' . $slug . '/products?sort=latest') }}">وصل حديثاً</a>
                    <a class="badge" href="{{ url('/store/' . $slug . '/products?sort=price_asc') }}">أفضل سعر</a>
                    <a class="badge" href="{{ url('/store/' . $slug . '/products?sort=stock') }}">متوفر الآن</a>
                    @foreach(array_slice($categories, 0, 8) as $category)
                        <a class="badge" href="{{ url('/store/' . $slug . '/products?category=' . urlencode($category['name'] ?? '')) }}">{{ $category['name'] ?? 'تصنيف' }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if($sectionIsVisible('offers_banner'))
    <section class="section" style="order: {{ $sectionOrder('offers_banner', 30) }};padding-top:10px">
        <div class="wrap promo-grid">
            <a class="promo-card yellow" href="{{ url('/store/' . $slug . '/products?sort=price_asc') }}">
                <div>
                    <span class="badge">صفقات اليوم</span>
                    <strong>أسعار مختارة</strong>
                    <p class="muted" style="margin:0">منتجات بسعر تنافسي من المتجر.</p>
                </div>
                <span class="btn btn-primary">تسوق</span>
            </a>
            <a class="promo-card dark" href="{{ url('/store/' . $slug . '/products?sort=latest') }}">
                <div>
                    <span class="badge">جديد</span>
                    <strong>وصل حديثاً</strong>
                    <p style="color:#cbd5e1;margin:0">أحدث المنتجات المنشورة.</p>
                </div>
                <span class="btn" style="background:#feee00;color:#111827">عرض</span>
            </a>
            <a class="promo-card cyan" href="{{ url('/store/' . $slug . '/products?sort=stock') }}">
                <div>
                    <span class="badge">متوفر</span>
                    <strong>جاهز للشحن</strong>
                    <p class="muted" style="margin:0">منتجات متاحة حالياً.</p>
                </div>
                <span class="btn btn-primary">ابدأ</span>
            </a>
        </div>
    </section>
    @endif

    @if($sectionIsVisible('trust_bar'))
    <section class="section" style="order: {{ $sectionOrder('trust_bar', 40) }};padding-top:6px">
        <div class="wrap service-bar">
            <div class="service-tile"><span class="service-icon">✓</span><span>طلب مربوط بلوحة التاجر</span></div>
            <div class="service-tile"><span class="service-icon">↺</span><span>سلة محفوظة تلقائياً</span></div>
            <div class="service-tile"><span class="service-icon">%</span><span>كوبونات وعروض</span></div>
            <div class="service-tile"><span class="service-icon">☎</span><span>دعم وتواصل مباشر</span></div>
        </div>
    </section>
    @endif

    @if($sectionIsVisible('categories_grid'))
    <section class="section" style="order: {{ $sectionOrder('categories_grid', 50) }}">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="section-kicker">أقسام المتجر</span>
                    <h2>التصنيفات الرئيسية</h2>
                    <p>تصفح المنتجات حسب الأقسام، وكل تصنيف مرتبط بمنتجات المتجر الحقيقية.</p>
                </div>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/categories') }}">كل التصنيفات</a>
            </div>
            @php
                $categoryImages = [
                    'الأكثر مبيعاً' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=640&q=80',
                    'وصل حديثاً' => 'https://images.unsplash.com/photo-1445205170230-053b83016050?auto=format&fit=crop&w=640&q=80',
                    'عروض اليوم' => 'https://images.unsplash.com/photo-1607082349566-187342175e2f?auto=format&fit=crop&w=640&q=80',
                    'إلكترونيات' => 'https://images.unsplash.com/photo-1498049794561-7780e7231661?auto=format&fit=crop&w=640&q=80',
                    'أزياء' => 'https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?auto=format&fit=crop&w=640&q=80',
                    'عطور' => 'https://images.unsplash.com/photo-1594035910387-fea47794261f?auto=format&fit=crop&w=640&q=80',
                    'منزل' => 'https://images.unsplash.com/photo-1484101403633-562f891dc89a?auto=format&fit=crop&w=640&q=80',
                    'جمال' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=640&q=80',
                ];
                $allVisualProducts = collect($featuredProducts ?? [])->concat($latestProducts ?? [])->concat($bestSellingProducts ?? []);
                $categoryImage = function (array $category) use ($categoryImages, $allVisualProducts) {
                    $name = $category['name'] ?? 'تصنيف';
                    $direct = $category['image'] ?? $category['cover'] ?? $category['banner'] ?? null;
                    if ($direct) {
                        return (str_starts_with($direct, 'http') || str_starts_with($direct, '/')) ? $direct : asset($direct);
                    }
                    $product = $allVisualProducts->first(fn ($product) => ($product['category'] ?? null) === $name && ! empty($product['image']));
                    if ($product) {
                        $image = $product['image'];
                        return (str_starts_with($image, 'http') || str_starts_with($image, '/')) ? $image : asset($image);
                    }
                    return $categoryImages[$name] ?? 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=640&q=80';
                };
                $displayCategories = collect($categories)
                    ->map(fn ($category) => $category + ['url' => url('/store/' . $slug . '/products?category=' . urlencode($category['name'] ?? '')), 'real' => true])
                    ->values();
                $fallbackCategories = collect([
                    ['name' => 'الأكثر مبيعاً', 'products_count' => count($bestSellingProducts ?? $featuredProducts), 'url' => url('/store/' . $slug . '/products?sort=stock')],
                    ['name' => 'وصل حديثاً', 'products_count' => count($latestProducts), 'url' => url('/store/' . $slug . '/products?sort=latest')],
                    ['name' => 'عروض اليوم', 'products_count' => count($featuredProducts), 'url' => url('/store/' . $slug . '/products?sort=price_asc')],
                    ['name' => 'إلكترونيات', 'products_count' => count($featuredProducts), 'url' => url('/store/' . $slug . '/products?q=إلكترونيات')],
                    ['name' => 'أزياء', 'products_count' => count($featuredProducts), 'url' => url('/store/' . $slug . '/products?q=أزياء')],
                    ['name' => 'عطور', 'products_count' => count($featuredProducts), 'url' => url('/store/' . $slug . '/products?q=عطور')],
                ]);
                $displayCategories = $displayCategories->concat($fallbackCategories)->unique('name')->take(8)->values();
            @endphp
            <div class="category-strip">
                @forelse($displayCategories as $category)
                    @php
                        $name = $category['name'] ?? 'تصنيف';
                        $visual = $categoryImage($category);
                    @endphp
                    <a class="category-thumb-card" href="{{ $category['url'] ?? url('/store/' . $slug . '/products') }}">
                        <div class="category-visual">
                            <img src="{{ $visual }}" alt="{{ $name }}" loading="lazy" onerror="this.src='{{ asset('solve-logo.png') }}'">
                            <span class="category-label">{{ $category['products_count'] ?? 0 }} منتج</span>
                        </div>
                        <div>
                            <h3>{{ $name }}</h3>
                            <p>تسوق منتجات {{ $name }}</p>
                        </div>
                    </a>
                @empty
                    <div class="empty-state" style="grid-column:1/-1">لا توجد تصنيفات منشورة بعد.</div>
                @endforelse
            </div>
        </div>
    </section>
    @endif

    @if($sectionIsVisible('offers_banner'))
    <section class="section" style="order: {{ $sectionOrder('offers_banner', 30) + 1 }};padding-top:8px">
        <div class="wrap">
            @php
                $wideOffers = [
                    ['image' => 'https://images.unsplash.com/photo-1607082350899-7e105aa886ae?auto=format&fit=crop&w=1400&q=80', 'badge' => 'عروض حصرية', 'title' => 'تسوق عروض المتجر المختارة', 'body' => 'بنر تسويقي عريض لعرض أفضل العروض والمنتجات النشطة داخل المتجر.', 'url' => url('/store/' . $slug . '/products?sort=price_asc'), 'stats' => ['خصومات موسمية', 'منتجات مميزة', 'جاهز للشحن']],
                    ['image' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?auto=format&fit=crop&w=1400&q=80', 'badge' => 'وصل حديثاً', 'title' => 'منتجات جديدة جاهزة للشراء', 'body' => 'اعرض المنتجات الأحدث في مساحة واضحة تدفع العميل لاستكشاف المزيد.', 'url' => url('/store/' . $slug . '/products?sort=latest'), 'stats' => ['وصل حديثاً', 'اختيارات جديدة', 'شراء سريع']],
                    ['image' => 'https://images.unsplash.com/photo-1607083206968-13611e3d76db?auto=format&fit=crop&w=1400&q=80', 'badge' => 'الأكثر طلباً', 'title' => 'اختيارات العملاء الأكثر رواجاً', 'body' => 'وجّه الزائر للمنتجات التي تستحق الظهور وتزيد فرص التحويل.', 'url' => url('/store/' . $slug . '/products?sort=stock'), 'stats' => ['متوفر الآن', 'طلب سريع', 'مخزون جاهز']],
                ];
            @endphp
            <div class="wide-offer-slider" id="wideOfferSlider">
                @foreach($wideOffers as $offer)
                    <a class="wide-offer-banner wide-offer-slide {{ $loop->first ? 'active' : '' }}" href="{{ $offer['url'] }}">
                        <img src="{{ $offer['image'] }}" alt="{{ $offer['title'] }}" loading="lazy" onerror="this.src='{{ asset('solve-logo.png') }}'">
                        <div class="wide-offer-content">
                            <span class="visual-chip">{{ $offer['badge'] }}</span>
                            <h2>{{ $offer['title'] }}</h2>
                            <p>{{ $offer['body'] }}</p>
                            <div class="wide-offer-stats">
                                @foreach($offer['stats'] as $stat)
                                    <span>{{ $stat }}</span>
                                @endforeach
                            </div>
                        </div>
                        <span class="btn" style="background:#feee00;color:#111827">تسوق الآن</span>
                    </a>
                @endforeach
                <div class="offer-dots" aria-label="تبديل عروض البنر">
                    @foreach($wideOffers as $offer)
                        <button class="{{ $loop->first ? 'active' : '' }}" type="button" data-offer-index="{{ $loop->index }}"></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif

    @if($sectionIsVisible('featured_products'))
    <section class="section" style="order: {{ $sectionOrder('featured_products', 60) }}">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="section-kicker">عروض لا تفوت</span>
                    <h2>منتجات مميزة</h2>
                    <p>عرض منتجات مضغوط وسريع بنفس أسلوب المتاجر الكبيرة، مع سعر ومخزون وإضافة مباشرة للسلة.</p>
                </div>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products') }}">عرض الكل</a>
            </div>
            <div class="products-grid">
                @forelse($featuredProducts as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    <div class="empty-state" style="grid-column:1/-1">
                        <div>
                            <h3 style="margin:0 0 8px">لا توجد منتجات منشورة بعد</h3>
                            <p class="muted">أي منتج ينشره التاجر سيظهر هنا فوراً.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
    @endif

    @if($sectionIsVisible('newsletter') || $sectionIsVisible('trust_bar'))
    <section class="section" style="order: {{ min($sectionOrder('newsletter', 70), $sectionOrder('trust_bar', 40) + 2) }}">
        <div class="wrap hero-grid">
            @if($sectionIsVisible('newsletter'))
            <div class="panel" style="padding:28px">
                <span class="section-kicker">العروض</span>
                <h2>اشترك في النشرة</h2>
                <p class="muted">استقبل العروض والكوبونات الجديدة من المتجر.</p>
                <form id="newsletterForm" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px">
                    <input class="input" name="email" type="email" placeholder="بريدك الإلكتروني" required>
                    <button class="btn btn-primary" type="submit">اشتراك</button>
                </form>
                <p id="newsletterResult" class="muted" style="display:none;margin-top:12px"></p>
            </div>
            @endif
            @if($sectionIsVisible('trust_bar'))
            <div class="panel" style="padding:28px">
                <span class="section-kicker">ثقة</span>
                <h2>تجربة شراء موثوقة</h2>
                <p class="muted">سلة حية، طلب مرتبط بلوحة التاجر، ومعلومات تواصل واضحة قبل الشراء.</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:18px">
                    <span class="badge badge-success">دفع آمن</span>
                    <span class="badge badge-success">شحن واضح</span>
                    <span class="badge badge-success">دعم مباشر</span>
                </div>
            </div>
            @endif
        </div>
    </section>
    @endif

    @if($sectionIsVisible('trust_bar'))
    <section class="section trust-section" style="order: {{ $sectionOrder('trust_bar', 40) + 3 }}">
        <div class="wrap">
            <div class="trust-head">
                <div>
                    <span class="section-kicker">لماذا نحن</span>
                    <h2>لماذا تشتري من هذا المتجر؟</h2>
                    <p>عناصر ثقة مختصرة وواضحة تساعد العميل على اتخاذ قرار الشراء بثقة.</p>
                </div>
                <div class="trust-summary">
                    <span class="trust-pill">دفع محمي</span>
                    <span class="trust-pill">شحن واضح</span>
                    <span class="trust-pill">متجر موثق</span>
                </div>
            </div>
            <div class="trust-grid">
                @foreach($trustBadges as $badge)
                    @php
                        $icons = ['✓', '↺', '⇄', '★'];
                    @endphp
                    <article class="trust-card">
                        <div class="trust-top">
                            <span class="trust-icon">{{ $icons[$loop->index] ?? '✓' }}</span>
                            <span class="trust-number">{{ $loop->iteration }}</span>
                        </div>
                        <div>
                            <h3>{{ $badge['title'] }}</h3>
                            <p class="muted">{{ $badge['body'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif

    @foreach($extraBuilderSections as $builderSection)
        @php
            $settings = is_array($builderSection['settings'] ?? null) ? $builderSection['settings'] : [];
            $type = $builderSection['type'] ?? 'custom';
            $order = (int) ($builderSection['sort_order'] ?? 100);
            $title = $builderSection['title'] ?? ($settings['headline'] ?? 'قسم مخصص');
            $body = $settings['body'] ?? 'تمت إضافة هذا المكوّن من محرر واجهة المتجر.';
            $primaryProducts = collect($featuredProducts ?? [])->isNotEmpty()
                ? collect($featuredProducts ?? [])
                : collect($latestProducts ?? []);
            $contactPhone = $settings['contact_phone'] ?? $settings['phone'] ?? $settings['whatsapp'] ?? $store['phone'] ?? $partner['phone'] ?? null;
            $contactEmail = $settings['contact_email'] ?? $settings['email'] ?? $store['email'] ?? $partner['email'] ?? null;
            $workingHours = $settings['working_hours'] ?? $store['working_hours'] ?? null;
            $sectionAddress = $settings['address'] ?? $store['address'] ?? 'Saudi Arabia';
            $phoneDigits = $contactPhone ? preg_replace('/\D+/', '', (string) $contactPhone) : '';
        @endphp
        <section class="section builder-dynamic-section" style="order: {{ $order }}">
            <div class="wrap">
                @if($type === 'rich_text')
                    <div class="builder-content-card">
                        <div class="builder-content-grid">
                            <div class="builder-content-copy">
                                <span class="section-kicker">{{ $settings['variant'] ?? 'Solve section' }}</span>
                                <h2>{{ $settings['headline'] ?? $title }}</h2>
                                <p>{{ $body }}</p>
                                <div class="builder-tool-grid">
                                    <a class="builder-tool" href="{{ url('/store/' . $slug . '/products') }}">
                                        <span class="builder-tool-icon">↗</span>
                                        <strong>تصفح المنتجات</strong>
                                        <span>انتقال سريع إلى منتجات المتجر المنشورة.</span>
                                    </a>
                                    <a class="builder-tool" href="{{ url('/store/' . $slug . '/categories') }}">
                                        <span class="builder-tool-icon">#</span>
                                        <strong>استكشف الأقسام</strong>
                                        <span>تنظيم واضح حسب التصنيفات الحالية.</span>
                                    </a>
                                    <a class="builder-tool" href="{{ url('/store/' . $slug . '/contact') }}">
                                        <span class="builder-tool-icon">☎</span>
                                        <strong>تواصل مباشر</strong>
                                        <span>زر واضح للعميل قبل أو بعد الشراء.</span>
                                    </a>
                                </div>
                                <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:22px">
                                    <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/products') }}">ابدأ التسوق</a>
                                    <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/contact') }}">اسأل المتجر</a>
                                </div>
                            </div>
                            <div class="builder-content-visual">
                                <div>
                                    <span class="visual-chip">Solve Storefront</span>
                                    <div class="builder-brand-orb">{{ mb_substr($store['name'] ?? $partner['name'] ?? 'S', 0, 1) }}</div>
                                </div>
                                <div>
                                    <h3 style="font-size:26px;margin:0 0 14px">{{ $store['name'] ?? $partner['name'] ?? 'Solve Store' }}</h3>
                                    <div class="builder-metric-strip">
                                        <div class="builder-metric"><span>منتجات</span><strong>{{ count($featuredProducts) + count($latestProducts) }}</strong></div>
                                        <div class="builder-metric"><span>تصنيفات</span><strong>{{ count($categories) }}</strong></div>
                                        <div class="builder-metric"><span>عروض</span><strong>{{ count($banners) }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($type === 'image_text')
                    <div class="panel hero-grid" style="padding:24px;align-items:center">
                        <div>
                            <span class="section-kicker">مكوّن صورة</span>
                            <h2>{{ $settings['headline'] ?? $title }}</h2>
                            <p class="muted">{{ $body }}</p>
                            <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/products') }}">تسوق الآن</a>
                        </div>
                        <img src="{{ $mediaUrl($settings['image'] ?? null) }}" alt="{{ $title }}" loading="lazy" style="width:100%;max-height:260px;object-fit:contain;border-radius:24px;background:#f8fafc">
                    </div>
                @elseif($type === 'button_cta')
                    <div class="panel" style="padding:28px;text-align:center">
                        <h2>{{ $title }}</h2>
                        <p class="muted">{{ $body }}</p>
                        <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/' . ltrim($settings['url'] ?? 'products', '/')) }}">{{ $settings['label'] ?? 'تسوق الآن' }}</a>
                    </div>
                @elseif($type === 'divider')
                    <div style="height:1px;background:linear-gradient(90deg,transparent,#cbd5e1,transparent)"></div>
                @elseif($type === 'spacer')
                    <div style="height:{{ max(16, min(160, (int) ($settings['height'] ?? 48))) }}px"></div>
                @elseif($type === 'hero')
                    <div class="banner-card" style="min-height:320px">
                        <img src="{{ $mediaUrl($settings['image'] ?? null, 'منصة_متاجر.png') }}" alt="{{ $title }}" loading="lazy" onerror="this.src='{{ asset('solve-logo.png') }}'">
                        <div>
                            <span class="badge">{{ $settings['layout'] ?? 'hero' }}</span>
                            <h2 style="margin:12px 0 8px">{{ $settings['headline'] ?? $title }}</h2>
                            <p class="muted">{{ $body }}</p>
                            <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/products') }}">{{ $settings['cta'] ?? 'تسوق الآن' }}</a>
                        </div>
                    </div>
                @elseif($type === 'video')
                    @php
                        $sectionVideoUrl = $videoUrl($settings['video_url'] ?? '');
                        $sectionPosterUrl = $mediaUrl($settings['poster'] ?? $settings['image'] ?? null, 'services/banner-storefront.svg');
                    @endphp
                    <div class="builder-video-card">
                        <div class="builder-video-copy">
                            <span class="section-kicker">{{ $settings['variant'] ?? 'Video' }}</span>
                            <h2>{{ $settings['headline'] ?? $title }}</h2>
                            <p>{{ $body }}</p>
                            <div class="builder-video-actions">
                                <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/' . ltrim($settings['url'] ?? 'products', '/')) }}">{{ $settings['cta'] ?? 'تسوق الآن' }}</a>
                                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/contact') }}">تواصل معنا</a>
                            </div>
                        </div>
                        <div class="builder-video-frame">
                            @if($sectionVideoUrl)
                                @if($isDirectVideo($sectionVideoUrl))
                                    <video controls preload="metadata" poster="{{ $sectionPosterUrl }}">
                                        <source src="{{ $sectionVideoUrl }}">
                                    </video>
                                @else
                                    <iframe
                                        src="{{ $sectionVideoUrl }}"
                                        title="{{ $title }}"
                                        loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen>
                                    </iframe>
                                @endif
                            @else
                                <img src="{{ $sectionPosterUrl }}" alt="{{ $title }}" loading="lazy">
                            @endif
                        </div>
                    </div>
                @elseif($type === 'featured_products')
                    <div class="section-head">
                        <div>
                            <span class="section-kicker">{{ $settings['source'] ?? 'products' }}</span>
                            <h2>{{ $title }}</h2>
                            <p>قسم منتجات تم إنشاؤه من محرر الواجهة ويقرأ منتجات المتجر الحقيقية.</p>
                        </div>
                        <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products') }}">عرض الكل</a>
                    </div>
                    <div class="products-grid">
                        @forelse($primaryProducts->take((int) ($settings['limit'] ?? 4)) as $product)
                            @include('storefront.partials.product-card', ['product' => $product])
                        @empty
                            <div class="empty-state" style="grid-column:1/-1">أضف منتجات ليظهر هذا القسم.</div>
                        @endforelse
                    </div>
                @elseif($type === 'categories_grid')
                    <div class="section-head">
                        <div>
                            <span class="section-kicker">تصنيفات</span>
                            <h2>{{ $title }}</h2>
                            <p>يعرض التصنيفات الموجودة داخل بيانات المتجر.</p>
                        </div>
                        <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/categories') }}">كل التصنيفات</a>
                    </div>
                    <div class="category-strip">
                        @forelse(collect($categories ?? [])->take((int) ($settings['limit'] ?? 6)) as $category)
                            @php $categoryName = is_array($category) ? ($category['name'] ?? 'تصنيف') : (string) $category; @endphp
                            <a class="category-thumb-card" href="{{ url('/store/' . $slug . '/products?category=' . urlencode($categoryName)) }}">
                                <div class="category-visual">
                                    <img src="https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=640&q=80" alt="{{ $categoryName }}" loading="lazy">
                                    <span class="category-label">قسم</span>
                                </div>
                                <div>
                                    <h3>{{ $categoryName }}</h3>
                                    <p>تسوق منتجات {{ $categoryName }}</p>
                                </div>
                            </a>
                        @empty
                            <div class="empty-state" style="grid-column:1/-1">لا توجد تصنيفات منشورة بعد.</div>
                        @endforelse
                    </div>
                @elseif($type === 'offers_banner')
                    <a class="wide-offer-banner" href="{{ url('/store/' . $slug . '/products?sort=price_asc') }}">
                        <img src="{{ $mediaUrl($settings['image'] ?? null, 'ما يُميز متاجر.png') }}" alt="{{ $title }}" loading="lazy" onerror="this.src='{{ asset('solve-logo.png') }}'">
                        <div class="wide-offer-content">
                            <span class="visual-chip">{{ $settings['style'] ?? 'عرض' }}</span>
                            <h2>{{ $settings['headline'] ?? $title }}</h2>
                            <p>{{ $body }}</p>
                        </div>
                        <span class="btn" style="background:#feee00;color:#111827">{{ $settings['cta'] ?? 'تسوق العرض' }}</span>
                    </a>
                @elseif($type === 'trust_bar')
                    <div class="service-bar">
                        @foreach(collect($trustBadges ?? [])->take(4) as $badge)
                            <div class="service-tile">
                                <span class="service-icon">✓</span>
                                <span>{{ $badge['title'] ?? 'ميزة المتجر' }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif($type === 'product_card')
                    <div class="section-head">
                        <div>
                            <span class="section-kicker">منتج مختار</span>
                            <h2>{{ $title }}</h2>
                        </div>
                    </div>
                    <div class="products-grid">
                        @if($primaryProducts->first())
                            @include('storefront.partials.product-card', ['product' => $primaryProducts->first()])
                        @else
                            <div class="empty-state" style="grid-column:1/-1">أضف منتجات ليظهر هذا المكوّن.</div>
                        @endif
                    </div>
                @elseif($type === 'slider')
                    <div class="panel" style="padding:18px">
                        <div class="section-head" style="margin-bottom:14px">
                            <div>
                                <span class="section-kicker">سلايدر</span>
                                <h2>{{ $title }}</h2>
                            </div>
                        </div>
                        <div style="display:grid;grid-auto-flow:column;grid-auto-columns:minmax(260px,1fr);gap:14px;overflow:auto">
                            @forelse(collect($banners ?? [])->take((int) ($settings['limit'] ?? 3)) as $banner)
                                <a class="banner-card" href="{{ url('/store/' . $slug . '/products') }}">
                                    <img src="{{ $mediaUrl($banner['image'] ?? $banner['image_url'] ?? null) }}" alt="{{ $banner['title'] ?? $title }}" loading="lazy">
                                    <strong>{{ $banner['title'] ?? $title }}</strong>
                                </a>
                            @empty
                                <div class="empty-state">أضف بنرات من لوحة التاجر ليعمل السلايدر.</div>
                            @endforelse
                        </div>
                    </div>
                @elseif($type === 'form')
                    <div class="builder-contact-hub">
                        <aside class="builder-contact-side">
                            <div>
                                <span class="visual-chip">مركز تواصل</span>
                                <h2 style="margin-top:18px">{{ $settings['headline'] ?? $title }}</h2>
                                <p>{{ $body }}</p>
                                <div class="builder-contact-list">
                                    <div class="builder-contact-item"><span>الجوال</span><strong>{{ $contactPhone ?: 'غير محدد' }}</strong></div>
                                    <div class="builder-contact-item"><span>البريد</span><strong>{{ $contactEmail ?: 'غير محدد' }}</strong></div>
                                    <div class="builder-contact-item"><span>العمل</span><strong>{{ $workingHours ?: 'متاح حسب أوقات المتجر' }}</strong></div>
                                </div>
                            </div>
                            <div class="builder-contact-actions">
                                @if($phoneDigits)
                                    <a class="btn" style="background:#feee00;color:#111827" href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener">واتساب</a>
                                    <a class="btn" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.18)" href="tel:{{ $phoneDigits }}">اتصال</a>
                                @endif
                                @if($contactEmail)
                                    <a class="btn" style="background:rgba(255,255,255,.12);color:#fff;border:1px solid rgba(255,255,255,.18)" href="mailto:{{ $contactEmail }}">بريد</a>
                                @endif
                            </div>
                        </aside>
                        <form class="builder-contact-form" data-section-title="{{ $title }}">
                            <span class="badge">رسالة مباشرة</span>
                            <input class="input" name="name" type="text" placeholder="اسمك الكامل" required>
                            <input class="input" name="contact" type="text" placeholder="البريد الإلكتروني أو رقم الجوال" required>
                            <textarea class="input" name="message" placeholder="اكتب رسالتك أو طلبك هنا" required></textarea>
                            <button class="btn btn-primary" type="submit">{{ $settings['button'] ?? 'إرسال الرسالة' }}</button>
                            <p class="builder-contact-result"></p>
                        </form>
                    </div>
                @elseif($type === 'stats')
                    <div class="panel" style="padding:24px">
                        <h2>{{ $title }}</h2>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:16px">
                            <div class="glass-panel" style="padding:18px;border-radius:18px"><span class="muted">منتجات</span><strong style="display:block;font-size:24px">{{ count($featuredProducts) + count($latestProducts) }}</strong></div>
                            <div class="glass-panel" style="padding:18px;border-radius:18px"><span class="muted">تصنيفات</span><strong style="display:block;font-size:24px">{{ count($categories) }}</strong></div>
                            <div class="glass-panel" style="padding:18px;border-radius:18px"><span class="muted">بنرات</span><strong style="display:block;font-size:24px">{{ count($banners) }}</strong></div>
                        </div>
                    </div>
                @elseif($type === 'custom_html')
                    <div class="panel" style="padding:28px">
                        <span class="section-kicker">HTML مخصص</span>
                        <h2>{{ $title }}</h2>
                        <p class="muted">تم حفظ كود HTML لهذا القسم. يتم عرضه بشكل آمن داخل المتجر بعد اعتماده.</p>
                        <code style="display:block;margin-top:14px;white-space:pre-wrap;border-radius:16px;background:#f8fafc;padding:14px">{{ $settings['html'] ?? '' }}</code>
                    </div>
                @elseif($type === 'map')
                    <div class="builder-map-card">
                        <div class="builder-map-visual">
                            <iframe
                                title="خريطة {{ $store['name'] ?? $partner['name'] ?? 'المتجر' }}"
                                src="https://www.google.com/maps?q={{ urlencode($sectionAddress) }}&output=embed"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                allowfullscreen>
                            </iframe>
                            <div class="builder-map-google-badge">Google Maps</div>
                        </div>
                        <div class="builder-map-info">
                            <span class="section-kicker">زيارة المتجر</span>
                            <h2>{{ $title }}</h2>
                            <p>{{ $sectionAddress }}</p>
                            <div class="builder-map-meta">
                                <div><span>الدولة / المدينة</span><strong>{{ $settings['city'] ?? $store['city'] ?? 'Saudi Arabia' }}</strong></div>
                                <div><span>أوقات العمل</span><strong>{{ $workingHours ?: 'حسب مواعيد المتجر' }}</strong></div>
                                <div><span>التواصل</span><strong>{{ $contactPhone ?: ($contactEmail ?: 'متاح من النموذج') }}</strong></div>
                            </div>
                            <div class="builder-map-actions">
                                <a class="btn btn-primary" target="_blank" rel="noopener" href="https://maps.google.com/?q={{ urlencode($sectionAddress) }}">فتح الخريطة</a>
                                <button class="btn btn-soft" type="button" data-copy-address="{{ $sectionAddress }}">نسخ العنوان</button>
                                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/contact') }}">تواصل معنا</a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="panel" style="padding:28px">
                        <span class="section-kicker">{{ $type }}</span>
                        <h2>{{ $title }}</h2>
                        <p class="muted">{{ $body }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endforeach
    </div>
@endsection

@push('scripts')
<script>
const newsletterForm = document.getElementById('newsletterForm');
if (newsletterForm) {
    newsletterForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const form = new FormData(event.currentTarget);
        const response = await fetch(window.solveStorefront.newsletterEndpoint, {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
            body: JSON.stringify({email: form.get('email')})
        });
        const result = await response.json();
        const target = document.getElementById('newsletterResult');
        target.style.display = 'block';
        target.textContent = result.message || (response.ok ? 'تم الاشتراك.' : 'تعذر الاشتراك.');
    });
}
const offerSlides = Array.from(document.querySelectorAll('.wide-offer-slide'));
const offerDots = Array.from(document.querySelectorAll('.offer-dots button'));
let activeOffer = 0;
function showOffer(index) {
    if (!offerSlides.length) return;
    activeOffer = (index + offerSlides.length) % offerSlides.length;
    offerSlides.forEach((slide, slideIndex) => slide.classList.toggle('active', slideIndex === activeOffer));
    offerDots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === activeOffer));
}
offerDots.forEach(dot => dot.addEventListener('click', () => showOffer(Number(dot.dataset.offerIndex || 0))));
if (offerSlides.length > 1) {
    setInterval(() => showOffer(activeOffer + 1), 5200);
}
document.querySelectorAll('.builder-contact-form').forEach((form) => {
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        const button = form.querySelector('button[type="submit"]');
        const resultTarget = form.querySelector('.builder-contact-result');
        const payload = new FormData(form);
        if (button) {
            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = 'جار الإرسال...';
        }
        if (resultTarget) {
            resultTarget.style.display = 'block';
            resultTarget.classList.remove('is-success', 'is-error');
            resultTarget.textContent = 'نرسل رسالتك للمتجر الآن.';
        }
        try {
            const response = await fetch(window.solveStorefront.contactEndpoint, {
                method: 'POST',
                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify({
                    name: payload.get('name'),
                    contact: payload.get('contact'),
                    message: payload.get('message')
                })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'تعذر إرسال الرسالة.');
            if (resultTarget) {
                resultTarget.classList.add('is-success');
                resultTarget.textContent = data.message || 'تم استلام رسالتك.';
            }
            form.reset();
            window.trackStorefrontEvent('contact_message_sent', {section: form.dataset.sectionTitle || 'builder'});
        } catch (error) {
            if (resultTarget) {
                resultTarget.classList.add('is-error');
                resultTarget.textContent = error.message || 'تعذر إرسال الرسالة، حاول مرة أخرى.';
            }
        } finally {
            if (button) {
                button.disabled = false;
                button.textContent = button.dataset.originalText || 'إرسال الرسالة';
            }
        }
    });
});
document.querySelectorAll('[data-copy-address]').forEach((button) => {
    button.addEventListener('click', async function () {
        const address = button.dataset.copyAddress || '';
        try {
            if (navigator.clipboard) {
                await navigator.clipboard.writeText(address);
            } else {
                const textarea = document.createElement('textarea');
                textarea.value = address;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                textarea.remove();
            }
            button.textContent = 'تم نسخ العنوان';
            setTimeout(() => button.textContent = 'نسخ العنوان', 1800);
        } catch (error) {
            button.textContent = 'تعذر النسخ';
            setTimeout(() => button.textContent = 'نسخ العنوان', 1800);
        }
    });
});
</script>
@endpush
