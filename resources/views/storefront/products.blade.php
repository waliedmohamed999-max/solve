@extends('storefront.layout')

@section('title', 'منتجات ' . ($store['name'] ?? $partner['name']))

@section('content')
    @php
        $pagination = $productsPage['pagination'] ?? ['total' => count($productsPage['products'] ?? []), 'page' => 1, 'last_page' => 1];
        $view = request('view', 'grid');
        $queryWithView = fn (string $mode) => request()->fullUrlWithQuery(['view' => $mode]);
    @endphp
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>المنتجات</h2>
                    <p>منتجات منشورة ومربوطة بمخزون المتجر، مع بحث وفلاتر من السيرفر.</p>
                </div>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug) }}">العودة للرئيسية</a>
            </div>

            <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr));margin-bottom:16px">
                <div class="mini-stat"><span class="muted">عدد النتائج</span><strong>{{ $pagination['total'] ?? 0 }}</strong></div>
                <div class="mini-stat"><span class="muted">التصنيف الحالي</span><strong style="font-size:18px">{{ request('category', 'كل التصنيفات') }}</strong></div>
                <div class="mini-stat"><span class="muted">طريقة العرض</span><strong style="font-size:18px">{{ $view === 'list' ? 'قائمة' : 'شبكة' }}</strong></div>
            </div>

            <form class="toolbar panel" method="GET">
                <input class="input" name="q" value="{{ request('q') }}" placeholder="بحث عن منتج أو SKU">
                <select class="input" name="category">
                    <option value="all">كل التصنيفات</option>
                    @foreach($productsPage['categories'] as $category)
                        <option value="{{ $category['name'] }}" @selected(request('category') === ($category['name'] ?? ''))>{{ $category['name'] }}</option>
                    @endforeach
                </select>
                <input class="input" name="min_price" type="number" min="0" value="{{ request('min_price') }}" placeholder="أقل سعر">
                <input class="input" name="max_price" type="number" min="0" value="{{ request('max_price') }}" placeholder="أعلى سعر">
                <select class="input" name="sort">
                    <option value="latest" @selected(request('sort') === 'latest')>الأحدث</option>
                    <option value="price_asc" @selected(request('sort') === 'price_asc')>السعر الأقل</option>
                    <option value="price_desc" @selected(request('sort') === 'price_desc')>السعر الأعلى</option>
                    <option value="stock" @selected(request('sort') === 'stock')>المتوفر أكثر</option>
                </select>
                <input type="hidden" name="view" value="{{ $view }}">
                <button class="btn btn-primary">تطبيق</button>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products') }}">إعادة ضبط</a>
                <span class="segmented" style="margin-inline-start:auto">
                    <a class="{{ $view !== 'list' ? 'active' : '' }}" href="{{ $queryWithView('grid') }}">شبكة</a>
                    <a class="{{ $view === 'list' ? 'active' : '' }}" href="{{ $queryWithView('list') }}">قائمة</a>
                </span>
            </form>

            @if($view === 'list')
                <div style="display:grid;gap:12px">
                    @forelse($productsPage['products'] as $product)
                        @php
                            $img = $product['image'] ?? null;
                            $src = ! $img ? asset('solve-logo.png') : ((str_starts_with($img, 'http') || str_starts_with($img, '/')) ? $img : asset($img));
                            $productUrl = url('/store/' . $slug . '/product/' . $product['id']);
                            $cartProduct = ['name' => $product['name'] ?? 'منتج', 'price' => $product['price'] ?? '0 ر.س', 'image' => $src, 'sku' => $product['sku'] ?? '', 'url' => $productUrl];
                        @endphp
                        <article class="product-row">
                            <a href="{{ $productUrl }}"><img src="{{ $src }}" alt="{{ $product['name'] ?? 'منتج' }}"></a>
                            <div>
                                <a href="{{ $productUrl }}" style="font-weight:900;font-size:18px">{{ $product['name'] ?? 'منتج' }}</a>
                                <p class="muted" style="margin:6px 0">SKU: {{ $product['sku'] ?? '-' }} | {{ $product['category'] ?? 'عام' }} | المخزون: {{ $product['stock'] ?? 0 }}</p>
                                <span class="price">{{ $product['price'] ?? '0 ر.س' }}</span>
                            </div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end">
                                <button class="btn btn-primary" type="button" onclick='addStorefrontCartItem(@json($product["id"]), 1, @json($cartProduct))'>أضف للسلة</button>
                                <a class="btn btn-soft" href="{{ $productUrl }}">تفاصيل</a>
                            </div>
                        </article>
                    @empty
                        <div class="empty-state">
                            <div>
                                <h3 style="margin:0 0 8px">لا توجد منتجات مطابقة</h3>
                                <p class="muted">جرّب تغيير البحث أو السعر أو التصنيف.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="products-grid">
                    @forelse($productsPage['products'] as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @empty
                        <div class="empty-state" style="grid-column:1/-1">
                            <div>
                                <h3 style="margin:0 0 8px">لا توجد منتجات مطابقة</h3>
                                <p class="muted">جرّب تغيير البحث أو السعر أو التصنيف.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif

            @if(($pagination['last_page'] ?? 1) > 1)
                <div class="panel" style="padding:14px;margin-top:16px;display:flex;align-items:center;justify-content:space-between;gap:12px">
                    <span class="muted">صفحة {{ $pagination['page'] }} من {{ $pagination['last_page'] }}</span>
                    <div style="display:flex;gap:8px">
                        @if(($pagination['page'] ?? 1) > 1)
                            <a class="btn btn-soft" href="{{ request()->fullUrlWithQuery(['page' => $pagination['page'] - 1]) }}">السابق</a>
                        @endif
                        @if(($pagination['page'] ?? 1) < ($pagination['last_page'] ?? 1))
                            <a class="btn btn-primary" href="{{ request()->fullUrlWithQuery(['page' => $pagination['page'] + 1]) }}">التالي</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
