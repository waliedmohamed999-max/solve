@php
    $img = $product['image'] ?? null;
    if (! $img) {
        $src = asset('solve-logo.png');
    } elseif (str_starts_with($img, 'http') || str_starts_with($img, '/')) {
        $src = $img;
    } else {
        $src = asset($img);
    }

    $productUrl = url('/store/' . $slug . '/product/' . $product['id']);
    $cartProduct = [
        'id' => $product['id'] ?? null,
        'name' => $product['name'] ?? 'منتج',
        'price' => $product['price'] ?? '0 ر.س',
        'image' => $src,
        'sku' => $product['sku'] ?? '',
        'url' => $productUrl,
    ];
    $stock = (int) ($product['stock'] ?? 0);
    $numericPrice = (float) preg_replace('/[^\d.]/', '', (string) ($product['price'] ?? 0));
    $oldPrice = $numericPrice > 0 ? number_format($numericPrice * 1.18, 2) . ' ر.س' : null;
    $discount = $numericPrice > 0 ? 15 : null;
@endphp
<article class="product-card">
    @if($discount)
        <span class="deal-tag">خصم {{ $discount }}%</span>
    @endif
    <a class="product-media" href="{{ $productUrl }}">
        <span class="product-tools">
            <button class="tool-btn" type="button" title="المفضلة" onclick="event.preventDefault(); solveToggleListItem('wishlist', @json($product['id']), this)">♡</button>
            <button class="tool-btn" type="button" title="مقارنة" onclick="event.preventDefault(); solveToggleListItem('compare', @json($product['id']), this)">⇄</button>
            <button class="tool-btn" type="button" title="عرض سريع" onclick="event.preventDefault(); location.href='{{ $productUrl }}'">↗</button>
        </span>
        <img src="{{ $src }}" alt="{{ $product['name'] ?? 'منتج' }}">
        <h3>{{ $product['name'] ?? 'منتج' }}</h3>
    </a>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:6px">
        <span class="rating">★ 4.8</span>
        <span class="muted" style="font-size:11px">{{ $product['category'] ?? 'عام' }}</span>
    </div>
    <div style="display:grid;gap:2px;margin-top:8px">
        <span class="price">{{ $product['price'] ?? '0 ر.س' }}</span>
        @if($oldPrice)
            <span class="old-price">{{ $oldPrice }}</span>
        @endif
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:10px">
        <span class="muted" style="font-size:11px">SKU: {{ $product['sku'] ?? '-' }}</span>
        <span class="badge" style="{{ $stock <= 0 ? 'background:#fee2e2;color:#b91c1c' : ($stock <= 5 ? 'background:#fff7ed;color:#c2410c' : 'background:#dcfce7;color:#047857') }}">
            {{ $stock <= 0 ? 'نفد' : ($stock <= 5 ? 'كمية محدودة' : 'متوفر') }}
        </span>
    </div>
    <div class="stock-meter"><span style="width:{{ max(8, min(100, $stock * 8)) }}%"></span></div>
    <div style="display:flex;gap:8px;margin-top:12px">
        <button class="btn btn-primary" style="flex:1;padding:10px 12px" type="button" @disabled($stock <= 0) onclick='addStorefrontCartItem(@json($product["id"]), 1, @json($cartProduct))'>أضف للسلة</button>
        <a class="btn btn-soft" style="padding:10px 12px" href="{{ $productUrl }}">عرض</a>
    </div>
</article>
