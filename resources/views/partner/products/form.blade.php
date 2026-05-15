@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . ($product ? 'تعديل منتج' : 'منتج جديد'))

@section('partner-content')
@php
    $rawPrice = old('price', isset($product['price']) ? preg_replace('/[^\d.]/', '', $product['price']) : 0);
    $rawCompare = old('compare_at_price', isset($product['compare_at_price']) ? preg_replace('/[^\d.]/', '', (string) $product['compare_at_price']) : 0);
    $rawCost = old('cost_price', isset($product['cost_price']) ? preg_replace('/[^\d.]/', '', (string) $product['cost_price']) : 0);
    $tags = old('tags', isset($product['tags']) && is_array($product['tags']) ? implode(', ', $product['tags']) : '');
    $optionValues = old('option_values', isset($product['option_values']) && is_array($product['option_values']) ? implode(', ', $product['option_values']) : '');
@endphp

<div class="bg-slate-50 px-4 py-6 lg:px-8 dark:bg-slate-950"
    x-data="{
        price: Number(@js($rawPrice ?: 0)),
        compareAt: Number(@js($rawCompare ?: 0)),
        cost: Number(@js($rawCost ?: 0)),
        stock: Number(@js(old('stock', $product['stock'] ?? 0))),
        threshold: Number(@js(old('low_stock_threshold', $product['low_stock_threshold'] ?? 12))),
        status: @js(old('status', $product['status_key'] ?? 'published')),
        visibility: @js(old('visibility', $product['visibility'] ?? 'visible')),
        imageName: '',
        get margin() { return Math.max(0, this.price - this.cost); },
        get marginPercent() { return this.price > 0 ? Math.round((this.margin / this.price) * 100) : 0; },
        get hasDiscount() { return this.compareAt > this.price && this.price > 0; },
        get discountPercent() { return this.hasDiscount ? Math.round(((this.compareAt - this.price) / this.compareAt) * 100) : 0; },
        get stockState() { return this.stock <= this.threshold ? 'مخزون منخفض' : 'متوفر'; },
        money(value) { return new Intl.NumberFormat('ar-SA', { maximumFractionDigits: 2 }).format(value || 0) + ' ر.س'; },
        publishNow() { this.status = 'published'; this.visibility = 'visible'; },
        makeDraft() { this.status = 'draft'; this.visibility = 'hidden'; },
        lowStockPreset() { this.threshold = Math.max(1, Math.ceil(this.stock * 0.25)); },
        autoSave() { localStorage.setItem('solve.product.autosave', JSON.stringify({ price: this.price, stock: this.stock, status: this.status, visibility: this.visibility, savedAt: new Date().toISOString() })); }
    }">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.products') }}" class="text-sm font-black text-solve-600 dark:text-solve-300">العودة للمنتجات</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $product ? 'تعديل المنتج' : 'إنشاء منتج' }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">كل التغييرات تحفظ في قاعدة البيانات ضمن {{ $partner['store_id'] }} وتظهر في قائمة المنتجات والمخزون.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" @click="publishNow()" class="rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-black text-emerald-700">نشر الآن</button>
            <button type="button" @click="makeDraft()" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-black text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">حفظ كمسودة</button>
            @if ($product)
                <form method="POST" action="{{ route('partner.products.pause', ['product' => $product['id']]) }}">
                    @csrf
                    <button class="rounded-full border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-black text-amber-700">إيقاف المنتج</button>
                </form>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700">تحقق من الحقول المطلوبة قبل حفظ المنتج.</div>
    @endif

    <form method="POST" action="{{ $product ? route('partner.products.update', ['product' => $product['id']]) : route('partner.products.store') }}" enctype="multipart/form-data" class="grid gap-5 xl:grid-cols-[1fr_380px]">
        @csrf

        <div class="space-y-5">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">المعلومات الأساسية</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">اسم واضح، SKU قابل للتتبع، وتصنيف يساعد في إدارة المتجر.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block md:col-span-2">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">اسم المنتج</span>
                        <input name="name" value="{{ old('name', $product['name'] ?? '') }}" required placeholder="مثال: عطر مسك فاخر" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @error('name')<span class="mt-1 block text-xs font-bold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">SKU</span>
                        <input name="sku" value="{{ old('sku', $product['sku'] ?? '') }}" required placeholder="SOLVE-001" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الباركود</span>
                        <input name="barcode" value="{{ old('barcode', $product['barcode'] ?? '') }}" placeholder="اختياري" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">التصنيف</span>
                        <input name="category" value="{{ old('category', $product['category'] ?? 'عام') }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الماركة</span>
                        <input name="brand" value="{{ old('brand', $product['brand'] ?? '') }}" placeholder="اختياري" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">التاجات</span>
                        <input name="tags" value="{{ $tags }}" placeholder="عطر، جديد، هدية" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">التسعير والمخزون</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">احسب هامش الربح والخصم وتنبيه المخزون قبل النشر.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-3">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">السعر</span>
                        <input name="price" x-model.number="price" type="number" step="0.01" min="0" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">سعر المقارنة</span>
                        <input name="compare_at_price" x-model.number="compareAt" type="number" step="0.01" min="0" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">تكلفة المنتج</span>
                        <input name="cost_price" x-model.number="cost" type="number" step="0.01" min="0" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الكمية</span>
                        <input name="stock" x-model.number="stock" type="number" min="0" required class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">حد تنبيه المخزون</span>
                        <input name="low_stock_threshold" x-model.number="threshold" type="number" min="1" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الوزن kg</span>
                        <input name="weight" value="{{ old('weight', $product['weight'] ?? '') }}" type="number" step="0.01" min="0" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" @click="lowStockPreset()" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">اقتراح حد التنبيه</button>
                    <span class="rounded-full bg-slate-100 px-4 py-2 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300" x-text="'هامش الربح: ' + money(margin) + ' (' + marginPercent + '%)'"></span>
                    <span class="rounded-full bg-cyan-50 px-4 py-2 text-xs font-black text-cyan-700" x-show="hasDiscount" x-text="'خصم ' + discountPercent + '%'"></span>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4">
                    <h2 class="text-xl font-black text-slate-950 dark:text-white">النوع والخيارات</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">جهز المنتج الفردي أو متعدد الخيارات من نفس الصفحة.</p>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">النوع</span>
                        <select name="type" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            @foreach (\App\Support\PartnerProducts::PRODUCT_TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $product['type_key'] ?? 'single') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">اسم الخيار</span>
                        <input name="option_name" value="{{ old('option_name', $product['option_name'] ?? '') }}" placeholder="مثال: المقاس أو اللون" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">قيم الخيارات</span>
                        <input name="option_values" value="{{ $optionValues }}" placeholder="أحمر، أزرق، أسود" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <div class="grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">صورة المنتج</span>
                        <input name="image" type="file" accept="image/*" @change="imageName = Array.from($event.target.files).map(file => file.name).join('، '); autoSave()" class="mt-2 w-full rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <p class="mt-2 text-xs font-bold text-slate-500" x-show="imageName" x-text="imageName"></p>
                        <p class="mt-2 text-xs font-bold text-slate-400">يدعم السحب والإفلات، JPG/PNG/WebP حتى 4MB. يتم ضغطها من المتصفح/الخادم حسب إعدادات الاستضافة وحفظها كصورة رئيسية.</p>
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">حالة الظهور</span>
                        <select name="visibility" x-model="visibility" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            <option value="visible">ظاهر في المتجر</option>
                            <option value="hidden">مخفي</option>
                        </select>
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">الوصف</span>
                        <textarea name="description" rows="5" placeholder="اكتب وصفاً واضحاً للعميل ومحركات البحث" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('description', $product['description'] ?? '') }}</textarea>
                    </label>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">SEO والتشغيل</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">عنوان SEO</span>
                        <input name="seo_title" value="{{ old('seo_title', $product['seo_title'] ?? '') }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">تاريخ النشر</span>
                        <input name="published_at" type="datetime-local" value="{{ old('published_at', $product['published_at'] ?? '') }}" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                    </label>
                    <label class="block md:col-span-2">
                        <span class="text-sm font-black text-slate-700 dark:text-slate-300">وصف SEO</span>
                        <textarea name="seo_description" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">{{ old('seo_description', $product['seo_description'] ?? '') }}</textarea>
                    </label>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm font-black dark:border-slate-700">
                        تتبع المخزون
                        <input name="track_inventory" type="hidden" value="0">
                        <input name="track_inventory" type="checkbox" value="1" class="rounded border-slate-300" @checked(old('track_inventory', $product['track_inventory'] ?? true))>
                    </label>
                    <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm font-black dark:border-slate-700">
                        السماح بالطلبات المسبقة
                        <input name="allow_backorders" type="hidden" value="0">
                        <input name="allow_backorders" type="checkbox" value="1" class="rounded border-slate-300" @checked(old('allow_backorders', $product['allow_backorders'] ?? false))>
                    </label>
                    <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3 text-sm font-black dark:border-slate-700">
                        يتطلب شحن
                        <input name="requires_shipping" type="hidden" value="0">
                        <input name="requires_shipping" type="checkbox" value="1" class="rounded border-slate-300" @checked(old('requires_shipping', $product['requires_shipping'] ?? true))>
                    </label>
                </div>
            </section>
        </div>

        <aside class="space-y-5">
            <section class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">معاينة المنتج</h2>
                <div class="mt-5 rounded-2xl border border-slate-100 p-4 dark:border-slate-800">
                    <div class="flex h-36 items-center justify-center rounded-2xl bg-slate-100 text-3xl font-black text-slate-400 dark:bg-slate-950">
                        @if (! empty($product['image']))
                            <img src="{{ asset($product['image']) }}" alt="" class="h-full w-full rounded-2xl object-cover">
                        @else
                            S
                        @endif
                    </div>
                    <div class="mt-4 space-y-2 text-sm font-bold">
                        <div class="flex justify-between"><span class="text-slate-500">السعر</span><span x-text="money(price)"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">هامش الربح</span><span x-text="money(margin) + ' / ' + marginPercent + '%'"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">المخزون</span><span x-text="stockState"></span></div>
                        <div class="flex justify-between"><span class="text-slate-500">الظهور</span><span x-text="visibility === 'visible' ? 'ظاهر' : 'مخفي'"></span></div>
                    </div>
                </div>
                <label class="mt-5 block">
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">الحالة</span>
                    <select name="status" x-model="status" class="mt-2 h-12 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        @foreach (\App\Support\PartnerProducts::PRODUCT_STATUSES as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="mt-5 grid gap-2">
                    <button class="rounded-full bg-solve-700 px-6 py-3 text-sm font-black text-white">{{ $product ? 'حفظ التعديلات' : 'إنشاء المنتج' }}</button>
                    @if ($product)
                        <a href="{{ url('/products/' . \Illuminate\Support\Str::slug($product['name'] ?? $product['id'])) }}" target="_blank" rel="noopener noreferrer" class="rounded-full border border-slate-200 px-6 py-3 text-center text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">معاينة المنتج</a>
                    @endif
                    <a href="{{ route('partner.products') }}" class="rounded-full border border-slate-200 px-6 py-3 text-center text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">إلغاء</a>
                </div>
                <div class="mt-5 rounded-2xl bg-solve-50 p-4 text-xs font-bold leading-6 text-solve-800 dark:bg-solve-500/10 dark:text-solve-200">
                    المنتج سيظهر في قائمة المنتجات والمخزون والتقارير بنفس store_id، مع بيانات الخيارات والـ SEO داخل payload.
                </div>
            </section>
        </aside>
    </form>
</div>
@endsection
