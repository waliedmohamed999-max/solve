@extends('layouts.partner')

@section('title', $title . ' | Solve')

@section('partner-content')
@php
    $isPages = $section === 'storefront_pages';
    $routeKey = $isPages ? 'pages' : 'banners';
    $rows = $storefrontPage['rows'] ?? [];
    $pagination = $storefrontPage['pagination'] ?? ['total' => count($rows)];
    $summary = $storefrontPage['summary'] ?? ['total' => count($rows), 'active' => 0, 'draft' => 0];
    $statusOptions = $storefrontPage['statusOptions'] ?? [];
    $filters = $storefrontPage['filters'] ?? ['q' => '', 'status' => 'all'];
    $placements = ['home_hero' => 'Hero رئيسي', 'home_secondary' => 'بنر عريض', 'category_top' => 'أعلى التصنيف', 'product_strip' => 'قبل المنتجات'];
    $resolveImage = function (?string $path) {
        if (! $path) {
            return asset('solve-logo.png');
        }
        return str_starts_with($path, 'http') ? $path : asset($path);
    };
@endphp

<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-black text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-6 p-6 lg:grid-cols-[1fr_340px] lg:items-center">
            <div>
                <div class="flex items-center gap-2 text-sm font-black text-solve-700 dark:text-solve-300">
                    <a href="{{ route('partner.storefront') }}">المتجر الإلكتروني</a>
                    <span>/</span>
                    <span>{{ $title }}</span>
                </div>
                <h1 class="mt-3 text-4xl font-black tracking-tight text-slate-950 dark:text-white">{{ $title }}</h1>
                <p class="mt-3 max-w-3xl text-sm font-bold leading-7 text-slate-500">
                    {{ $isPages ? 'أنشئ صفحات المتجر، حرر المحتوى، اضبط SEO، وانشر الصفحات بدون الخروج من لوحة الشريك.' : 'أنشئ بنرات تسويقية، اربطها بمنتج أو تصنيف، وجدول ظهورها مع معاينة واضحة قبل النشر.' }}
                </p>
                <div class="mt-5 flex flex-wrap gap-2">
                    @include('partner.partials.api-tools', [
                        'url' => route('api.partner.' . $routeKey . '.index'),
                        'copyLabel' => 'نسخ API',
                        'openLabel' => 'فتح API',
                    ])
                    <a href="{{ route('partner.storefront.customize') }}" class="rounded-full bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">تعديل الواجهة</a>
                    @if (! $isPages)
                        <a href="{{ route('partner.storefront') }}" class="rounded-full bg-solve-700 px-5 py-3 text-sm font-black text-white">معاينة المتجر</a>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-3xl bg-slate-50 p-4 dark:bg-slate-950">
                    <p class="text-xs font-black text-slate-400">الإجمالي</p>
                    <p class="mt-3 text-3xl font-black">{{ $summary['total'] ?? $pagination['total'] }}</p>
                </div>
                <div class="rounded-3xl bg-emerald-50 p-4 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200">
                    <p class="text-xs font-black">النشط</p>
                    <p class="mt-3 text-3xl font-black">{{ $summary['active'] ?? 0 }}</p>
                </div>
                <div class="rounded-3xl bg-amber-50 p-4 text-amber-800 dark:bg-amber-950/40 dark:text-amber-200">
                    <p class="text-xs font-black">{{ $isPages ? 'مسودات' : 'مجدول' }}</p>
                    <p class="mt-3 text-3xl font-black">{{ $summary['draft'] ?? 0 }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('partner.storefront.' . $routeKey) }}" class="grid gap-3 lg:grid-cols-[1fr_220px_180px_160px]">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="بحث سريع بالعنوان أو الرابط" class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold outline-none focus:border-solve-500 dark:border-slate-700 dark:bg-slate-950">
            <select name="status" class="h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                @foreach ($statusOptions as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['status'] ?? 'all') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="h-12 rounded-2xl bg-slate-950 px-4 text-sm font-black text-white dark:bg-white dark:text-slate-950">تطبيق الفلتر</button>
            <a href="{{ route('partner.storefront.' . $routeKey) }}" class="flex h-12 items-center justify-center rounded-2xl border border-slate-200 text-sm font-black dark:border-slate-700">إعادة ضبط</a>
        </form>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-2xl font-black text-slate-950 dark:text-white">{{ $isPages ? 'إضافة صفحة جديدة' : 'إضافة بنر تسويقي' }}</h2>
                <p class="mt-1 text-sm font-bold text-slate-500">{{ $isPages ? 'استخدمها لسياسات المتجر وصفحات الهبوط.' : 'أضف صورة حقيقية، مكان الظهور، الرابط، وجدولة البداية والنهاية.' }}</p>
            </div>
            <span class="rounded-full bg-solve-50 px-4 py-2 text-xs font-black text-solve-700 dark:bg-solve-900/30 dark:text-solve-200">store-atlas</span>
        </div>

        <form method="POST" action="{{ route('partner.storefront.' . $routeKey . '.store') }}" class="grid gap-3 {{ $isPages ? 'lg:grid-cols-6' : 'lg:grid-cols-12' }}">
            @csrf
            @if ($isPages)
                <input name="title" required placeholder="عنوان الصفحة" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-2">
                <input name="slug" placeholder="slug مثال: returns-policy" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                    @foreach (\App\Support\PartnerStorefront::PAGE_STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input name="seo_title" placeholder="SEO Title" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <button class="rounded-2xl bg-solve-700 px-4 py-3 text-sm font-black text-white">إضافة صفحة</button>
                <textarea name="content" rows="4" placeholder="محتوى الصفحة" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-6"></textarea>
                <input name="seo_description" placeholder="SEO Description" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-3">
                <input name="preview_url" placeholder="رابط المعاينة" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-3">
            @else
                <input name="title" required placeholder="عنوان البنر" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-3">
                <input name="image_url" placeholder="رابط الصورة أو مسارها" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-4">
                <select name="placement" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-2">
                    @foreach ($placements as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button class="rounded-2xl bg-solve-700 px-4 py-3 text-sm font-black text-white lg:col-span-3">إضافة بنر</button>
                <select name="link_type" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-2">
                    <option value="url">رابط</option><option value="product">منتج</option><option value="category">تصنيف</option><option value="page">صفحة</option>
                </select>
                <input name="link_target" placeholder="الهدف: /products أو featured" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-3">
                <input type="number" name="sort_order" value="1" min="0" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-1">
                <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-2">
                    @foreach (\App\Support\PartnerStorefront::BANNER_STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="starts_at" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-2">
                <input type="date" name="ends_at" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 lg:col-span-2">
            @endif
        </form>
    </section>

    @if (! $isPages)
        <form method="POST" action="{{ route('partner.storefront.banners.reorder') }}" class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            @csrf
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-xl font-black">ترتيب البنرات</h2>
                    <p class="mt-1 text-sm font-bold text-slate-500">رتب البنرات بالأولوية. يمكنك تعديل الأرقام ثم حفظ الترتيب.</p>
                </div>
                <button class="rounded-full bg-slate-950 px-6 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">حفظ الترتيب</button>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($rows as $row)
                    <label class="flex items-center gap-3 rounded-2xl bg-slate-50 p-3 dark:bg-slate-950">
                        <input type="hidden" name="order[]" value="{{ $row['id'] }}">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-sm font-black text-solve-700 dark:bg-slate-900">{{ $row['sort_order'] ?? $loop->iteration }}</span>
                        <span class="min-w-0 flex-1 truncate text-sm font-black">{{ $row['title'] }}</span>
                    </label>
                @endforeach
            </div>
        </form>
    @endif

    <section class="grid gap-5 {{ $isPages ? 'xl:grid-cols-2' : 'xl:grid-cols-2' }}">
        @forelse ($rows as $row)
            <article class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
                @if (! $isPages)
                    <div class="relative h-48 overflow-hidden bg-slate-100 dark:bg-slate-950">
                        <img src="{{ $resolveImage($row['image_url'] ?? null) }}" alt="{{ $row['title'] }}" class="h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-l from-slate-950/70 via-slate-950/20 to-transparent"></div>
                        <div class="absolute right-5 top-5 flex gap-2">
                            <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-black text-slate-950">{{ $row['placement'] ?? '-' }}</span>
                            <span class="rounded-full bg-solve-600 px-3 py-1 text-xs font-black text-white">{{ $row['status'] }}</span>
                        </div>
                        <div class="absolute bottom-5 right-5 text-white">
                            <h2 class="text-2xl font-black">{{ $row['title'] }}</h2>
                            <p class="mt-1 text-sm font-bold opacity-80">{{ $row['link_type'] ?? 'url' }} · {{ $row['link_target'] ?? '-' }}</p>
                        </div>
                    </div>
                @else
                    <div class="border-b border-slate-100 p-5 dark:border-slate-800">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="text-2xl font-black">{{ $row['title'] }}</h2>
                                <p class="mt-1 text-xs font-bold text-slate-500">/{{ $row['slug'] ?? '' }}</p>
                            </div>
                            <span class="rounded-full bg-solve-50 px-3 py-1 text-xs font-black text-solve-700">{{ $row['status'] }}</span>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('partner.storefront.' . $routeKey . '.update', ['record' => $row['id']]) }}" class="grid gap-3 p-5">
                    @csrf
                    @if ($isPages)
                        <input name="title" value="{{ $row['title'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        <input name="slug" value="{{ $row['slug'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        <textarea name="content" rows="4" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">{{ $row['content'] ?? '' }}</textarea>
                        <div class="grid gap-3 md:grid-cols-2">
                            <input name="seo_title" value="{{ $row['seo_title'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <input name="seo_description" value="{{ $row['seo_description'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <input name="preview_url" value="{{ $row['preview_url'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                @foreach (\App\Support\PartnerStorefront::PAGE_STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected(($row['status_key'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input name="title" value="{{ $row['title'] }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        <input name="image_url" value="{{ $row['image_url'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                        <div class="grid gap-3 md:grid-cols-2">
                            <select name="link_type" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                @foreach (['url' => 'رابط', 'product' => 'منتج', 'category' => 'تصنيف', 'page' => 'صفحة'] as $key => $label)
                                    <option value="{{ $key }}" @selected(($row['link_type'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="link_target" value="{{ $row['link_target'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <select name="placement" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                                @foreach ($placements as $key => $label)
                                    <option value="{{ $key }}" @selected(($row['placement'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input name="sort_order" type="number" value="{{ $row['sort_order'] ?? 1 }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <input type="date" name="starts_at" value="{{ $row['starts_at'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <input type="date" name="ends_at" value="{{ $row['ends_at'] ?? '' }}" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                            <select name="status" class="rounded-2xl border border-slate-200 px-4 py-3 text-sm font-bold dark:border-slate-700 dark:bg-slate-950 md:col-span-2">
                                @foreach (\App\Support\PartnerStorefront::BANNER_STATUSES as $key => $label)
                                    <option value="{{ $key }}" @selected(($row['status_key'] ?? '') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="flex flex-wrap justify-between gap-2 pt-2">
                        <div class="flex gap-2">
                            <button class="rounded-full bg-slate-950 px-5 py-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">حفظ</button>
                            <button form="delete-{{ $row['id'] }}" class="rounded-full border border-rose-200 px-5 py-3 text-sm font-black text-rose-700" onclick="return confirm('تأكيد الحذف؟')">حذف</button>
                        </div>
                        @if (! empty($row['preview_url']))
                            <a href="{{ $row['preview_url'] }}" target="_blank" class="rounded-full border border-slate-200 px-5 py-3 text-sm font-black dark:border-slate-700">معاينة</a>
                        @endif
                    </div>
                </form>
                <form id="delete-{{ $row['id'] }}" method="POST" action="{{ route('partner.storefront.' . $routeKey . '.delete', ['record' => $row['id']]) }}">@csrf</form>
            </article>
        @empty
            <div class="rounded-[2rem] border border-dashed border-slate-300 bg-white p-12 text-center shadow-card dark:border-slate-700 dark:bg-slate-900 xl:col-span-2">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-solve-50 text-2xl font-black text-solve-700">+</div>
                <h2 class="mt-4 text-2xl font-black">لا توجد سجلات بعد</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">ابدأ بإضافة {{ $isPages ? 'صفحة' : 'بنر' }} من النموذج بالأعلى.</p>
            </div>
        @endforelse
    </section>
</div>
@endsection
