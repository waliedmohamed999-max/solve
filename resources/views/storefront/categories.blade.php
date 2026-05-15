@extends('storefront.layout')

@section('title', 'تصنيفات ' . ($store['name'] ?? $partner['name']))

@section('content')
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="section-kicker">أقسام المتجر</span>
                    <h2>التصنيفات</h2>
                    <p>كل تصنيف مرتبط بمنتجات المتجر ويحوّل العميل مباشرة لقائمة منتجات مفلترة.</p>
                </div>
                <a class="btn btn-soft" href="{{ url('/store/' . $slug . '/products') }}">كل المنتجات</a>
            </div>
            <div class="grid">
                @forelse($categories as $category)
                    <a class="panel category-card" href="{{ url('/store/' . $slug . '/products?category=' . urlencode($category['name'] ?? '')) }}">
                        <span class="badge">{{ $category['products_count'] ?? 0 }} منتج</span>
                        <h2 style="margin:14px 0 8px">{{ $category['name'] ?? 'تصنيف' }}</h2>
                        <p class="muted">تصفح منتجات هذا القسم مع البحث والفرز والسعر.</p>
                        <span class="btn btn-soft" style="margin-top:10px">عرض المنتجات</span>
                    </a>
                @empty
                    <div class="empty-state" style="grid-column:1/-1">
                        <div>
                            <h3 style="margin:0 0 8px">لا توجد تصنيفات بعد</h3>
                            <p class="muted">ستظهر التصنيفات عند إضافتها من لوحة التاجر.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
