@extends('layouts.partner')

@section('title', 'Solve Merchant | ' . $title)

@section('partner-content')
<div class="px-4 py-6 lg:px-8">
    @if (session('status'))
        <div class="mb-4 rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-black text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ session('status') }}</div>
    @endif

    <div class="mb-6 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
        <div>
            <a href="{{ route('partner.products') }}" class="text-sm font-black text-solve-600 dark:text-solve-300">المنتجات</a>
            <h1 class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ $title }}</h1>
            <p class="mt-2 text-sm font-bold text-slate-500">بيانات حقيقية من `platform_records` ومفلترة حسب {{ $partner['store_id'] }}.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-4 py-2 text-sm font-black text-slate-600 dark:bg-slate-800 dark:text-slate-200">{{ count($rows) }} سجل</span>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <form method="POST" action="{{ route('api.partner.' . match($section) { 'product_categories' => 'categories.store', 'product_filters' => 'product-filters.store', 'product_custom_fields' => 'custom-fields.store', default => 'options.store' }) }}" class="grid gap-3 border-b border-slate-100 p-4 dark:border-slate-800 md:grid-cols-5">
            @csrf
            <input name="name" required placeholder="الاسم" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            <input name="values" placeholder="القيم" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            <input name="category" placeholder="التصنيف" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
            <select name="type" class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold dark:border-slate-700 dark:bg-slate-950">
                <option value="نص">نص</option>
                <option value="رقم">رقم</option>
                <option value="اختيار">اختيار</option>
                <option value="تاريخ">تاريخ</option>
                <option value="ملف">ملف</option>
            </select>
            <button class="h-11 rounded-xl bg-solve-700 px-4 text-sm font-black text-white">إضافة</button>
        </form>
        @if (count($rows))
            @php $columns = array_values(array_unique(collect($rows)->flatMap(fn ($row) => array_keys($row))->reject(fn ($key) => in_array($key, ['store_id'], true))->all())); @endphp
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-right text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                        <tr>
                            @foreach ($columns as $column)
                                <th class="whitespace-nowrap px-4 py-4">{{ $column }}</th>
                            @endforeach
                            <th class="whitespace-nowrap px-4 py-4">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($rows as $row)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-950/60">
                                @foreach ($columns as $column)
                                    <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-700 dark:text-slate-300">{{ is_array($row[$column] ?? null) ? json_encode($row[$column], JSON_UNESCAPED_UNICODE) : ($row[$column] ?? '-') }}</td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-4">
                                    <form method="POST" action="{{ route('api.partner.' . match($section) { 'product_categories' => 'categories.delete', 'product_filters' => 'product-filters.delete', 'product_custom_fields' => 'custom-fields.delete', default => 'options.delete' }, ['record' => $row['id']]) }}" onsubmit="return confirm('تأكيد الحذف؟')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-full border border-slate-200 px-3 py-2 text-xs font-black hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">حذف</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <h2 class="text-xl font-black text-slate-950 dark:text-white">لا توجد بيانات في {{ $title }}</h2>
                <p class="mt-2 text-sm font-bold text-slate-500">ستظهر البيانات هنا عند إضافتها أو مزامنتها من النظام.</p>
            </div>
        @endif
    </section>
</div>
@endsection
