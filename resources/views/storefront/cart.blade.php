@extends('storefront.layout')

@section('title', 'سلة ' . ($store['name'] ?? $partner['name']))

@push('head')
<style>
    body { font-family:"Cairo","IBM Plex Sans Arabic",Tahoma,Arial,sans-serif; }
    .cart-page { padding-top:22px; }
    .cart-crumbs { display:flex; gap:8px; flex-wrap:wrap; color:#64748b; font-weight:900; font-size:13px; margin-bottom:14px; }
    .cart-hero { border:1px solid #dbe5f0; background:linear-gradient(135deg,#fff,#fffbea); border-radius:26px; padding:26px; display:flex; align-items:center; justify-content:space-between; gap:18px; box-shadow:0 18px 48px rgba(15,23,42,.06); margin-bottom:16px; }
    .cart-hero h1 { margin:0; font-size:clamp(30px,4vw,44px); }
    .cart-hero p { margin:8px 0 0; color:#64748b; font-weight:900; line-height:1.8; }
    .cart-benefits { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:16px; }
    .cart-benefit { border:1px solid #dbe5f0; background:#fff; border-radius:18px; padding:14px; display:flex; align-items:center; gap:10px; font-weight:900; box-shadow:0 12px 30px rgba(15,23,42,.04); }
    .cart-benefit span { width:40px; height:40px; border-radius:14px; display:grid; place-items:center; background:#feee00; color:#111827; }
    .cart-layout { display:grid; grid-template-columns:minmax(0,1fr) 380px; gap:18px; align-items:start; }
    .cart-panel,.summary-panel { border:1px solid #dbe5f0; background:#fff; border-radius:24px; box-shadow:0 18px 48px rgba(15,23,42,.06); }
    .cart-panel { padding:22px; }
    .cart-panel-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
    .cart-panel-head h2,.summary-panel h2 { margin:0; font-size:24px; }
    .cart-items-list { display:grid; gap:14px; }
    .cart-item-card { position:relative; display:grid; grid-template-columns:118px 1fr auto; gap:16px; align-items:center; padding:14px; border:1px solid #e8edf3; border-radius:22px; background:#fff; transition:.2s ease; }
    .cart-item-card:hover { transform:translateY(-2px); box-shadow:0 18px 38px rgba(15,23,42,.08); }
    .cart-item-card.removing { opacity:0; transform:scale(.98); }
    .cart-item-card img { width:118px; height:118px; border-radius:18px; object-fit:cover; background:#f1f5f9; }
    .cart-item-title { font-weight:900; font-size:18px; display:inline-block; margin-bottom:8px; }
    .cart-item-meta { display:flex; gap:8px; flex-wrap:wrap; align-items:center; color:#64748b; font-size:12px; font-weight:900; }
    .cart-item-price { font-size:20px; font-weight:900; color:#111827; margin-top:12px; }
    .cart-item-actions { display:grid; justify-items:end; gap:10px; }
    .cart-qty { display:grid; grid-template-columns:38px 54px 38px; height:42px; border:1px solid #dbe5f0; border-radius:15px; overflow:hidden; background:#fff; }
    .cart-qty button { border:0; background:#f1f5f9; cursor:pointer; font-weight:900; }
    .cart-qty input { border:0; text-align:center; font-weight:900; }
    .remove-btn { width:42px; height:42px; border:1px solid #fee2e2; color:#b91c1c; background:#fff1f2; border-radius:14px; cursor:pointer; font-weight:900; }
    .summary-panel { padding:22px; position:sticky; top:118px; }
    .coupon-box { display:grid; grid-template-columns:1fr auto; gap:8px; padding:10px; background:#f8fafc; border:1px solid #e8edf3; border-radius:18px; margin:16px 0; }
    .coupon-box input { border:0; background:transparent; min-width:0; height:40px; padding:0 8px; font-weight:900; outline:0; }
    .summary-total { display:flex; align-items:center; justify-content:space-between; gap:16px; padding-top:14px; margin-top:4px; border-top:2px solid #111827; }
    .summary-total strong { font-size:28px; }
    .checkout-cta { width:100%; margin-top:18px; min-height:54px; border-radius:18px; background:linear-gradient(135deg,#111827,#253044); color:#fff; box-shadow:0 18px 38px rgba(17,24,39,.18); }
    .checkout-cta.loading { opacity:.7; pointer-events:none; }
    .free-shipping { margin-top:14px; padding:14px; border-radius:18px; background:#fffbea; border:1px solid #fef08a; }
    .progress-track { height:9px; border-radius:999px; background:#fef3c7; overflow:hidden; margin-top:10px; }
    .progress-fill { display:block; height:100%; width:0%; border-radius:999px; background:#111827; transition:.25s ease; }
    .payment-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
    .payment-row span { border:1px solid #dbe5f0; background:#fff; border-radius:12px; padding:8px 10px; font-weight:900; font-size:12px; }
    .cart-empty-premium { min-height:360px; display:grid; place-items:center; text-align:center; border:1px dashed #cbd5e1; border-radius:24px; background:linear-gradient(180deg,#f8fafc,#fff); padding:34px; }
    .empty-icon { width:86px; height:86px; border-radius:28px; display:grid; place-items:center; background:#feee00; color:#111827; font-size:34px; font-weight:900; margin:auto auto 18px; box-shadow:0 18px 34px rgba(254,238,0,.3); }
    .cart-skeleton { display:grid; gap:12px; }
    .cart-skeleton span { height:112px; border-radius:22px; background:linear-gradient(90deg,#f1f5f9,#fff,#f1f5f9); background-size:200% 100%; animation:skeleton 1.1s infinite; }
    .cart-suggestions { margin-top:18px; border:1px solid #dbe5f0; border-radius:24px; background:#fff; padding:20px; box-shadow:0 18px 48px rgba(15,23,42,.04); }
    .suggestion-row { display:flex; gap:10px; flex-wrap:wrap; margin-top:12px; }
    @keyframes skeleton { to { background-position:-200% 0; } }
    @media (max-width: 980px) {
        .cart-layout { grid-template-columns:1fr; }
        .summary-panel { position:relative; top:auto; }
        .cart-benefits { grid-template-columns:1fr; }
    }
    @media (max-width: 640px) {
        .cart-hero { align-items:flex-start; flex-direction:column; padding:20px; }
        .cart-item-card { grid-template-columns:88px 1fr; }
        .cart-item-card img { width:88px; height:88px; }
        .cart-item-actions { grid-column:1/-1; grid-template-columns:1fr auto; display:grid; justify-items:stretch; }
        .coupon-box { grid-template-columns:1fr; }
    }
</style>
@endpush

@section('content')
    <section class="section cart-page">
        <div class="wrap">
            <nav class="cart-crumbs">
                <a href="{{ url('/store/' . $slug) }}">الرئيسية</a>
                <span>/</span>
                <span>سلة التسوق</span>
            </nav>

            <div class="cart-hero">
                <div>
                    <span class="section-kicker">Checkout Ready</span>
                    <h1>سلة التسوق</h1>
                    <p>راجع المنتجات وعدّل الكميات وطبّق الكوبون قبل إكمال الدفع.</p>
                </div>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products') }}">متابعة التسوق</a>
            </div>

            <div class="cart-benefits">
                <div class="cart-benefit"><span>⇄</span> شحن سريع وواضح</div>
                <div class="cart-benefit"><span>✓</span> دفع آمن ومحمي</div>
                <div class="cart-benefit"><span>↺</span> استرجاع مرن حسب سياسة المتجر</div>
            </div>

            <div class="cart-layout">
                <div>
                    <div class="cart-panel">
                        <div class="cart-panel-head">
                            <div>
                                <h2>منتجات السلة</h2>
                                <p class="muted" style="margin:6px 0 0">تحديث الكمية والسعر يتم مباشرة من API المتجر.</p>
                            </div>
                            <button class="btn btn-ghost" type="button" id="clearCartButton">إفراغ السلة</button>
                        </div>
                        <div id="cartItems" class="cart-items-list">
                            <div class="cart-skeleton"><span></span><span></span></div>
                        </div>
                    </div>

                    <div class="cart-suggestions">
                        <div class="section-head" style="margin-bottom:0">
                            <div>
                                <span class="section-kicker">اقتراحات</span>
                                <h2 style="font-size:22px">قد تحتاج أيضاً</h2>
                                <p>اختصارات تساعدك تكمل التسوق بسرعة.</p>
                            </div>
                        </div>
                        <div class="suggestion-row">
                            <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products?sort=latest') }}">وصل حديثاً</a>
                            <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products?sort=price_asc') }}">أفضل سعر</a>
                            <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/categories') }}">التصنيفات</a>
                        </div>
                    </div>
                </div>

                <aside class="summary-panel">
                    <h2>ملخص الطلب</h2>
                    <div class="coupon-box">
                        <input id="couponCode" placeholder="أدخل كود الخصم">
                        <button id="applyCouponButton" class="btn btn-soft" type="button">تطبيق</button>
                    </div>
                    <div id="cartSummary" style="display:grid;gap:4px">
                        <div class="summary-row"><span>قيمة المنتجات</span><strong>0 ر.س</strong></div>
                        <div class="summary-row"><span>الخصم</span><strong>0 ر.س</strong></div>
                        <div class="summary-row"><span>الشحن</span><strong>0 ر.س</strong></div>
                        <div class="summary-row"><span>الضريبة</span><strong>0 ر.س</strong></div>
                        <div class="summary-total"><span>الإجمالي</span><strong>0 ر.س</strong></div>
                    </div>
                    <div class="free-shipping">
                        <strong>الشحن المجاني</strong>
                        <p id="shippingProgressText" class="muted" style="margin:6px 0 0">أضف منتجات بقيمة 300 ر.س للحصول على شحن مجاني.</p>
                        <div class="progress-track"><span id="shippingProgressFill" class="progress-fill"></span></div>
                    </div>
                    <p id="estimatedDelivery" class="muted" style="margin:14px 0 0;line-height:1.8">التوصيل المتوقع يظهر بعد تحديث السلة.</p>
                    <a id="checkoutButton" class="btn checkout-cta" href="{{ url('/store/' . $slug . '/checkout') }}">إكمال الدفع</a>
                    <div class="payment-row">
                        <span>Mada</span><span>Visa</span><span>Apple Pay</span><span>COD</span>
                    </div>
                    <p id="cartStatus" class="muted" style="margin:14px 0 0;line-height:1.8"></p>
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
const cartItemsEl = document.getElementById('cartItems');
const cartSummaryEl = document.getElementById('cartSummary');
const cartStatusEl = document.getElementById('cartStatus');
const couponInput = document.getElementById('couponCode');
const shippingProgressFill = document.getElementById('shippingProgressFill');
const shippingProgressText = document.getElementById('shippingProgressText');
const estimatedDelivery = document.getElementById('estimatedDelivery');
const checkoutButton = document.getElementById('checkoutButton');

function moneyValue(value) {
    return value || '0 ر.س';
}

function numericMoney(value) {
    return Number(String(value || 0).replace(/[^\d.]/g, '')) || 0;
}

function renderCartItems() {
    const items = window.readStorefrontCart();
    if (!items.length) {
        cartItemsEl.innerHTML = `
            <div class="cart-empty-premium">
                <div>
                    <div class="empty-icon">🛒</div>
                    <h3 style="margin:0 0 8px;font-size:26px">سلتك فارغة</h3>
                    <p class="muted" style="margin:0 auto 18px;max-width:420px">ابدأ بإضافة منتجات من المتجر، وسنحفظها هنا تلقائياً لتكمل الطلب بسرعة.</p>
                    <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/products') }}">تصفح المنتجات</a>
                </div>
            </div>`;
        checkoutButton.style.pointerEvents = 'none';
        checkoutButton.style.opacity = '.55';
        return;
    }
    checkoutButton.style.pointerEvents = '';
    checkoutButton.style.opacity = '1';
    cartItemsEl.innerHTML = items.map(item => {
        const price = numericMoney(item.price);
        const line = price * Number(item.qty || 1);
        return `
        <article class="cart-item-card" id="cart-item-${item.product_id}">
            <img src="${item.image || '{{ asset('solve-logo.png') }}'}" alt="">
            <div>
                <a class="cart-item-title" href="${item.url || '#'}">${item.name || 'منتج'}</a>
                <div class="cart-item-meta">
                    <span class="badge">متوفر</span>
                    <span>SKU: ${item.sku || '-'}</span>
                    <span>السعر: ${item.price || '0 ر.س'}</span>
                </div>
                <div class="cart-item-price">${line ? line.toLocaleString('ar-SA', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ر.س' : item.price || '0 ر.س'}</div>
            </div>
            <div class="cart-item-actions">
                <span class="cart-qty">
                    <button type="button" onclick="changeCartQty('${item.product_id}', -1)">-</button>
                    <input value="${item.qty}" readonly>
                    <button type="button" onclick="changeCartQty('${item.product_id}', 1)">+</button>
                </span>
                <button class="remove-btn" type="button" title="حذف" onclick="deleteCartItem('${item.product_id}')">⌫</button>
            </div>
        </article>`;
    }).join('');
}

function renderEmptySummary() {
    cartSummaryEl.innerHTML = `
        <div class="summary-row"><span>قيمة المنتجات</span><strong>0 ر.س</strong></div>
        <div class="summary-row"><span>الخصم</span><strong>0 ر.س</strong></div>
        <div class="summary-row"><span>الشحن</span><strong>0 ر.س</strong></div>
        <div class="summary-row"><span>الضريبة</span><strong>0 ر.س</strong></div>
        <div class="summary-total"><span>الإجمالي</span><strong>0 ر.س</strong></div>`;
    shippingProgressFill.style.width = '0%';
    shippingProgressText.textContent = 'أضف منتجات بقيمة 300 ر.س للحصول على شحن مجاني.';
    estimatedDelivery.textContent = 'التوصيل المتوقع يظهر بعد تحديث السلة.';
}

function updateShippingProgress(subtotal) {
    const target = 300;
    const percent = Math.max(0, Math.min(100, (subtotal / target) * 100));
    shippingProgressFill.style.width = percent + '%';
    if (subtotal >= target) {
        shippingProgressText.textContent = 'رائع، طلبك مؤهل للشحن المجاني.';
    } else {
        shippingProgressText.textContent = `تبقى ${(target - subtotal).toFixed(2)} ر.س للحصول على شحن مجاني.`;
    }
    const date = new Date();
    date.setDate(date.getDate() + 3);
    estimatedDelivery.textContent = `التوصيل المتوقع: ${date.toLocaleDateString('ar-SA', {weekday:'long', day:'numeric', month:'long'})}`;
}

async function refreshCartSummary() {
    const items = window.readStorefrontCart();
    renderCartItems();
    if (!items.length) {
        renderEmptySummary();
        cartStatusEl.textContent = '';
        return;
    }
    cartStatusEl.textContent = 'جاري تحديث ملخص الطلب...';
    const response = await fetch(window.solveStorefront.cartEndpoint, {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify(window.cartApiPayload(couponInput.value))
    });
    const result = await response.json();
    if (!response.ok) {
        cartStatusEl.textContent = result.message || 'تعذر حساب السلة. تأكد من توفر المنتجات.';
        return;
    }
    const totals = result.totals || {};
    cartSummaryEl.innerHTML = `
        <div class="summary-row"><span>قيمة المنتجات</span><strong>${moneyValue(totals.subtotal)}</strong></div>
        <div class="summary-row"><span>الخصم</span><strong>${moneyValue(totals.discount)}</strong></div>
        <div class="summary-row"><span>الشحن</span><strong>${moneyValue(totals.shipping)}</strong></div>
        <div class="summary-row"><span>الضريبة</span><strong>${moneyValue(totals.tax)}</strong></div>
        <div class="summary-total"><span>الإجمالي</span><strong>${moneyValue(totals.total)}</strong></div>`;
    updateShippingProgress(Number(totals.subtotal_numeric || 0));
    cartStatusEl.textContent = result.coupon_code ? 'تم تطبيق الكوبون على السلة إن كان صالحاً.' : 'الأسعار محدثة مباشرة حسب بيانات المتجر.';
}

function changeCartQty(productId, delta) {
    const items = window.readStorefrontCart().map(item => {
        if (String(item.product_id) === String(productId)) {
            item.qty = Math.max(1, Number(item.qty || 1) + delta);
        }
        return item;
    });
    window.writeStorefrontCart(items);
    refreshCartSummary();
}

function deleteCartItem(productId) {
    const row = document.getElementById('cart-item-' + productId);
    if (row) row.classList.add('removing');
    setTimeout(() => {
        window.removeStorefrontCartItem(productId);
        refreshCartSummary();
    }, 160);
}

document.getElementById('clearCartButton').addEventListener('click', function () {
    if (!confirm('هل تريد إفراغ السلة؟')) return;
    window.clearStorefrontCart();
    refreshCartSummary();
});
document.getElementById('applyCouponButton').addEventListener('click', refreshCartSummary);
checkoutButton.addEventListener('click', function () {
    checkoutButton.classList.add('loading');
    checkoutButton.textContent = 'جاري الانتقال للدفع...';
});
window.addEventListener('solve-cart-updated', refreshCartSummary);
setTimeout(refreshCartSummary, 180);
</script>
@endpush
