@extends('storefront.layout')

@section('title', $product['seo_title'] ?? $product['name'])
@section('description', $product['seo_description'] ?? Str::limit(strip_tags($product['description'] ?? ''), 150))

@php
    $img = $product['image'] ?? null;
    $src = ! $img ? asset('solve-logo.png') : ((str_starts_with($img, 'http') || str_starts_with($img, '/')) ? $img : asset($img));
    $productUrl = url('/store/' . $slug . '/product/' . $product['id']);
    $stock = (int) ($product['stock'] ?? 0);
    $numericPrice = (float) preg_replace('/[^\d.]/', '', (string) ($product['price'] ?? 0));
    $oldPrice = $numericPrice > 0 ? number_format($numericPrice * 1.25, 0) . ' ر.س' : null;
    $discount = $numericPrice > 0 ? 20 : null;
    $reviewCount = max(1, count($reviews));
    $ratingAverage = number_format(collect($reviews)->avg(fn ($review) => (float) ($review['rating'] ?? $review['stars'] ?? 5)) ?: 4.8, 1);
    $cartProduct = [
        'id' => $product['id'] ?? null,
        'name' => $product['name'] ?? 'منتج',
        'price' => $product['price'] ?? '0 ر.س',
        'image' => $src,
        'sku' => $product['sku'] ?? '',
        'url' => $productUrl,
    ];
    $gallery = collect($product['media'] ?? [])
        ->map(fn ($media) => $media['path'] ?? $media['url'] ?? null)
        ->filter()
        ->map(fn ($path) => (str_starts_with($path, 'http') || str_starts_with($path, '/')) ? $path : asset($path))
        ->prepend($src)
        ->unique()
        ->values();
    $gallery = $gallery->isEmpty() ? collect([$src]) : $gallery;
    $colors = [
        ['name' => 'أسود', 'hex' => '#111827'],
        ['name' => 'بني', 'hex' => '#8b5e34'],
        ['name' => 'كحلي', 'hex' => '#0f2a56'],
        ['name' => 'بيج', 'hex' => '#eadfce'],
    ];
    $sizes = ['S', 'M', 'L', 'XL'];
    $description = $product['description'] ?? 'منتج مختار بعناية من المتجر، بتفاصيل واضحة وتجربة شراء سريعة وآمنة.';
@endphp

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'] ?? '',
    'sku' => $product['sku'] ?? '',
    'image' => $gallery->values()->all(),
    'description' => strip_tags($description),
    'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => $ratingAverage, 'reviewCount' => $reviewCount],
    'offers' => ['@type' => 'Offer', 'priceCurrency' => 'SAR', 'price' => preg_replace('/[^\d.]/', '', (string) ($product['price'] ?? 0)), 'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<style>
    body { font-family: "Cairo", "IBM Plex Sans Arabic", Tahoma, Arial, sans-serif; }
    .pp-page { padding: 26px 0 0; }
    .pp-crumbs { display:flex; gap:9px; align-items:center; flex-wrap:wrap; color:#64748b; font-size:13px; font-weight:900; margin-bottom:22px; }
    .pp-crumbs a { color:#475569; }
    .pp-shell { display:grid; grid-template-columns:430px minmax(0, 1fr) 112px; grid-template-areas:"info gallery thumbs"; gap:20px; align-items:start; }
    .pp-card { background:#fff; border:1px solid #dbe5f0; border-radius:22px; box-shadow:0 18px 48px rgba(15,23,42,.06); }
    .pp-info { grid-area:info; padding:26px; position:sticky; top:118px; }
    .pp-gallery { grid-area:gallery; position:relative; overflow:hidden; border-radius:22px; background:#f1f5f9; min-height:590px; }
    .pp-gallery img { width:100%; height:100%; min-height:590px; object-fit:cover; display:block; transition:transform .35s ease; }
    .pp-gallery:hover img { transform:scale(1.045); }
    .pp-thumbs { grid-area:thumbs; display:grid; gap:12px; }
    .pp-thumb { height:118px; border:2px solid transparent; border-radius:18px; overflow:hidden; background:#fff; padding:0; cursor:pointer; box-shadow:0 12px 28px rgba(15,23,42,.05); position:relative; }
    .pp-thumb.active { border-color:#1d4ed8; }
    .pp-thumb img { width:100%; height:100%; object-fit:cover; display:block; }
    .pp-more { position:absolute; inset:0; background:rgba(15,23,42,.72); color:#fff; display:grid; place-items:center; font-size:22px; font-weight:900; }
    .pp-gallery-tools { position:absolute; top:18px; right:18px; display:grid; gap:10px; z-index:2; }
    .pp-icon-btn { width:42px; height:42px; border:0; border-radius:14px; background:#111827; color:#fff; display:grid; place-items:center; cursor:pointer; font-weight:900; box-shadow:0 14px 28px rgba(15,23,42,.18); }
    .pp-stock-badge { position:absolute; right:18px; bottom:18px; z-index:2; border-radius:999px; padding:8px 12px; font-size:12px; font-weight:900; background:#dcfce7; color:#047857; }
    .pp-counter { position:absolute; left:18px; bottom:18px; z-index:2; border-radius:999px; padding:8px 12px; font-size:12px; font-weight:900; background:rgba(17,24,39,.85); color:#fff; }
    .pp-top-actions { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:16px; }
    .pp-round-actions { display:flex; gap:8px; }
    .pp-light-btn { width:44px; height:44px; border-radius:999px; border:1px solid #dbe5f0; background:#fff; display:grid; place-items:center; cursor:pointer; font-weight:900; color:#07111f; }
    .pp-tag { display:inline-flex; width:max-content; border-radius:999px; background:#fff7c2; color:#6b4e00; padding:7px 11px; font-size:12px; font-weight:900; }
    .pp-title { margin:12px 0 8px; font-size:clamp(28px, 3vw, 40px); line-height:1.22; letter-spacing:0; color:#07111f; }
    .pp-rating { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-weight:900; color:#64748b; }
    .pp-stars { color:#f59e0b; letter-spacing:1px; }
    .pp-price { margin-top:18px; display:flex; align-items:center; gap:14px; flex-wrap:wrap; }
    .pp-current { font-size:34px; font-weight:900; color:#07111f; }
    .pp-old { color:#94a3b8; text-decoration:line-through; font-size:22px; font-weight:900; }
    .pp-discount { border-radius:999px; padding:8px 12px; background:#fee2e2; color:#dc2626; font-size:13px; font-weight:900; }
    .pp-muted { color:#64748b; font-size:13px; font-weight:800; line-height:1.8; }
    .pp-divider { height:1px; background:#e8edf3; margin:18px 0; }
    .pp-benefits-grid { display:grid; grid-template-columns:repeat(3, minmax(0,1fr)); gap:10px; }
    .pp-benefit { border:1px solid #e8edf3; border-radius:16px; background:#f8fafc; padding:12px; display:flex; align-items:center; gap:9px; min-height:70px; }
    .pp-benefit strong { display:block; font-size:12px; }
    .pp-benefit span:last-child { display:block; margin-top:2px; color:#64748b; font-size:11px; font-weight:800; }
    .pp-mini-icon { width:34px; height:34px; border-radius:13px; display:grid; place-items:center; background:#fff; border:1px solid #dbe5f0; color:#0f172a; font-weight:900; flex:0 0 auto; }
    .pp-option-title { display:flex; justify-content:space-between; gap:12px; margin:17px 0 10px; font-weight:900; color:#07111f; }
    .pp-swatches, .pp-sizes { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
    .pp-swatch { width:36px; height:36px; border-radius:999px; border:4px solid #fff; outline:1px solid #dbe5f0; cursor:pointer; }
    .pp-swatch.active { outline:3px solid #07111f; }
    .pp-size { min-width:48px; height:38px; border-radius:10px; border:1px solid #dbe5f0; background:#fff; font-weight:900; cursor:pointer; }
    .pp-size.active, .pp-size:hover { background:#07111f; color:#fff; border-color:#07111f; }
    .pp-qty { margin-top:16px; display:flex; align-items:center; justify-content:flex-end; gap:8px; }
    .pp-stepper { display:grid; grid-template-columns:34px 48px 34px; height:36px; border:1px solid #dbe5f0; border-radius:12px; overflow:hidden; background:#fff; }
    .pp-stepper button { border:0; background:#f8fafc; cursor:pointer; font-weight:900; }
    .pp-stepper input { border:0; text-align:center; font-weight:900; min-width:0; }
    .pp-actions { margin-top:10px; display:grid; gap:10px; }
    .pp-primary-action, .pp-secondary-action { width:100%; min-height:52px; border-radius:14px; font-weight:900; cursor:pointer; transition:.18s ease; display:flex; align-items:center; justify-content:center; gap:10px; }
    .pp-primary-action { border:0; background:#07111f; color:#fff; box-shadow:0 18px 32px rgba(7,17,31,.15); }
    .pp-secondary-action { border:1px solid #07111f; background:#fff; color:#07111f; }
    .pp-primary-action:hover, .pp-secondary-action:hover { transform:translateY(-1px); }
    .pp-live-note { margin-top:14px; display:flex; align-items:center; justify-content:center; gap:8px; color:#64748b; font-size:13px; font-weight:900; }
    .pp-flame { color:#dc2626; }
    .pp-service-strip { margin:24px 0; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    .pp-service { border:1px solid #e8edf3; border-radius:18px; background:#fff; padding:14px; display:flex; align-items:center; gap:10px; box-shadow:0 12px 30px rgba(15,23,42,.04); }
    .pp-service strong { display:block; font-size:13px; }
    .pp-service span:last-child { display:block; margin-top:2px; color:#64748b; font-size:12px; font-weight:800; }
    .pp-tabs-card { overflow:hidden; margin-top:22px; }
    .pp-tab-nav { position:sticky; top:78px; z-index:5; display:grid; grid-template-columns:repeat(4,1fr); background:#fff; border-bottom:1px solid #e8edf3; }
    .pp-tab-nav button { min-height:56px; border:0; background:#fff; font-weight:900; color:#64748b; cursor:pointer; border-bottom:3px solid transparent; }
    .pp-tab-nav button.active { color:#07111f; border-bottom-color:#07111f; }
    .pp-tab-panel { display:none; padding:26px; color:#475569; line-height:2; font-weight:800; }
    .pp-tab-panel.active { display:block; }
    .pp-feature-row { margin-top:20px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
    .pp-feature { display:flex; align-items:center; gap:10px; border-radius:18px; background:#f8fafc; padding:14px; }
    .pp-review-layout { display:grid; grid-template-columns:1fr 1.2fr .9fr; gap:18px; margin-top:22px; }
    .pp-store-box, .pp-rating-box, .pp-comments-box { padding:24px; }
    .pp-rating-score { font-size:46px; font-weight:900; color:#07111f; }
    .pp-rating-bar { display:grid; grid-template-columns:32px 1fr 34px; align-items:center; gap:9px; margin-top:9px; color:#64748b; font-size:12px; font-weight:900; }
    .pp-rating-track { height:7px; border-radius:999px; background:#edf2f7; overflow:hidden; }
    .pp-rating-track span { display:block; height:100%; border-radius:999px; background:#0f2a56; }
    .pp-comment { display:flex; gap:12px; padding:13px 0; border-bottom:1px solid #eef2f7; }
    .pp-comment:last-child { border-bottom:0; }
    .pp-avatar { width:42px; height:42px; border-radius:999px; background:#f1f5f9; display:grid; place-items:center; font-weight:900; color:#0f172a; flex:0 0 auto; }
    .pp-section-head { display:flex; align-items:end; justify-content:space-between; gap:14px; margin:30px 0 14px; }
    .pp-section-head h2 { margin:0; font-size:28px; color:#07111f; }
    .pp-product-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:16px; }
    .pp-related-card { overflow:hidden; border:1px solid #e8edf3; border-radius:18px; background:#fff; box-shadow:0 12px 34px rgba(15,23,42,.05); position:relative; }
    .pp-related-card img { width:100%; aspect-ratio:1.35/1; object-fit:cover; background:#eef3f8; display:block; }
    .pp-related-body { padding:12px; }
    .pp-related-title { min-height:42px; margin:0; font-size:14px; line-height:1.5; color:#07111f; }
    .pp-related-meta { margin-top:8px; display:flex; justify-content:space-between; align-items:center; gap:8px; color:#64748b; font-size:12px; font-weight:900; }
    .pp-bottom-bar { margin-top:26px; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
    @media (max-width:1100px) {
        .pp-shell { grid-template-columns:1fr 100px; grid-template-areas:"gallery thumbs" "info info"; }
        .pp-info { position:relative; top:auto; }
        .pp-gallery img, .pp-gallery { min-height:500px; }
        .pp-review-layout { grid-template-columns:1fr; }
        .pp-product-row, .pp-bottom-bar { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    @media (max-width:760px) {
        .pp-page { padding-top:16px; }
        .pp-shell { grid-template-columns:1fr; grid-template-areas:"gallery" "thumbs" "info"; }
        .pp-thumbs { display:flex; overflow:auto; }
        .pp-thumb { min-width:88px; height:88px; }
        .pp-gallery, .pp-gallery img { min-height:390px; }
        .pp-info { padding:18px; }
        .pp-benefits-grid, .pp-service-strip, .pp-feature-row, .pp-tab-nav { grid-template-columns:1fr 1fr; }
        .pp-product-row, .pp-bottom-bar { grid-template-columns:1fr; }
        .pp-current { font-size:28px; }
        .pp-tab-panel { padding:18px; }
    }
</style>
@endpush

@section('content')
<section class="section pp-page">
    <div class="wrap">
        <nav class="pp-crumbs">
            <a href="{{ url('/store/' . $slug) }}">الرئيسية</a>
            <span>›</span>
            <a href="{{ url('/store/' . $slug . '/products') }}">المنتجات</a>
            <span>›</span>
            <span>{{ $product['category'] ?? 'عام' }}</span>
            <span>›</span>
            <strong>{{ $product['name'] ?? 'منتج' }}</strong>
        </nav>

        <div class="pp-shell">
            <aside class="pp-thumbs">
                @foreach($gallery->take(5) as $image)
                    <button class="pp-thumb {{ $loop->first ? 'active' : '' }}" type="button" onclick="setProductImage(@json($image), this, {{ $loop->iteration }})">
                        <img src="{{ $image }}" alt="{{ $product['name'] ?? 'منتج' }}">
                        @if($loop->last && $gallery->count() > 5)
                            <span class="pp-more">+{{ $gallery->count() - 5 }}</span>
                        @endif
                    </button>
                @endforeach
            </aside>

            <section class="pp-gallery pp-card">
                <div class="pp-gallery-tools">
                    <button class="pp-icon-btn" type="button" title="تكبير" onclick="window.open(document.getElementById('mainProductImage').src, '_blank')">⌕</button>
                </div>
                <img id="mainProductImage" src="{{ $src }}" alt="{{ $product['name'] ?? 'منتج' }}">
                <span class="pp-stock-badge">{{ $stock > 0 ? 'متوفر' : 'نفد' }}</span>
                <span id="galleryCounter" class="pp-counter">1/{{ $gallery->count() }}</span>
            </section>

            <aside class="pp-info pp-card">
                <div class="pp-top-actions">
                    @if($discount)
                        <span class="pp-tag">الأكثر مبيعاً</span>
                    @else
                        <span class="pp-tag">{{ $product['category'] ?? 'منتج' }}</span>
                    @endif
                    <div class="pp-round-actions">
                        <button class="pp-light-btn" type="button" title="المفضلة" onclick="solveToggleListItem('wishlist', @json($product['id']), this)">♡</button>
                        <button class="pp-light-btn" type="button" title="مشاركة" onclick="navigator.share ? navigator.share({title: document.title, url: location.href}) : navigator.clipboard.writeText(location.href)">↗</button>
                    </div>
                </div>

                <h1 class="pp-title">{{ $product['name'] ?? 'منتج' }}</h1>
                <div class="pp-rating">
                    <span class="pp-stars">★★★★★</span>
                    <span>{{ $ratingAverage }}</span>
                    <span>({{ $reviewCount }} تقييم)</span>
                </div>

                <div class="pp-price">
                    @if($discount)
                        <span class="pp-discount">خصم {{ $discount }}%</span>
                    @endif
                    <span class="pp-old">{{ $oldPrice }}</span>
                    <span class="pp-current">{{ $product['price'] ?? '0 ر.س' }}</span>
                </div>
                <p class="pp-muted">شامل الضريبة | توصيل سريع لجميع المناطق</p>
                <div class="pp-divider"></div>

                <div class="pp-benefits-grid">
                    <div class="pp-benefit"><span class="pp-mini-icon">↻</span><span><strong>توصيل سريع</strong><span>1-3 أيام عمل</span></span></div>
                    <div class="pp-benefit"><span class="pp-mini-icon">♢</span><span><strong>جودة عالية</strong><span>خامة ممتازة</span></span></div>
                    <div class="pp-benefit"><span class="pp-mini-icon">✓</span><span><strong>تسوق آمن</strong><span>دفع موثق</span></span></div>
                    <div class="pp-benefit"><span class="pp-mini-icon">⟳</span><span><strong>استرجاع سهل</strong><span>خلال 7 أيام</span></span></div>
                    <div class="pp-benefit"><span class="pp-mini-icon">▣</span><span><strong>دفع آمن</strong><span>جميع وسائل الدفع</span></span></div>
                    <div class="pp-benefit"><span class="pp-mini-icon">★</span><span><strong>متجر موثوق</strong><span>على منصة Solve</span></span></div>
                </div>

                <div class="pp-option-title"><span>اللون: <strong id="selectedColor">{{ $colors[0]['name'] }}</strong></span></div>
                <div class="pp-swatches">
                    @foreach($colors as $color)
                        <button class="pp-swatch {{ $loop->first ? 'active' : '' }}" type="button" style="background:{{ $color['hex'] }}" aria-label="{{ $color['name'] }}" onclick="selectProductColor(this, @json($color['name']))"></button>
                    @endforeach
                </div>

                <div class="pp-option-title">
                    <span>المقاس</span>
                    <a href="#specs" class="pp-muted" onclick="openProductTab('specs')">دليل المقاسات ⓘ</a>
                </div>
                <div class="pp-sizes">
                    @forelse(($product['variants'] ?? []) as $variant)
                        <button class="pp-size {{ $loop->first ? 'active' : '' }}" type="button">{{ $variant['value'] ?? $variant['sku'] ?? 'خيار' }}</button>
                    @empty
                        @foreach($sizes as $size)
                            <button class="pp-size {{ $loop->first ? 'active' : '' }}" type="button">{{ $size }}</button>
                        @endforeach
                    @endforelse
                </div>

                <div class="pp-qty">
                    <div class="pp-stepper">
                        <button type="button" onclick="changeProductQty(-1)">-</button>
                        <input id="qty" type="number" min="1" value="1">
                        <button type="button" onclick="changeProductQty(1)">+</button>
                    </div>
                </div>
                <div class="pp-actions">
                    <button class="pp-primary-action" type="button" @disabled($stock <= 0) onclick='addStorefrontCartItem(@json($product["id"]), document.getElementById("qty").value, @json($cartProduct))'>
                        أضف إلى السلة 🛒
                    </button>
                    <button class="pp-secondary-action" type="button" @disabled($stock <= 0) onclick='addStorefrontCartItem(@json($product["id"]), document.getElementById("qty").value, @json($cartProduct)).then(() => location.href = @json(url("/store/" . $slug . "/checkout")))'>
                        اشتري الآن
                    </button>
                </div>
                <div class="pp-live-note"><span class="pp-flame">🔥</span> 15 شخصاً شاهدوا هذا المنتج خلال 24 ساعة</div>
            </aside>
        </div>

        <div class="pp-service-strip">
            <div class="pp-service"><span class="pp-mini-icon">🚚</span><span><strong>شحن لجميع المناطق</strong><span>متاح محلياً</span></span></div>
            <div class="pp-service"><span class="pp-mini-icon">🛡</span><span><strong>أمان وجودة</strong><span>تسوق بثقة</span></span></div>
            <div class="pp-service"><span class="pp-mini-icon">🎧</span><span><strong>خدمة العملاء</strong><span>دعم سريع</span></span></div>
            <div class="pp-service"><span class="pp-mini-icon">↻</span><span><strong>ضمان لجميع القطع</strong><span>توصيل سريع</span></span></div>
        </div>

        <section class="pp-card pp-tabs-card">
            <nav class="pp-tab-nav">
                <button class="active" type="button" data-tab="description">الوصف</button>
                <button type="button" data-tab="specs">المواصفات</button>
                <button type="button" data-tab="policy">الشحن والاسترجاع</button>
                <button type="button" data-tab="reviews">التقييمات ({{ $reviewCount }})</button>
            </nav>
            <div id="description" class="pp-tab-panel active">
                <p>{{ $description }}</p>
                <div class="pp-feature-row">
                    <div class="pp-feature"><span class="pp-mini-icon">✂</span><span><strong>مقاس ظاهر فاخر</strong><br><span class="pp-muted">ملمس ناعم وراقي</span></span></div>
                    <div class="pp-feature"><span class="pp-mini-icon">✣</span><span><strong>تصميم عصري</strong><br><span class="pp-muted">يناسب جميع الأذواق</span></span></div>
                    <div class="pp-feature"><span class="pp-mini-icon">×</span><span><strong>سهل التنسيق</strong><br><span class="pp-muted">مناسب للمناسبات اليومية</span></span></div>
                    <div class="pp-feature"><span class="pp-mini-icon">❖</span><span><strong>خياطة دقيقة</strong><br><span class="pp-muted">جودة عالية وتفاصيل مميزة</span></span></div>
                </div>
            </div>
            <div id="specs" class="pp-tab-panel">
                <div class="pp-feature-row">
                    <div class="meta-tile"><span>رقم المنتج</span><strong>{{ $product['id'] }}</strong></div>
                    <div class="meta-tile"><span>SKU</span><strong>{{ $product['sku'] ?? '-' }}</strong></div>
                    <div class="meta-tile"><span>التصنيف</span><strong>{{ $product['category'] ?? 'عام' }}</strong></div>
                    <div class="meta-tile"><span>المخزون</span><strong>{{ $stock }}</strong></div>
                </div>
            </div>
            <div id="policy" class="pp-tab-panel">
                <p>يمكن الاستبدال أو الاسترجاع حسب سياسة المتجر وحالة المنتج. تفاصيل الشحن والاسترجاع تظهر من إعدادات التاجر عند تفعيلها.</p>
            </div>
            <div id="reviews" class="pp-tab-panel">
                <p>يعرض هذا القسم تقييمات العملاء المعتمدة من لوحة التاجر.</p>
            </div>
        </section>

        <section class="pp-review-layout">
            <div class="pp-card pp-store-box">
                <h3>المتجر</h3>
                <div style="display:flex;align-items:center;gap:12px;margin-top:18px">
                    <img src="{{ asset($settings['logo'] ?? $partner['logo'] ?? 'solve-logo.png') }}" alt="{{ $store['name'] ?? 'Store' }}" style="width:54px;height:54px;border-radius:18px;object-fit:contain;border:1px solid #dbe5f0">
                    <div>
                        <strong>{{ $store['name'] ?? ($partner['name'] ?? 'Solve Store') }}</strong>
                        <div class="pp-stars">★★★★★ <span style="color:#07111f">{{ $ratingAverage }}</span></div>
                    </div>
                </div>
                <p class="pp-muted" style="margin-top:16px">متجر موثوق يقدم منتجات عالية الجودة مع تجربة تسوق سهلة.</p>
                <a class="pp-muted" href="{{ url('/store/' . $slug) }}" style="color:#1d4ed8">عرض المتجر ↗</a>
            </div>

            <div class="pp-card pp-rating-box">
                <h3>التقييمات ({{ $reviewCount }})</h3>
                <div style="display:flex;gap:22px;align-items:center;margin-top:18px">
                    <div>
                        <div class="pp-rating-score">{{ $ratingAverage }}</div>
                        <div class="pp-stars">★★★★★</div>
                    </div>
                    <div style="flex:1">
                        @foreach([5 => 96, 4 => 20, 3 => 8, 2 => 3, 1 => 1] as $stars => $count)
                            <div class="pp-rating-bar"><span>{{ $stars }}★</span><div class="pp-rating-track"><span style="width:{{ min(100, $count) }}%"></span></div><span>{{ $count }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="pp-card pp-comments-box">
                @forelse($reviews as $review)
                    <div class="pp-comment">
                        <span class="pp-avatar">{{ Str::substr($review['customer'] ?? $review['name'] ?? 'ع', 0, 1) }}</span>
                        <div>
                            <strong>{{ $review['customer'] ?? $review['name'] ?? 'عميل' }}</strong>
                            <div class="pp-stars">★★★★★</div>
                            <p class="pp-muted" style="margin:6px 0 0">{{ $review['review'] ?? $review['body'] ?? $review['text'] ?? 'تجربة ممتازة.' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="min-height:150px">لا توجد تقييمات بعد.</div>
                @endforelse
            </div>
        </section>

        <div class="pp-section-head">
            <div>
                <h2>منتجات قد تعجبك</h2>
                <p class="pp-muted">اقتراحات مرتبطة من نفس المتجر.</p>
            </div>
            <a class="pp-muted" style="color:#1d4ed8" href="{{ url('/store/' . $slug . '/products') }}">عرض الكل</a>
        </div>
        <div class="pp-product-row">
            @forelse($relatedProducts as $related)
                @php
                    $relatedImage = $related['image'] ?? null;
                    $relatedSrc = ! $relatedImage ? asset('solve-logo.png') : ((str_starts_with($relatedImage, 'http') || str_starts_with($relatedImage, '/')) ? $relatedImage : asset($relatedImage));
                    $relatedUrl = url('/store/' . $slug . '/product/' . $related['id']);
                @endphp
                <article class="pp-related-card">
                    <a href="{{ $relatedUrl }}"><img src="{{ $relatedSrc }}" alt="{{ $related['name'] ?? 'منتج' }}"></a>
                    <div class="pp-related-body">
                        <h3 class="pp-related-title">{{ $related['name'] ?? 'منتج' }}</h3>
                        <div class="pp-related-meta"><strong>{{ $related['price'] ?? '0 ر.س' }}</strong><span>4.7 ★</span></div>
                    </div>
                </article>
            @empty
                <div class="empty-state" style="grid-column:1/-1">لا توجد منتجات مشابهة حالياً.</div>
            @endforelse
        </div>

        <div class="pp-section-head">
            <div>
                <h2>قد يناسبك أيضاً</h2>
                <p class="pp-muted">اختيارات إضافية تكمّل طلبك من أقسام مختلفة.</p>
            </div>
        </div>
        <div class="pp-product-row">
            @forelse($crossSellProducts as $crossSell)
                @php
                    $crossImage = $crossSell['image'] ?? null;
                    $crossSrc = ! $crossImage ? asset('solve-logo.png') : ((str_starts_with($crossImage, 'http') || str_starts_with($crossImage, '/')) ? $crossImage : asset($crossImage));
                    $crossUrl = url('/store/' . $slug . '/product/' . $crossSell['id']);
                @endphp
                <article class="pp-related-card">
                    <a href="{{ $crossUrl }}"><img src="{{ $crossSrc }}" alt="{{ $crossSell['name'] ?? 'منتج' }}"></a>
                    <div class="pp-related-body">
                        <h3 class="pp-related-title">{{ $crossSell['name'] ?? 'منتج' }}</h3>
                        <div class="pp-related-meta"><strong>{{ $crossSell['price'] ?? '0 ر.س' }}</strong><span>4.6 ★</span></div>
                    </div>
                </article>
            @empty
                <div class="empty-state" style="grid-column:1/-1">لا توجد اقتراحات إضافية حالياً.</div>
            @endforelse
        </div>

        <section class="pp-card" style="margin-top:26px;padding:22px;border-style:dashed">
            <h2 style="margin:0">شوهدت مؤخراً</h2>
            <p class="pp-muted" style="margin:8px 0 0">اختصارات محفوظة محلياً لتسهيل العودة للمنتجات التي تصفحتها.</p>
            <div id="recentlyViewed" style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px"></div>
        </section>

        <div class="pp-bottom-bar">
            <div class="pp-service"><span class="pp-mini-icon">🚚</span><span><strong>توصيل سريع</strong><span>1-3 أيام عمل</span></span></div>
            <div class="pp-service"><span class="pp-mini-icon">↻</span><span><strong>استرجاع سهل</strong><span>خلال 7 أيام</span></span></div>
            <div class="pp-service"><span class="pp-mini-icon">▣</span><span><strong>دفع آمن</strong><span>جميع وسائل الدفع</span></span></div>
            <div class="pp-service"><span class="pp-mini-icon">✓</span><span><strong>منتجات أصلية</strong><span>مضمونة 100%</span></span></div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
window.trackStorefrontEvent('product_view', {product_id: @json($product['id'])});
function setProductImage(src, button, index) {
    document.getElementById('mainProductImage').src = src;
    document.querySelectorAll('.pp-thumb').forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    document.getElementById('galleryCounter').textContent = `${index}/{{ $gallery->count() }}`;
}
function changeProductQty(delta) {
    const input = document.getElementById('qty');
    input.value = Math.max(1, Number(input.value || 1) + delta);
}
function selectProductColor(button, name) {
    document.querySelectorAll('.pp-swatch').forEach(item => item.classList.remove('active'));
    button.classList.add('active');
    document.getElementById('selectedColor').textContent = name;
}
document.querySelectorAll('.pp-size').forEach(button => {
    button.addEventListener('click', () => {
        button.parentElement.querySelectorAll('.pp-size').forEach(item => item.classList.remove('active'));
        button.classList.add('active');
    });
});
function openProductTab(tab) {
    document.querySelectorAll('.pp-tab-nav button').forEach(item => item.classList.remove('active'));
    document.querySelectorAll('.pp-tab-panel').forEach(item => item.classList.remove('active'));
    document.querySelector(`.pp-tab-nav button[data-tab="${tab}"]`)?.classList.add('active');
    document.getElementById(tab)?.classList.add('active');
}
document.querySelectorAll('.pp-tab-nav button').forEach(button => {
    button.addEventListener('click', () => openProductTab(button.dataset.tab));
});
const viewedKey = 'solve_recently_viewed_{{ $slug }}';
const currentProduct = {id: @json($product['id']), name: @json($product['name']), url: location.href};
const viewed = JSON.parse(localStorage.getItem(viewedKey) || '[]').filter(item => item.id !== currentProduct.id);
viewed.unshift(currentProduct);
localStorage.setItem(viewedKey, JSON.stringify(viewed.slice(0, 6)));
const recentlyViewed = document.getElementById('recentlyViewed');
if (recentlyViewed) {
    recentlyViewed.innerHTML = viewed.slice(1, 5).map(item => `<a class="btn btn-soft" href="${item.url}">${item.name}</a>`).join('') || '<p class="pp-muted">لا توجد منتجات سابقة.</p>';
}
</script>
@endpush
