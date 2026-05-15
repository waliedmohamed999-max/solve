@extends('storefront.layout')

@section('title', 'الدفع - ' . ($store['name'] ?? $partner['name']))

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <h2>إتمام الطلب</h2>
                    <p>طلب حقيقي من واجهة المتجر، يتم ربطه بالطلبات والمخزون والفاتورة داخل لوحة التاجر.</p>
                </div>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/cart') }}">العودة للسلة</a>
            </div>
            <div class="panel" style="padding:16px;margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap">
                <span class="badge">1 بيانات العميل</span>
                <span class="badge">2 الشحن والدفع</span>
                <span class="badge">3 تأكيد الطلب</span>
                <span class="muted">Checkout مختصر بخطوة واحدة لتقليل التسرب.</span>
            </div>
            <div class="hero-grid" style="grid-template-columns:1.08fr .92fr">
                <form id="checkoutForm" class="panel" style="padding:24px;display:grid;gap:16px">
                    <div>
                        <h3 style="margin:0 0 10px">بيانات العميل</h3>
                        <div class="form-grid">
                            <input class="input" name="name" placeholder="اسم العميل" required>
                            <input class="input" name="phone" placeholder="الجوال" required>
                            <input class="input" name="email" type="email" placeholder="البريد">
                            <input class="input" name="city" placeholder="المدينة">
                            <input class="input" name="address" placeholder="العنوان التفصيلي">
                            <input id="checkoutCoupon" class="input" name="coupon_code" placeholder="كوبون">
                        </div>
                    </div>
                    <div>
                        <h3 style="margin:0 0 10px">الدفع والشحن</h3>
                        <div class="form-grid" style="grid-template-columns:repeat(2,minmax(0,1fr))">
                            <select class="input" name="payment_method">
                                <option value="payment_link">رابط دفع</option>
                                <option value="cod">الدفع عند الاستلام</option>
                                <option value="bank">تحويل بنكي</option>
                                <option value="card">بطاقة دفع</option>
                            </select>
                            <select class="input" name="shipping_method">
                                <option value="standard">شحن عادي</option>
                                <option value="express">شحن سريع</option>
                                <option value="pickup">استلام من الفرع</option>
                            </select>
                        </div>
                    </div>
                    <textarea class="input" style="height:100px;padding-top:14px" name="customer_note" placeholder="ملاحظة للطلب"></textarea>
                    <button id="submitOrderButton" class="btn btn-primary" type="submit">تأكيد الطلب</button>
                    <div id="checkoutResult" style="display:none"></div>
                </form>
                <aside class="panel" style="padding:24px;height:max-content;position:sticky;top:96px">
                    <h3 style="margin:0 0 14px">منتجات الطلب</h3>
                    <div id="checkoutItems" style="display:grid;gap:10px;margin-bottom:16px"></div>
                    <div id="checkoutSummary" style="display:grid;gap:4px"></div>
                    <p id="checkoutStatus" class="muted" style="margin:14px 0 0;line-height:1.8"></p>
                </aside>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
const checkoutItemsEl = document.getElementById('checkoutItems');
const checkoutSummaryEl = document.getElementById('checkoutSummary');
const checkoutStatusEl = document.getElementById('checkoutStatus');
const checkoutCoupon = document.getElementById('checkoutCoupon');
const submitOrderButton = document.getElementById('submitOrderButton');

function renderCheckoutItems() {
    const items = window.readStorefrontCart();
    if (!items.length) {
        checkoutItemsEl.innerHTML = `
            <div class="empty-state">
                <div>
                    <h3 style="margin:0 0 8px">لا توجد منتجات للدفع</h3>
                    <p class="muted" style="margin:0 0 16px">أضف منتجات للسلة أولاً.</p>
                    <a class="btn btn-primary" href="{{ url('/store/' . $slug . '/products') }}">تصفح المنتجات</a>
                </div>
            </div>`;
        submitOrderButton.disabled = true;
        submitOrderButton.style.opacity = '.55';
        return;
    }
    submitOrderButton.disabled = false;
    submitOrderButton.style.opacity = '1';
    checkoutItemsEl.innerHTML = items.map(item => `
        <div class="cart-line" style="grid-template-columns:58px 1fr auto;padding:10px">
            <img src="${item.image || '{{ asset('solve-logo.png') }}'}" style="width:58px;height:58px" alt="">
            <div>
                <strong>${item.name || 'منتج'}</strong>
                <p class="muted" style="margin:4px 0 0">الكمية: ${item.qty} | ${item.price || ''}</p>
            </div>
            <span class="badge">${item.sku || 'SKU'}</span>
        </div>`).join('');
}

async function refreshCheckoutSummary() {
    const items = window.readStorefrontCart();
    renderCheckoutItems();
    if (!items.length) {
        checkoutSummaryEl.innerHTML = '';
        checkoutStatusEl.textContent = '';
        return;
    }
    checkoutStatusEl.textContent = 'جاري حساب الطلب...';
    const response = await fetch(window.solveStorefront.cartEndpoint, {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify(window.cartApiPayload(checkoutCoupon.value))
    });
    const result = await response.json();
    if (!response.ok) {
        checkoutStatusEl.textContent = result.message || 'تعذر حساب الطلب.';
        return;
    }
    const totals = result.totals || {};
    checkoutSummaryEl.innerHTML = `
        <div class="summary-row"><span>قيمة المنتجات</span><strong>${totals.subtotal || '0 ر.س'}</strong></div>
        <div class="summary-row"><span>الخصم</span><strong>${totals.discount || '0 ر.س'}</strong></div>
        <div class="summary-row"><span>الشحن</span><strong>${totals.shipping || '0 ر.س'}</strong></div>
        <div class="summary-row"><span>الضريبة</span><strong>${totals.tax || '0 ر.س'}</strong></div>
        <div class="summary-row" style="font-size:20px"><span>الإجمالي</span><strong>${totals.total || '0 ر.س'}</strong></div>`;
    checkoutStatusEl.textContent = 'سيتم إنشاء الطلب داخل لوحة التاجر بعد التأكيد.';
}

checkoutCoupon.addEventListener('change', refreshCheckoutSummary);
window.addEventListener('solve-cart-updated', refreshCheckoutSummary);
window.trackStorefrontEvent('checkout_started');
refreshCheckoutSummary();

document.getElementById('checkoutForm').addEventListener('submit', async function (event) {
    event.preventDefault();
    const items = window.readStorefrontCart();
    if (!items.length) return;
    const form = new FormData(event.currentTarget);
    submitOrderButton.disabled = true;
    submitOrderButton.textContent = 'جاري إنشاء الطلب...';
    const response = await fetch(window.solveStorefront.checkoutEndpoint, {
        method: 'POST',
        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({
            customer: {name: form.get('name'), phone: form.get('phone'), email: form.get('email'), city: form.get('city'), address: form.get('address')},
            coupon_code: form.get('coupon_code'),
            payment_method: form.get('payment_method'),
            shipping_method: form.get('shipping_method'),
            customer_note: form.get('customer_note'),
            items: items.map(item => ({product_id: item.product_id, qty: Number(item.qty || 1)}))
        })
    });
    const data = await response.json();
    const result = document.getElementById('checkoutResult');
    result.style.display = 'block';
    if (!response.ok) {
        result.innerHTML = `<div class="empty-state" style="border-color:#fecaca;background:#fff1f2"><div><h3 style="margin:0 0 8px">تعذر إنشاء الطلب</h3><p class="muted">${data.message || 'راجع البيانات وحاول مرة أخرى.'}</p></div></div>`;
        submitOrderButton.disabled = false;
        submitOrderButton.textContent = 'تأكيد الطلب';
        return;
    }
    window.clearStorefrontCart();
    window.trackStorefrontEvent('order_created', {metadata: {order_id: data.order?.id || null}});
    result.innerHTML = `<div class="empty-state" style="border-color:#bbf7d0;background:#f0fdf4"><div><h3 style="margin:0 0 8px">تم إنشاء الطلب بنجاح</h3><p class="muted">رقم الطلب: ${data.order?.order_number || data.order?.id || '-'}</p><a class="btn btn-primary" href="{{ url('/store/' . $slug) }}">العودة للمتجر</a></div></div>`;
    submitOrderButton.textContent = 'تم إنشاء الطلب';
    refreshCheckoutSummary();
});
</script>
@endpush
