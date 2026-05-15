@extends('layouts.admin')

@section('title', 'إدارة منتجات المتاجر - Solve Admin')

@section('admin-content')
    @include('admin.components.toast')
    @include('admin.components.confirm-dialog')

    @php
        $selectedStore = $productsDashboard['selectedStore'] ?? null;
        $filters = $productsDashboard['filters'];
    @endphp

    <section class="mt-6 space-y-6">
        <div class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-card">
            <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <p class="text-sm font-black text-brand-600">Solve Merchant Product Control</p>
                    <h2 class="mt-2 text-3xl font-black text-slate-950">{{ $productsDashboard['title'] }}</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-500">{{ $productsDashboard['summary'] }}</p>
                    <div class="mt-4 flex flex-wrap gap-2 text-xs font-black text-slate-600">
                        <span class="rounded-full bg-slate-50 px-3 py-2">منتجات كل متجر</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">إدارة المنتجات</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">مخزون</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">ملخص الطلبات</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">فلترة حسب store_id</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">منتج متعدد الخيارات</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">مسودة</span>
                        <span class="rounded-full bg-slate-50 px-3 py-2">حفظ تلقائي</span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.products') }}" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700">كل المتاجر</a>
                    @if ($selectedStore)
                        <a href="{{ route('admin.orders', ['store_id' => $selectedStore['id']]) }}" class="rounded-2xl bg-brand-50 px-5 py-3 text-sm font-black text-brand-700">طلبات هذا المتجر</a>
                        <a href="{{ route('admin.partners.show', ['partner' => $selectedStore['partner_id'] ?? \Illuminate\Support\Str::after($selectedStore['id'], 'store-')]) }}" class="rounded-2xl bg-slate-950 px-5 py-3 text-sm font-black text-white">فتح ملف الشريك</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4" data-testid="admin-product-store-cards">
            @foreach ($productsDashboard['stores'] as $store)
                <a href="{{ $store['url'] }}" class="rounded-[24px] border p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-soft {{ $store['active'] ? 'border-brand-200 bg-brand-50' : 'border-slate-100 bg-white' }}" data-testid="admin-product-store-card">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-black text-slate-950">{{ $store['name'] }}</h3>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $store['id'] }} · {{ $store['owner'] }}</p>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-black text-brand-700">{{ $store['plan'] }}</span>
                    </div>
                    <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-2xl bg-white/80 p-3">
                            <p class="text-xs font-bold text-slate-500">المنتجات</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ $store['products_count'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-3">
                            <p class="text-xs font-bold text-slate-500">نشطة</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ $store['active_count'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-white/80 p-3">
                            <p class="text-xs font-bold text-slate-500">منخفض</p>
                            <p class="mt-1 text-xl font-black text-slate-950">{{ $store['low_stock_count'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center justify-between text-xs font-black">
                        <span class="text-slate-500">طلبات: {{ $store['orders_count'] }} · {{ $store['sales_total'] }}</span>
                        <span class="text-brand-700">عرض المنتجات</span>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($productsDashboard['stats'] as $stat)
                <article class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                    <p class="text-sm font-bold text-slate-500">{{ $stat['label'] }}</p>
                    <p class="mt-3 text-3xl font-black text-slate-950">{{ $stat['value'] }}</p>
                    <p class="mt-2 text-xs font-black text-brand-600">{{ $stat['hint'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="rounded-[28px] border border-slate-100 bg-white p-5 shadow-card">
            <form method="GET" action="{{ route('admin.products') }}" class="grid gap-3 lg:grid-cols-[1fr_180px_180px_160px]">
                <input type="hidden" name="store_id" value="{{ $filters['store_id'] }}">
                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="بحث باسم المنتج أو SKU أو المتجر" class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-brand-300 focus:bg-white">
                <select name="status" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                    <option value="all">كل الحالات</option>
                    @foreach ($productsDashboard['statusOptions'] as $option)
                        <option value="{{ $option }}" @selected($filters['status'] === $option)>{{ $option }}</option>
                    @endforeach
                </select>
                <select name="stock" class="h-12 rounded-2xl border border-slate-200 bg-white px-4 text-sm font-bold">
                    @foreach ($productsDashboard['stockOptions'] as $value => $label)
                        <option value="{{ $value }}" @selected($filters['stock'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="h-12 rounded-2xl bg-slate-950 px-5 text-sm font-black text-white">تطبيق الفلتر</button>
            </form>
        </div>

        <div class="grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
            <section class="rounded-[28px] border border-slate-100 bg-white p-6 shadow-card">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-950">{{ $selectedStore ? 'منتجات ' . $selectedStore['name'] : 'كل المنتجات' }}</h3>
                        <p class="mt-1 text-sm font-bold text-slate-500">{{ count($productsDashboard['products']) }} منتج معروض من أصل {{ $productsDashboard['allProductsCount'] }}</p>
                    </div>
                    <button type="button" class="rounded-2xl bg-slate-50 px-4 py-2 text-sm font-black text-slate-600" @click="$dispatch('solve-toast', 'تم تجهيز تصدير المنتجات الحالية')">تصدير</button>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-100">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="p-4 font-black">المتجر</th>
                                <th class="p-4 font-black">المنتج</th>
                                <th class="p-4 font-black">SKU</th>
                                <th class="p-4 font-black">السعر</th>
                                <th class="p-4 font-black">المخزون</th>
                                <th class="p-4 font-black">الحالة</th>
                                <th class="p-4 font-black">إجراء</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($productsDashboard['products'] as $product)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="p-4 font-black text-slate-800">
                                        <a href="{{ route('admin.products', ['store_id' => $product['store_id']]) }}" class="text-brand-700">{{ $product['store'] }}</a>
                                        <p class="mt-1 text-xs font-bold text-slate-400">{{ $product['store_id'] }}</p>
                                    </td>
                                    <td class="p-4 font-black text-slate-900">{{ $product['name'] }}</td>
                                    <td class="p-4 font-bold text-slate-700">{{ $product['sku'] }}</td>
                                    <td class="p-4 font-black text-slate-900">{{ $product['price'] }}</td>
                                    <td class="p-4">
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-700">{{ $product['stock'] }}</span>
                                    </td>
                                    <td class="p-4 font-bold text-slate-700">{{ $product['status'] }}</td>
                                    <td class="p-4">
                                        <a href="{{ route('admin.products', ['store_id' => $product['store_id'], 'q' => $product['sku']]) }}" class="rounded-xl bg-brand-50 px-3 py-2 text-xs font-black text-brand-700">عرض</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="p-10 text-center text-sm font-black text-slate-500">لا توجد منتجات مطابقة لهذا المتجر أو الفلتر.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                    <h4 class="text-lg font-black text-slate-950">ملخص طلبات المتجر</h4>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-500">الطلبات</p>
                            <p class="mt-1 text-xl font-black">{{ $productsDashboard['ordersSummary']['count'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-500">متابعة</p>
                            <p class="mt-1 text-xl font-black">{{ $productsDashboard['ordersSummary']['pending'] }}</p>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-xs font-bold text-slate-500">المبيعات</p>
                            <p class="mt-1 text-sm font-black">{{ $productsDashboard['ordersSummary']['sales'] }}</p>
                        </div>
                    </div>
                    <div class="mt-4 grid gap-2">
                        @forelse ($productsDashboard['ordersSummary']['latest'] as $order)
                            <a href="{{ route('admin.orders.show', ['order' => $order['id']]) }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">
                                {{ $order['order_number'] }} · {{ $order['total'] }}
                            </a>
                        @empty
                            <p class="rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm font-black text-slate-500">لا توجد طلبات لهذا المتجر بعد.</p>
                        @endforelse
                    </div>
                    @if ($selectedStore)
                        <a href="{{ route('admin.orders', ['store_id' => $selectedStore['id']]) }}" class="mt-4 block rounded-2xl bg-slate-950 px-4 py-3 text-center text-sm font-black text-white">فتح إدارة طلبات المتجر</a>
                    @endif
                </div>
                <div class="rounded-[24px] border border-slate-100 bg-white p-5 shadow-card">
                    <h4 class="text-lg font-black text-slate-950">جزء التحكم الكامل</h4>
                    <p class="mt-2 text-sm leading-7 text-slate-500">من هنا تراجع منتجات كل متجر، مخزونه، وطلباته المرتبطة. اختيار المتجر يفلتر كل البيانات حسب store_id.</p>
                    <div class="mt-4 grid gap-2">
                        <a href="{{ route('admin.stores') }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">إدارة المتاجر</a>
                        <a href="{{ route('admin.inventory') }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">مركز المخزون</a>
                        <a href="{{ route('admin.orders') }}" class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-black text-slate-700">إدارة الطلبات</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
@endsection
