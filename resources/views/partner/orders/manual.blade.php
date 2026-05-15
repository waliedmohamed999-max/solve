@extends('layouts.partner')

@section('title', 'Solve Merchant | إنشاء طلب')

@section('partner-content')
<div class="bg-slate-50 px-4 py-6 lg:px-8 dark:bg-slate-950"
    x-data="{
        products: @js($orderProducts ?? []),
        selectedProductId: @js(old('product_id', '')),
        selectedProduct: null,
        qty: Number(@js(old('qty', 1))),
        unitPrice: Number(@js(old('unit_price', old('total', 0)))),
        discount: Number(@js(old('discount', 0))),
        shippingFee: Number(@js(old('shipping_fee', 0))),
        tax: Number(@js(old('tax', 0))),
        payment: @js(old('payment_status', 'unpaid')),
        priority: @js(old('fulfillment_priority', 'normal')),
        get subtotal() { return Math.max(0, this.qty * this.unitPrice); },
        get total() { return Math.max(0, this.subtotal - this.discount + this.shippingFee + this.tax); },
        money(value) { return new Intl.NumberFormat('ar-SA', { maximumFractionDigits: 2 }).format(value || 0) + ' ر.س'; },
        selectProduct(id) {
            this.selectedProductId = id;
            this.selectedProduct = this.products.find((product) => product.id === id) || null;
            if (this.selectedProduct) this.unitPrice = Number(this.selectedProduct.price || 0);
        },
        applyVat() { this.tax = Math.round((this.subtotal - this.discount) * 0.15 * 100) / 100; },
        freeShipping() { this.shippingFee = 0; },
        fastShipping() { this.shippingFee = 35; this.priority = 'fast'; },
        paidTemplate() { this.payment = 'paid'; },
        paymentLinkTemplate() { this.payment = 'pending'; },
        init() { if (this.selectedProductId) this.selectProduct(this.selectedProductId); }
    }">
    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.orders') }}" class="text-sm font-black text-solve-600 dark:text-solve-300">العودة للطلبات</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">إنشاء طلب يدوي</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">اختر المنتج من منتجات متجر {{ $partner['store_id'] }} ليحفظ الطلب مرتبطاً بالـ SKU والمخزون.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="paidTemplate()" class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">مدفوع</button>
            <button type="button" @click="paymentLinkTemplate()" class="rounded-full border border-solve-100 bg-solve-50 px-4 py-2 text-sm font-black text-solve-700">رابط دفع</button>
            <button type="button" @click="fastShipping()" class="rounded-full border border-cyan-200 bg-cyan-50 px-4 py-2 text-sm font-black text-cyan-700">شحن سريع</button>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">
            تحقق من الحقول المطلوبة قبل حفظ الطلب.
        </div>
    @endif

    <form method="POST" action="{{ route('partner.orders.manual.store') }}" class="grid gap-5 xl:grid-cols-[1fr_380px]">
        @csrf
        <input type="hidden" name="total" :value="total.toFixed(2)">
        <input type="hidden" name="item_name" :value="selectedProduct ? selectedProduct.name : @js(old('item_name', ''))">
        <input type="hidden" name="product_sku" :value="selectedProduct ? selectedProduct.sku : @js(old('product_sku', ''))">

        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">بيانات العميل</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">اربط الطلب بعميل ورقم جوال واضح للشحن والمتابعة.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 dark:bg-slate-800">1</span>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">اسم العميل</span>
                        <input name="customer" value="{{ old('customer') }}" required placeholder="مثال: نورة أحمد" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الجوال</span>
                        <input name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="9665xxxxxxxx" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">البريد</span>
                        <input name="email" type="email" value="{{ old('email') }}" placeholder="customer@example.com" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">المنتج والتسعير</h2>
                        <p class="mt-1 text-sm font-bold text-slate-500">اختر منتجاً موجوداً. السعر والـ SKU والمخزون يتم سحبها تلقائياً من بيانات المنتج.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-500 dark:bg-slate-800">2</span>
                </div>

                @if (empty($orderProducts))
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold leading-7 text-amber-800">
                        لا توجد منتجات متاحة لهذا المتجر حالياً. أضف منتجاً أولاً من صفحة المنتجات ثم ارجع لإنشاء الطلب.
                        <a href="{{ route('partner.products.new') }}" class="font-black underline">إضافة منتج</a>
                    </div>
                @endif

                <div class="mt-4 grid gap-4 md:grid-cols-4">
                    <label class="block md:col-span-2">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">المنتج من المخزون</span>
                        <select name="product_id" x-model="selectedProductId" @change="selectProduct($event.target.value)" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="">اختر منتجاً من منتجات المتجر</option>
                            <template x-for="product in products" :key="product.id">
                                <option :value="product.id" x-text="product.name + ' - ' + product.sku + ' - مخزون ' + product.stock"></option>
                            </template>
                        </select>
                        <p class="mt-2 text-xs font-bold text-slate-500" x-show="selectedProduct" x-text="'SKU: ' + selectedProduct.sku + ' · المخزون المتاح: ' + selectedProduct.stock"></p>
                        @error('item_name')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الكمية</span>
                        <input name="qty" x-model.number="qty" type="number" min="1" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">سعر الوحدة</span>
                        <input name="unit_price" x-model.number="unitPrice" type="number" step="0.01" min="0" required readonly class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الخصم</span>
                        <input name="discount" x-model.number="discount" type="number" step="0.01" min="0" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">رسوم الشحن</span>
                        <input name="shipping_fee" x-model.number="shippingFee" type="number" step="0.01" min="0" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الضريبة</span>
                        <input name="tax" x-model.number="tax" type="number" step="0.01" min="0" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">كود الخصم</span>
                        <input name="coupon_code" value="{{ old('coupon_code') }}" placeholder="اختياري" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="applyVat()" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">احتساب VAT 15%</button>
                    <button type="button" @click="freeShipping()" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">شحن مجاني</button>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">الدفع والشحن</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">حالة الدفع</span>
                        <select name="payment_status" x-model="payment" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="unpaid">غير مدفوع</option>
                            <option value="pending">بانتظار الدفع</option>
                            <option value="paid">مدفوع</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">طريقة الدفع</span>
                        <select name="payment_method" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="إرسال رابط دفع">إرسال رابط دفع</option>
                            <option value="Apple Pay">Apple Pay</option>
                            <option value="مدى">مدى</option>
                            <option value="تحويل بنكي">تحويل بنكي</option>
                            <option value="الدفع عند الاستلام">الدفع عند الاستلام</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">قناة الطلب</span>
                        <select name="source_channel" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="لوحة التحكم">لوحة التحكم</option>
                            <option value="واتساب">واتساب</option>
                            <option value="الهاتف">الهاتف</option>
                            <option value="إنستغرام">إنستغرام</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">طريقة الشحن</span>
                        <select name="shipping_method" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="عادي">عادي</option>
                            <option value="سريع">سريع</option>
                            <option value="استلام من الفرع">استلام من الفرع</option>
                            <option value="توصيل داخلي">توصيل داخلي</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الأولوية</span>
                        <select name="fulfillment_priority" x-model="priority" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="normal">عادي</option>
                            <option value="fast">سريع</option>
                            <option value="vip">VIP</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">المدينة</span>
                        <input name="city" value="{{ old('city') }}" placeholder="الرياض" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block md:col-span-3">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">العنوان</span>
                        <input name="address" value="{{ old('address') }}" placeholder="الحي، الشارع، رقم المبنى" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">الملاحظات</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">ملاحظة للعميل</span>
                        <textarea name="customer_note" rows="4" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('customer_note') }}</textarea>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">ملاحظة داخلية</span>
                        <textarea name="internal_note" rows="4" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('internal_note') }}</textarea>
                    </label>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">ملخص الطلب</h2>
                <div class="mt-5 space-y-3 text-sm font-bold">
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">المنتج</span><span x-text="selectedProduct ? selectedProduct.name : 'لم يتم الاختيار'"></span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">قيمة المنتجات</span><span x-text="money(subtotal)"></span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">الخصم</span><span x-text="money(discount)"></span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">الشحن</span><span x-text="money(shippingFee)"></span></div>
                    <div class="flex justify-between rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-950"><span class="text-slate-500">الضريبة</span><span x-text="money(tax)"></span></div>
                    <div class="flex justify-between rounded-2xl bg-slate-950 px-4 py-4 text-white dark:bg-white dark:text-slate-950"><span>الإجمالي</span><span class="text-xl" x-text="money(total)"></span></div>
                </div>
                <div class="mt-5 grid gap-2">
                    <button class="rounded-full bg-solve-700 px-6 py-3 text-sm font-black text-white transition hover:bg-solve-800">حفظ الطلب</button>
                    <a href="{{ route('partner.orders') }}" class="rounded-full border border-slate-200 px-6 py-3 text-center text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">إلغاء</a>
                </div>
                <div class="mt-5 rounded-2xl bg-solve-50 p-4 text-xs font-bold leading-6 text-solve-800 dark:bg-solve-500/10 dark:text-solve-200">
                    بعد الحفظ سيظهر الطلب في قائمة الطلبات، صفحة التفاصيل، Timeline، والفاتورة بنفس store_id وببيانات المنتج المختار.
                </div>
            </section>
        </aside>
    </form>
</div>
@endsection
