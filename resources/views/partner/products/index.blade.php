@extends('layouts.partner')

@section('title', 'Solve Merchant | المنتجات')

@section('partner-content')
@php
    $filters = $productsPage['filters'];
    $pagination = $productsPage['pagination'];
    $isGrid = ($filters['view'] ?? 'table') === 'grid';
    $canManageProducts = \App\Support\PartnerTenantStore::can($partnerUser, 'view-products');
    $statusColors = [
        'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
        'low_stock' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-200',
        'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
        'paused' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-200',
    ];
@endphp

<div class="px-4 py-6 lg:px-8" x-data="{ selectAll: false, loading: false, error: '' }">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <section class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
        <div>
            <h1 class="text-3xl font-black text-slate-950 dark:text-white">المنتجات</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">جميع منتجات متجرك هنا · {{ $partner['store_id'] }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('partner.products.new') }}" class="rounded-full bg-solve-700 px-6 py-3 text-sm font-black text-white hover:bg-solve-800">إنشاء</a>
            <a href="{{ route('partner.products.export', request()->query()) }}" class="rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">تصدير CSV</a>
            <a href="{{ route('partner.api.products', request()->query()) }}" class="rounded-full border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">API</a>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="flex gap-4 overflow-x-auto border-b border-slate-100 px-4 py-4 text-sm font-black dark:border-slate-800">
            @foreach ($productsPage['typeOptions'] as $key => $label)
                <a href="{{ route('partner.products', array_merge(request()->except('type'), ['type' => $key])) }}"
                    class="flex shrink-0 items-center gap-2 rounded-xl px-4 py-2 {{ ($filters['type'] === $key || ($filters['type'] === '' && $key === 'all')) ? 'bg-slate-100 text-solve-700 dark:bg-slate-800 dark:text-solve-200' : 'text-slate-600 hover:bg-slate-50 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    <span>{{ $label }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500 dark:bg-slate-700 dark:text-slate-300">{{ $productsPage['counts'][$key] ?? 0 }}</span>
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('partner.products') }}" class="flex flex-col gap-3 border-b border-slate-100 p-4 dark:border-slate-800 lg:flex-row lg:items-center">
            <input type="hidden" name="type" value="{{ $filters['type'] }}">
            <div class="relative w-full max-w-md">
                <span class="absolute inset-y-0 right-4 flex items-center text-slate-400">@include('partner.partials.icon', ['name' => 'search', 'class' => 'h-4 w-4'])</span>
                <input name="q" value="{{ $filters['q'] }}" type="search" placeholder="بحث بالاسم أو SKU"
                    class="h-11 w-full rounded-full border border-slate-200 bg-white pr-11 pl-4 text-sm font-bold outline-none focus:border-solve-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
            </div>
            <select name="status" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($productsPage['statusOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['status'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($productsPage['categoryOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['category'] ?? 'all') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="stock" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                @foreach ($productsPage['stockOptions'] as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['stock'] ?? 'all') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="view" class="h-11 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                <option value="table" @selected(! $isGrid)>Table View</option>
                <option value="grid" @selected($isGrid)>Grid View</option>
            </select>
            <button class="h-11 rounded-full bg-slate-950 px-5 text-sm font-black text-white dark:bg-white dark:text-slate-950">تطبيق</button>
            <a href="{{ route('partner.products') }}" class="flex h-11 items-center rounded-full border border-slate-200 px-5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">إعادة ضبط</a>
        </form>

        <div class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 sm:grid-cols-4">
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">المعروض</p><p class="mt-1 text-xl font-black">{{ $productsPage['summary']['filtered'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">الكل</p><p class="mt-1 text-xl font-black">{{ $productsPage['summary']['total'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">منشور</p><p class="mt-1 text-xl font-black">{{ $productsPage['summary']['published'] }}</p></div>
            <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-950"><p class="text-xs font-black text-slate-400">مخزون منخفض</p><p class="mt-1 text-xl font-black">{{ $productsPage['summary']['low_stock'] }}</p></div>
        </div>

        <div class="border-b border-slate-100 px-4 py-3 text-sm font-black text-slate-500 dark:border-slate-800" x-show="loading">
            <div class="grid gap-2 md:grid-cols-4">
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
                <span class="h-3 animate-pulse rounded-full bg-slate-100 dark:bg-slate-800"></span>
            </div>
        </div>
        <div class="border-b border-rose-100 bg-rose-50 px-4 py-3 text-sm font-black text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200" x-show="error" x-text="error" x-cloak></div>

        @if (count($productsPage['products']))
            <form method="POST" action="{{ route('partner.products.bulk') }}" onsubmit="return confirm('تأكيد تطبيق الإجراء على المنتجات المحددة؟')">
                @csrf
                @if ($canManageProducts)
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 p-4 dark:border-slate-800">
                        <select name="status" class="h-10 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                            @foreach (\App\Support\PartnerProducts::PRODUCT_STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input name="category" placeholder="تصنيف اختياري" class="h-10 rounded-full border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                        <button class="h-10 rounded-full bg-solve-700 px-5 text-sm font-black text-white hover:bg-solve-800">تطبيق Bulk Action</button>
                    </div>
                @endif
            @if ($isGrid)
                <div class="grid gap-4 p-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($productsPage['products'] as $product)
                        <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950">
                            <div class="flex items-start justify-between gap-3">
                                <input name="product_ids[]" value="{{ $product['id'] }}" type="checkbox" :checked="selectAll" class="mt-2 rounded border-slate-300">
                                <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-white text-2xl font-black dark:bg-slate-900">
                                    @if ($product['image'])
                                        <img src="{{ asset($product['image']) }}" alt="" class="h-24 w-24 rounded-2xl object-cover">
                                    @else
                                        {{ mb_substr($product['name'], 0, 1) }}
                                    @endif
                                </div>
                                <span class="{{ $statusColors[$product['status_key']] ?? 'bg-slate-100 text-slate-700' }} rounded-full px-3 py-1 text-xs font-black">{{ $product['status'] }}</span>
                            </div>
                            <a href="{{ route('partner.products.edit', ['product' => $product['id']]) }}" class="mt-4 block text-lg font-black text-slate-950 hover:text-solve-700 dark:text-white">{{ $product['name'] }}</a>
                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $product['sku'] }} · {{ $product['category'] }}</p>
                            <div class="mt-4 grid grid-cols-2 gap-2 text-sm font-black">
                                <div class="rounded-xl bg-white p-3 dark:bg-slate-900"><span class="block text-xs text-slate-400">السعر</span>{{ $product['price'] }}</div>
                                <div class="rounded-xl bg-white p-3 dark:bg-slate-900"><span class="block text-xs text-slate-400">المخزون</span>{{ $product['stock'] }}</div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            <th class="px-4 py-4"><input type="checkbox" x-model="selectAll" class="rounded border-slate-300"></th>
                            <th class="px-4 py-4">الاسم<br><span class="font-bold text-slate-400">كود SKU</span></th>
                            <th class="px-4 py-4">الكمية</th>
                            <th class="px-4 py-4">السعر<br><span class="font-bold text-slate-400">العملة</span></th>
                            <th class="px-4 py-4">الحالة</th>
                            <th class="px-4 py-4">تاريخ الإنشاء<br><span class="font-bold text-slate-400">تاريخ التحديث</span></th>
                            <th class="px-4 py-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                        @foreach ($productsPage['products'] as $product)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                <td class="px-4 py-4"><input name="product_ids[]" value="{{ $product['id'] }}" type="checkbox" :checked="selectAll" class="rounded border-slate-300"></td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-xs font-black dark:bg-slate-800">
                                            @if ($product['image'])
                                                <img src="{{ asset($product['image']) }}" alt="" class="h-11 w-11 rounded-xl object-cover">
                                            @else
                                                {{ mb_substr($product['name'], 0, 1) }}
                                            @endif
                                        </div>
                                        <div>
                                            <a href="{{ route('partner.products.edit', ['product' => $product['id']]) }}" class="font-black text-slate-950 hover:text-solve-700 dark:text-white">{{ $product['name'] }}</a>
                                            <p class="mt-1 text-xs font-bold text-slate-500">{{ $product['sku'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-black">{{ $product['stock'] }}</td>
                                <td class="px-4 py-4 font-black">{{ $product['price'] }}<p class="text-xs font-bold text-slate-500">ر.س</p></td>
                                <td class="px-4 py-4"><span class="{{ $statusColors[$product['status_key']] ?? 'bg-slate-100 text-slate-700' }} rounded-full px-3 py-1 text-xs font-black">{{ $product['status'] }}</span></td>
                                <td class="px-4 py-4 font-bold text-slate-600 dark:text-slate-300">{{ $product['created_at'] }}<p class="text-xs text-slate-400">{{ $product['updated_at_human'] }}</p></td>
                                <td class="px-4 py-4">
                                    <div class="flex gap-2">
                                        <a href="{{ route('partner.products.edit', ['product' => $product['id']]) }}" class="rounded-full bg-solve-700 px-4 py-2 text-xs font-black text-white">تعديل</a>
                                        <form method="POST" action="{{ route('partner.products.delete', ['product' => $product['id']]) }}" onsubmit="return confirm('حذف المنتج؟')">
                                            @csrf
                                            <button class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">حذف</button>
                                        </form>
                                        <form method="POST" action="{{ route('partner.products.duplicate', ['product' => $product['id']]) }}">
                                            @csrf
                                            <button class="rounded-full border border-slate-200 px-4 py-2 text-xs font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">تكرار</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            </form>
            <div class="flex flex-col gap-3 border-t border-slate-100 p-4 text-sm font-black text-slate-500 dark:border-slate-800 md:flex-row md:items-center md:justify-between">
                <span>عرض {{ $pagination['from'] }} - {{ $pagination['to'] }} من {{ $pagination['total'] }}</span>
                <div class="flex gap-2">
                    @if ($pagination['page'] > 1)
                        <a href="{{ route('partner.products', array_merge(request()->query(), ['page' => $pagination['page'] - 1])) }}" class="rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">السابق</a>
                    @endif
                    <span class="rounded-full bg-slate-100 px-4 py-2 dark:bg-slate-800">{{ $pagination['page'] }} / {{ $pagination['last_page'] }}</span>
                    @if ($pagination['page'] < $pagination['last_page'])
                        <a href="{{ route('partner.products', array_merge(request()->query(), ['page' => $pagination['page'] + 1])) }}" class="rounded-full border border-slate-200 px-4 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">التالي</a>
                    @endif
                </div>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد منتجات مطابقة</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">غيّر الفلاتر أو أنشئ منتجاً جديداً.</p>
                <a href="{{ route('partner.products.new') }}" class="mt-5 inline-flex rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">إنشاء منتج</a>
            </div>
        @endif
    </section>
</div>
@endsection
